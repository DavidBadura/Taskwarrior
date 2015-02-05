<?php

namespace DavidBadura\Taskwarrior;

use JMS\Serializer\SerializerBuilder;
use Symfony\Component\Process\ProcessBuilder;
use Symfony\Component\Filesystem\Filesystem;


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
     * @param array  $rcOptions
     */
    public function __construct($taskrc = '~/.taskrc', $taskData = '~/.task', $rcOptions = [])
    {
        $this->rcOptions = array_merge(
            array(
                'rc:' . $taskrc,
                'rc.data.location=' . $taskData,
                'rc.json.array=false',
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
     * @param Task $task
     */
    public function delete(Task $task)
    {
        // todo
    }

    /**
     * @param Task $task
     */
    public function done(Task $task)
    {
        // todo
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
        $tasks = array();

        $json = $this->command('export', $filter);

        if (!$json) {
            return $tasks;
        }

        $jsons = explode("\n", $json);

        foreach ($jsons as $row) {
            if (trim($row) == "") {
                continue;
            }

            $serializer = SerializerBuilder::create()
                ->addDefaultHandlers()
                ->build();

            $tasks[] = $serializer->deserialize($row, 'DavidBadura\Taskwarrior\Task', 'json');
        }

        return $tasks;
    }

    /**
     * @param string $command
     * @param string $filter
     * @param array  $options
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

        $task->setUuid($uuid);
        $this->tasks[$uuid] = $task;

        $this->update($task);
    }

    /**
     * @param Task $task
     */
    private function edit(Task $task)
    {
        $modify  = Modify::createFromTask($task);
        $options = $this->modifyOptions($modify);
        $this->command('modify', $task->getUuid(), $options);
    }

    /**
     * @param Task $task
     */
    private function update(Task $task)
    {
        // todo
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
     * @param Modify $modify
     * @return array
     */
    private function modifyOptions(Modify $modify)
    {
        $array = [];

        $array[] = $modify->getDescription();

        return $array;
    }
}