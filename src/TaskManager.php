<?php

namespace DavidBadura\Taskwarrior;

use DavidBadura\Taskwarrior\Config\Context;
use DavidBadura\Taskwarrior\Config\Report;
use DavidBadura\Taskwarrior\Exception\ReferenceException;
use DavidBadura\Taskwarrior\Exception\TaskwarriorException;
use DavidBadura\Taskwarrior\Proxy\UuidContainer;
use DavidBadura\Taskwarrior\Query\QueryBuilder;
use DavidBadura\Taskwarrior\Serializer\Handler\CarbonHandler;
use DavidBadura\Taskwarrior\Serializer\Handler\DependsHandler;
use DavidBadura\Taskwarrior\Serializer\Handler\RecurringHandler;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;
use ProxyManager\Factory\LazyLoadingValueHolderFactory;
use ProxyManager\Proxy\LazyLoadingInterface;
use ProxyManager\Proxy\ValueHolderInterface;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class TaskManager
{
    const PATTERN = '/^[\wäüö]*$/i';

    /**
     * @var Taskwarrior
     */
    private $taskwarrior;

    /**
     * @var Task[]
     */
    private $tasks = [];

    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @param Taskwarrior $taskwarrior
     */
    public function __construct(Taskwarrior $taskwarrior)
    {
        $this->taskwarrior = $taskwarrior;
    }

    /**
     * @return Taskwarrior
     */
    public function getTaskwarrior()
    {
        return $this->taskwarrior;
    }

    /**
     * @param Task $task
     * @throws TaskwarriorException
     */
    public function save(Task $task)
    {
        $errors = $this->validate($task);

        if ($errors) {
            throw new TaskwarriorException(implode(', ', $errors));
        }

        if (!$task->getUuid()) {
            $this->add($task);
        } else {
            $this->edit($task);
        }

        $this->refresh($task);
    }

    /**
     * @param string $uuid
     * @return Task
     * @throws TaskwarriorException
     */
    public function find($uuid)
    {
        if (isset($this->tasks[$uuid])) {
            return $this->tasks[$uuid];
        }

        $task = $this->exportOne($uuid);

        return $this->tasks[$uuid] = $task;
    }

    /**
     * @param string $filter
     * @return Task[]|ArrayCollection
     */
    public function filter($filter = null)
    {
        $result = $this->export($filter);

        foreach ($result as $key => $task) {

            // not yet known? then remember it
            if (!isset($this->tasks[$task->getUuid()])) {
                $this->tasks[$task->getUuid()] = $task;

                continue;
            }

            // replace result entry
            $result[$key] = $prev = $this->tasks[$task->getUuid()];

            // not proxy? update task
            if (!$prev instanceof LazyLoadingInterface || !$prev instanceof ValueHolderInterface) {
                $this->merge($prev, $task);

                continue;
            }

            // wrapper object is a task? skip
            if ($prev->getWrappedValueHolderValue() instanceof Task) {
                continue;
            }

            // replace proxy initializer
            $prev->setProxyInitializer(function (&$wrappedObject, LazyLoadingInterface $proxy) use ($task) {
                $proxy->setProxyInitializer(null);
                $wrappedObject = $task;
            });
        }

        return new ArrayCollection($result);
    }

    /**
     * @param string|array $filter
     * @return Task[]|ArrayCollection
     */
    public function filterPending($filter = null)
    {
        return $this->filter(array_merge((array)$filter, ['status:pending']));
    }

    /**
     * @param Task $task
     */
    public function delete(Task $task)
    {
        if (!$task->getUuid()) {
            return;
        }

        $this->taskwarrior->delete($task->getUuid());
        $this->refresh($task);
    }

    /**
     * @param Task $task
     */
    public function done(Task $task)
    {
        if (!$task->getUuid()) {
            return;
        }

        $this->taskwarrior->done($task->getUuid());
        $this->refresh($task);
    }

    /**
     * @param Task $task
     */
    public function start(Task $task)
    {
        if (!$task->getUuid()) {
            return;
        }

        $this->taskwarrior->start($task->getUuid());
        $this->refresh($task);
    }

    /**
     * @param Task $task
     */
    public function stop(Task $task)
    {
        if (!$task->getUuid()) {
            return;
        }

        $this->taskwarrior->stop($task->getUuid());
        $this->refresh($task);
    }

    /**
     * @param Task $task
     */
    public function reopen(Task $task)
    {
        if (!$task->getUuid()) {
            return;
        }

        if ($task->isPending() || $task->isWaiting() || $task->isRecurring()) {
            return;
        }

        $this->taskwarrior->modify([
            'status' => Task::STATUS_PENDING
        ], $task->getUuid());

        $this->refresh($task);
    }

    /**
     * @param Task $task
     * @return array
     */
    public function validate(Task $task)
    {
        $errors = [];

        if ($task->isRecurring() && !$task->getDue()) {
            $errors[] = 'You cannot remove the due date from a recurring task.';
        }

        if ($task->isRecurring() && !$task->getRecurring()) {
            $errors[] = 'You cannot remove the recurrence from a recurring task.';
        }

        if ($task->getRecurring() && !$task->getDue()) {
            $errors[] = "A recurring task must also have a 'due' date.";
        }

        if (!preg_match(static::PATTERN, $task->getProject())) {
            $errors[] = sprintf("Project with the name '%s' is not allowed", $task->getProject());
        }

        foreach ($task->getTags() as $tag) {
            if (!preg_match(static::PATTERN, $tag)) {
                $errors[] = sprintf("Tag with the name '%s' is not allowed", $tag);
            }
        }

        return $errors;
    }

    /**
     *
     */
    public function clear()
    {
        $this->tasks = [];
    }

    /**
     * @return QueryBuilder
     */
    public function createQueryBuilder()
    {
        return new QueryBuilder($this);
    }

    /**
     * @param Report|string $report
     * @return Task[]|ArrayCollection
     * @throws Exception\ConfigException
     */
    public function filterByReport($report, $additionalFilters = null)
    {
        if (!$report instanceof Report) {
            $report = $this->taskwarrior->config()->getReport($report);
        }

        // @todo eliminate duplicate filters
        $filter = trim($report->filter . ' ' . $additionalFilters);

        return $this->createQueryBuilder()
            ->where($filter)
            ->orderBy($report->sort)
            ->getResult();
    }

    /**
     * @param Context|string $context
     * @return Task[]|ArrayCollection
     * @throws Exception\ConfigException
     */
    public function filterByContext($context)
    {
        if (!$context instanceof Report) {
            $context = $this->taskwarrior->config()->getContext($context);
        }

        return $this->createQueryBuilder()
            ->where($context->filter)
            ->getResult();
    }

    /**
     * @param string $uuid
     * @return Task
     */
    public function getReference($uuid)
    {
        if (isset($this->tasks[$uuid])) {
            return $this->tasks[$uuid];
        }

        $factory = new LazyLoadingValueHolderFactory();

        $initializer = function (
            &$wrappedObject,
            LazyLoadingInterface $proxy,
            $method
        ) use ($uuid) {
            if ('getUuid' == $method) {
                if (!$wrappedObject) {
                    $wrappedObject = new UuidContainer($uuid);
                }
            } else {
                $proxy->setProxyInitializer(null);
                $wrappedObject = $this->exportOne($uuid);
            }
        };

        $task = $factory->createProxy('DavidBadura\Taskwarrior\Task', $initializer);

        return $this->tasks[$uuid] = $task;
    }

    /**
     * @param Task $task
     */
    private function refresh(Task $task)
    {
        // skip refresh & initailize task
        if ($task instanceof LazyLoadingInterface && !$task->isProxyInitialized()) {
            return;
        }

        $clean = $this->exportOne($task->getUuid());
        $this->merge($task, $clean);
    }

    /**
     * @param string|array $filter
     * @return Task[]
     */
    private function export($filter = null)
    {
        $json = $this->taskwarrior->export($filter);

        /** @var Task[] $tasks */
        $tasks = $this->getSerializer()->deserialize($json, 'array<DavidBadura\Taskwarrior\Task>', 'json');

        foreach ($tasks as $task) {
            if (!$task->getDependencies()) {
                $task->setDependencies([]);
            }
        }

        return $tasks;
    }

    /**
     * @param string|array $filter
     * @return Task
     * @throws TaskwarriorException
     */
    private function exportOne($filter)
    {
        $tasks = $this->export($filter);

        if (count($tasks) == 0) {
            throw new TaskwarriorException('task not found');
        }

        if (count($tasks) > 1) {
            throw new TaskwarriorException('multiple task found');
        }

        return $tasks[0];
    }

    /**
     * @param Task $task
     * @throws TaskwarriorException
     */
    private function add(Task $task)
    {
        $json = $this->serializeTask($task);
        $uuid = $this->taskwarrior->import($json);

        $this->setValue($task, 'uuid', $uuid);
        $this->tasks[$uuid] = $task;
    }

    /**
     * @param Task $task
     * @throws TaskwarriorException
     */
    private function edit(Task $task)
    {
        $params = [
            'description' => $task->getDescription(),
            'project'     => $task->getProject(),
            'priority'    => $task->getPriority(),
            'tags'        => $task->getTags(),
            'due'         => $this->transformDate($task->getDue()),
            'wait'        => $this->transformDate($task->getWait()),
            'until'       => $this->transformDate($task->getUntil())
        ];

        if ($task->getRecurring()) {
            $params['recur'] = $task->getRecurring()->getValue();
        }

        $params['depends'] = [];

        foreach ($task->getDependencies() as $depend) {
            if (!$depend->getUuid()) {
                throw new ReferenceException("you can't save a task that has dependencies to tasks that have not been saved");
            }

            $params['depends'][] = $depend->getUuid();
        }

        $this->taskwarrior->modify($params, $task->getUuid());
    }

    /**
     * @param Task $old
     * @param Task $new
     */
    private function merge(Task $old, Task $new)
    {
        $this->setValue($old, 'urgency', $new->getUrgency());
        $this->setValue($old, 'status', $new->getStatus());
        $this->setValue($old, 'modified', $new->getModified());
        $this->setValue($old, 'start', $new->getStart());

        if ($new->isPending()) { // fix reopen problem
            $this->setValue($old, 'end', null);
        } else {
            $this->setValue($old, 'end', $new->getEnd());
        }
    }

    /**
     *
     * @param Task $task
     * @return string
     */
    private function serializeTask(Task $task)
    {
        $result = $this->getSerializer()->serialize($task, 'json');

        return str_replace("\\/", "/", $result);
    }

    /**
     * @param Task $task
     * @param string $attr
     * @param mixed $value
     */
    private function setValue(Task $task, $attr, $value)
    {
        $refClass = new \ReflectionClass('DavidBadura\Taskwarrior\Task');
        $refProp  = $refClass->getProperty($attr);
        $refProp->setAccessible(true);
        $refProp->setValue($task, $value);
    }

    /**
     * @param \DateTime $dateTime
     * @return null|string
     */
    private function transformDate(\DateTime $dateTime = null)
    {
        if (!$dateTime) {
            return null;
        }

        $dateTime = clone $dateTime;
        $dateTime->setTimezone(new \DateTimeZone('UTC'));

        return $dateTime->format('Ymd\THis\Z');
    }

    /**
     * @return Serializer
     */
    private function getSerializer()
    {
        if ($this->serializer) {
            return $this->serializer;
        }

        $propertyNamingStrategy = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());

        $visitor = new JsonSerializationVisitor($propertyNamingStrategy);
        $visitor->setOptions(JSON_UNESCAPED_UNICODE);

        return $this->serializer = SerializerBuilder::create()
            ->setPropertyNamingStrategy($propertyNamingStrategy)
            ->configureHandlers(function (HandlerRegistryInterface $registry) {
                $registry->registerSubscribingHandler(new CarbonHandler());
                $registry->registerSubscribingHandler(new RecurringHandler());
                $registry->registerSubscribingHandler(new DependsHandler($this));
            })
            ->addDefaultHandlers()
            ->setSerializationVisitor('json', $visitor)
            ->addDefaultDeserializationVisitors()
            ->build();
    }

    /**
     * @return self
     */
    public static function create()
    {
        return new self(new Taskwarrior());
    }
}
