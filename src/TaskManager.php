<?php

namespace DavidBadura\Taskwarrior;

use DavidBadura\Taskwarrior\Exception\TaskwarriorException;
use DavidBadura\Taskwarrior\Serializer\Handler\CarbonHandler;
use DavidBadura\Taskwarrior\Serializer\Handler\RecurringHandler;
use JMS\Serializer\Handler\HandlerRegistryInterface;
use JMS\Serializer\JsonSerializationVisitor;
use JMS\Serializer\Naming\CamelCaseNamingStrategy;
use JMS\Serializer\Naming\SerializedNameAnnotationStrategy;
use JMS\Serializer\Serializer;
use JMS\Serializer\SerializerBuilder;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class TaskManager
{
    /**
     * @var Taskwarrior
     */
    private $taskwarrior;

    /**
     * @var Task[]
     */
    private $tasks = [];

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

        $tasks = $this->filterAll($uuid);

        if (count($tasks) == 0) {
            return null;
        }

        if (count($tasks) == 1) {
            return $tasks[0];
        }

        throw new TaskwarriorException();
    }

    /**
     * @param string|array $filter
     * @return Task[]
     */
    public function filterAll($filter = null)
    {
        if (is_string($filter)) {
            $filter = explode(' ', $filter);
        }

        $result = $this->export($filter);

        foreach ($result as $key => $task) {
            if (isset($this->tasks[$task->getUuid()])) {

                $result[$key] = $this->tasks[$task->getUuid()];
                $this->merge($result[$key], $task);

                continue;
            }

            $this->tasks[$task->getUuid()] = $task;
        }

        return $result;
    }

    /**
     * @param string|array $filter
     * @return Task[]
     */
    public function filter($filter = null)
    {
        return $this->filterAll($filter . ' status:pending');
    }

    /**
     * @param Task $task
     */
    public function delete(Task $task)
    {
        if (!$task->getUuid()) {
            return;
        }

        if ($task->isRecurring()) {
            $task->setUntil('now');
            $this->save($task);
        } else {
            $this->taskwarrior->delete($task->getUuid());
            $this->refresh($task);
        }
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

        return $errors;
    }

    /**
     * @param Task $task
     */
    public function refresh(Task $task)
    {
        $clean = $this->export($task->getUuid())[0];
        $this->merge($task, $clean);
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
     * @param string|array $filter
     * @return Task[]
     */
    private function export($filter = null)
    {
        $this->update();
        $json = $this->taskwarrior->export($filter);

        return $this->getSerializer()->deserialize($json, 'array<DavidBadura\Taskwarrior\Task>', 'json');
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
        $this->taskwarrior->modify(
            [
                'description' => $task->getDescription(),
                'project'     => $task->getProject(),
                'priority'    => $task->getPriority(),
                'tags'        => $task->getTags(),
                'due'         => $task->getDue() ? $task->getDue()->format('Ymd\THis\Z') : null,
                'wait'        => $task->getWait() ? $task->getWait()->format('Ymd\THis\Z') : null,
                'until'       => $task->getUntil() ? $task->getUntil()->format('Ymd\THis\Z') : null,
                'recur'       => $task->getRecurring() ? $task->getRecurring()->getValue() : null,
            ],
            $task->getUuid()
        );
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

        if ($new->isPending()) { // fix reopen problem
            $this->setValue($old, 'end', null);
        } else {
            $this->setValue($old, 'end', $new->getEnd());
        }
    }

    /**
     *
     */
    private function update()
    {
        try {
            $this->taskwarrior->command('list');
        } catch (TaskwarriorException $e) {
            // do nothing
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
     * @return Serializer
     */
    private function getSerializer()
    {
        $propertyNamingStrategy = new SerializedNameAnnotationStrategy(new CamelCaseNamingStrategy());

        $visitor = new JsonSerializationVisitor($propertyNamingStrategy);
        $visitor->setOptions(JSON_UNESCAPED_UNICODE);

        return SerializerBuilder::create()
            ->setPropertyNamingStrategy($propertyNamingStrategy)
            ->configureHandlers(function (HandlerRegistryInterface $registry) {
                $registry->registerSubscribingHandler(new CarbonHandler());
                $registry->registerSubscribingHandler(new RecurringHandler());
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