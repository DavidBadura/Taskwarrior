<?php

namespace DavidBadura\Taskwarrior;

use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Filesystem\Filesystem;
use Symfony\Component\Process\ProcessBuilder;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Taskwarrior
{
    /**
     * @var array
     */
    private $rcOptions;

    /**
     * @var Task[]
     */
    private $tasks = [];

    /**
     * @param string $taskrc
     * @param string $taskData
     * @param array $rcOptions
     */
    public function __construct($taskrc = '~/.taskrc', $taskData = '~/.task', $rcOptions = [])
    {
        $this->rcOptions = array_merge(
            array(
                'rc:' . $taskrc,
                'rc.data.location=' . $taskData,
                'rc.json.array=true',
                'rc.confirmation=no',
            ),
            $rcOptions
        );
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

        $tasks = $this->filter($uuid);

        if (count($tasks) == 0) {
            return null;
        }

        if (count($tasks) == 1) {
            return $tasks[0];
        }

        throw new TaskwarriorException();
    }

    /**
     * @param $filter
     * @return Task[]
     */
    public function filter($filter = '')
    {
        $result = $this->export($filter);

        foreach ($result as $key => $task) {
            if (isset($this->tasks[$task->getUuid()])) {

                $result[$key] = $this->tasks[$task->getUuid()];

                continue;
            }

            $this->tasks[$task->getUuid()] = $task;
        }

        return $result;
    }

    /**
     * @param string $filter
     * @return Task[]
     */
    public function filterPending($filter = '')
    {
        $tasks = $this->filter($filter . ' status:pending');

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

        $this->command('delete', $task->getUuid());
        $this->update($task);
    }

    /**
     * @param Task $task
     */
    public function done(Task $task)
    {
        if (!$task->getUuid()) {
            return;
        }

        $this->command('done', $task->getUuid());
        $this->update($task);
    }

    /**
     *
     */
    public function clear()
    {
        $this->tasks = [];
    }

    /**
     * @param $json
     * @return string
     * @throws TaskwarriorException
     */
    private function import($json)
    {
        $fs = new Filesystem();

        $file = tempnam(sys_get_temp_dir(), 'task') . '.json';
        $fs->dumpFile($file, $json);

        $output = $this->command('import', $file);

        if (!preg_match('/([0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12})/', $output, $matches)) {
            throw new TaskwarriorException();
        }

        return $matches[1];
    }

    /**
     * @param string $filter
     * @return Task[]
     */
    private function export($filter = '')
    {
        $json = $this->command('export', $filter);

        $serializer = SerializerBuilder::create()
            ->addDefaultHandlers()
            ->build();

        return $serializer->deserialize($json, 'array<DavidBadura\Taskwarrior\Task>', 'json');
    }

    /**
     * @param string $command
     * @param string $filter
     * @param array $options
     * @return string
     * @throws TaskwarriorException
     */
    private function command($command, $filter = null, $options = array())
    {
        $builder = $this->createProcessBuilder();

        if ($filter) {
            $builder->add($filter);
        }

        $builder->add($command);

        foreach ($options as $param) {
            $builder->add($param);
        }

        $process = $builder->getProcess();
        $process->run();

        if (!$process->isSuccessful()) {
            throw new TaskwarriorException(
                $process->getErrorOutput(),
                $process->getExitCode(),
                $process->getCommandLine()
            );
        }

        return $process->getOutput();
    }

    /**
     * @param Task $task
     * @throws TaskwarriorException
     */
    private function add(Task $task)
    {
        $json = $this->serializeTask($task);
        $uuid = $this->import($json);

        $this->setValue($task, 'uuid', $uuid);
        $this->tasks[$uuid] = $task;

        $this->update($task);
    }

    /**
     * @param Task $task
     */
    private function edit(Task $task)
    {
        $options = [];

        if ($task->getDue()) {
            $options[] = 'due:' . $task->getDue()->format('Ymd\THis\Z');
        } else {
            $options[] = 'due:';
        }

        $options[] = $task->getDescription();

        $this->command('modify', $task->getUuid(), $options);
        $this->update($task);
    }

    /**
     * @param Task $task
     */
    private function update(Task $task)
    {
        $clean = $this->export($task->getUuid())[0];

        $this->setValue($task, 'urgency', $clean->getUrgency());
        $this->setValue($task, 'status', $clean->getStatus());
    }

    /**
     * @param Task[] $tasks
     * @return Task[]
     */
    private function sort(array $tasks)
    {
        usort($tasks, function (Task $a, Task $b) {
            if(0 != $diff = $b->getUrgency() - $a->getUrgency()) {
                return $diff;
            }

            return $a->getEntry() >= $b->getEntry() ? 1 : -1;
        });

        return $tasks;
    }

    /**
     * @return ProcessBuilder
     */
    private function createProcessBuilder()
    {
        $builder = new ProcessBuilder();

        foreach ($this->rcOptions as $option) {
            $builder->add($option);
        }

        $builder->setPrefix('task');
        $builder->setTimeout(360);

        return $builder;
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
}