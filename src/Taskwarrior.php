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
                'rc.json.array=true',
                'rc.confirmation=no',
            ),
            $rcOptions
        );
    }

    /**
     * @param string $uuid
     */
    public function delete($uuid)
    {
        $this->command('delete', $uuid);
    }

    /**
     * @param string $uuid
     */
    public function done($uuid)
    {
        $this->command('done', $uuid);
    }

    /**
     * @param $json
     * @return string
     * @throws TaskwarriorException
     */
    public function import($json)
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
     * @param string|array $filter
     * @return string
     */
    public function export($filter = null)
    {
        return $this->command('export', $filter);
    }

    /**
     * @param string       $command
     * @param string|array $filter
     * @param array        $options
     * @return string
     * @throws TaskwarriorException
     */
    public function command($command, $filter = null, array $options = array())
    {
        $builder = $this->createProcessBuilder();

        if (!is_array($filter)) {
            $filter = [$filter];
        }

        foreach ($filter as $param) {
            if (empty($param)) {
                continue;
            }

            $builder->add($param);
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
}