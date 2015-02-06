<?php

namespace DavidBadura\Taskwarrior;

use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;

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
     */
    public function save(Task $task)
    {
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
        $tasks = $this->filterAll($filter . ' status:pending');

        return $this->sort($tasks);
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
     * @param string|array $filter
     * @return Task[]
     */
    private function export($filter = null)
    {
        $json = $this->taskwarrior->export($filter);

        $serializer = SerializerBuilder::create()
            ->addDefaultHandlers()
            ->build();

        return $serializer->deserialize($json, 'array<DavidBadura\Taskwarrior\Task>', 'json');
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
        $this->setValue($old, 'end', $new->getEnd());
    }

    /**
     * @param Task[] $tasks
     * @return Task[]
     */
    private function sort(array $tasks)
    {
        usort(
            $tasks,
            function (Task $a, Task $b) {
                if (0 != $diff = $b->getUrgency() - $a->getUrgency()) {
                    return $diff;
                }

                return $a->getEntry() >= $b->getEntry() ? 1 : -1;
            }
        );

        return $tasks;
    }

    /**
     *
     * @param Task $task
     * @return string
     */
    private function serializeTask(Task $task)
    {
        $serializer = SerializerBuilder::create()
            ->addDefaultHandlers()
            ->build();

        $result = $serializer->serialize($task, 'json');

        return str_replace("\\/", "/", $result);
    }

    /**
     * @param Task $task
     * @param string $attr
     * @param mixed $value
     */
    private function setValue(Task $task, $attr, $value)
    {
        $reflectionClass = new \ReflectionClass('DavidBadura\Taskwarrior\Task');
        $prop            = $reflectionClass->getProperty($attr);
        $prop->setAccessible(true);
        $prop->setValue($task, $value);
    }

    /**
     * @return self
     */
    public static function create()
    {
        return new self(new Taskwarrior());
    }
}