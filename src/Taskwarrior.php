<?php

namespace DavidBadura\Taskwarrior;

use DavidBadura\Taskwarrior\Config\Config;
use DavidBadura\Taskwarrior\Exception\CommandException;
use DavidBadura\Taskwarrior\Exception\TaskwarriorException;
use Symfony\Component\Process\Process;
use Webmozart\Assert\Assert;
use Webmozart\PathUtil\Path;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Taskwarrior
{
    /**
     * @var string
     */
    private $bin;

    /**
     * @var string
     */
    private $taskrc;

    /**
     * @var string
     */
    private $taskData;

    /**
     * @var array
     */
    private $rcOptions;

    /**
     * @var string
     */
    private $version;

    /**
     * @var Config
     */
    private $config;

    /**
     * @param string $taskrc
     * @param string $taskData
     * @param array $rcOptions
     * @param string $bin
     * @throws TaskwarriorException
     */
    public function __construct($taskrc = '~/.taskrc', $taskData = '~/.task', $rcOptions = [], $bin = 'task')
    {
        $this->bin      = Path::canonicalize($bin);
        $this->taskrc   = Path::canonicalize($taskrc);
        $this->taskData = Path::canonicalize($taskData);

        $this->rcOptions = array_merge(
            array(
                'rc:' . $this->taskrc,
                'rc.data.location=' . $this->taskData,
                'rc.json.array=true',
                'rc.confirmation=no',
            ),
            $rcOptions
        );

        if (version_compare($this->version(), '2.5.0') < 0) {
            throw new TaskwarriorException(sprintf("Taskwarrior version %s isn't supported", $this->version()));
        }

        try {
            Assert::readable($this->taskrc);
            Assert::readable($this->taskData);
            Assert::writable($this->taskData);
        } catch (\InvalidArgumentException $e) {
            throw new TaskwarriorException($e->getMessage(), $e->getCode(), $e);
        }
    }

    /**
     * @param string|string[] $filter
     */
    public function delete($filter)
    {
        $this->command('delete', $filter);
    }

    /**
     * @param string|string[] $filter
     */
    public function done($filter)
    {
        $this->command('done', $filter);
    }

    /**
     * @param string|string[] $filter
     */
    public function start($filter)
    {
        $this->command('start', $filter);
    }

    /**
     * @param string|string[] $filter
     */
    public function stop($filter)
    {
        $this->command('stop', $filter);
    }

    /**
     * @param array $params
     */
    public function add(array $params)
    {
        $this->command('add', null, $this->getOptions($params));
    }

    /**
     * @param array $params
     * @param string|string[] $filter
     */
    public function modify(array $params, $filter = null)
    {
        $this->command('modify', $filter, $this->getOptions($params));
    }

    /**
     * @param string|string[] $filter
     * @return array
     */
    public function projects($filter = null)
    {
        $result = $this->command('_project', $filter);

        return $this->parseResult($result);
    }

    /**
     * @param string|string[] $filter
     * @return array
     */
    public function tags($filter = null)
    {
        $result = $this->command('_tags', $filter);

        $tags = $this->parseResult($result);

        return array_values(array_filter($tags, function ($value) {
            return !in_array($value, [
                "ACTIVE",
                "ANNOTATED",
                "BLOCKED",
                "BLOCKING",
                "CHILD",
                "COMPLETED",
                "DELETED",
                "DUE",
                "DUETODAY",
                "MONTH",
                "ORPHAN",
                "OVERDUE",
                "PARENT",
                "PENDING",
                "READY",
                "SCHEDULED",
                "TAGGED",
                "TODAY",
                "TOMORROW",
                "UDA",
                "UNBLOCKED",
                "UNTIL",
                "WAITING",
                "WEEK",
                "YEAR",
                "YESTERDAY",
                "next",
                "nocal",
                "nocolor",
                "nonag"
            ]);
        }));
    }

    /**
     * @param string $json
     * @return string
     * @throws CommandException
     * @throws TaskwarriorException
     */
    public function import($json)
    {
        $output = $this->command('import', null, ['-'], $json);

        if ($uuid = self::parseUuid($output)) {
            return $uuid;
        }

        throw new TaskwarriorException();
    }

    /**
     * @param string|string[] $filter
     * @return string
     */
    public function export($filter = null)
    {
        return $this->command('export', $filter);
    }

    /**
     * @param string $command
     * @param string|string[] $filter
     * @param array $options
     * @param string $input
     * @return string
     * @throws CommandException
     */
    public function command($command, $filter = null, array $options = array(), $input = null)
    {
        $parts = [$this->bin];

        foreach ($this->rcOptions as $option) {
            $parts[] = $option;
        }

        $filter = array_filter((array)$filter, 'trim');

        if ($filter) {
            foreach ($filter as $f) {
                $parts[] = "( " . $f . " )";
            }
        }

        $parts[] = $command;

        foreach ($options as $param) {
            $parts[] = $param;
        }

        $process = new Process($this->createCommandLine($parts));

        if ($input) {
            $process->setInput($input);
        }

        $process->run();

        if (!$process->isSuccessful()) {
            throw new CommandException($process);
        }

        return $process->getOutput();
    }

    /**
     * @return string
     */
    public function version()
    {
        if (!$this->version) {
            $this->version = trim($this->command('_version'));
        }

        return $this->version;
    }

    /**
     * @return string
     */
    public function getTaskrcPath()
    {
        return $this->taskrc;
    }

    /**
     * @return string
     */
    public function getTaskDataPath()
    {
        return $this->taskData;
    }

    /**
     * @return Config
     * @throws CommandException
     */
    public function config()
    {
        if (!$this->config) {
            $this->config = Config::create($this->command('_show'));
        }

        return $this->config;
    }

    /**
     * @param $params
     * @return array
     */
    private function getOptions($params)
    {
        $options = [];

        if (array_key_exists('due', $params)) {
            $options[] = 'due:' . $params['due'];
        }

        if (array_key_exists('wait', $params)) {
            $options[] = 'wait:' . $params['wait'];
        }

        if (array_key_exists('until', $params)) {
            $options[] = 'until:' . $params['until'];
        }

        if (array_key_exists('recur', $params)) {
            $options[] = 'recur:' . $params['recur'];
        }

        if (array_key_exists('project', $params)) {
            $options[] = 'project:' . $params['project'];
        }

        if (array_key_exists('priority', $params)) {
            $options[] = 'priority:' . $params['priority'];
        }

        if (array_key_exists('tags', $params)) {
            if (is_array($params['tags'])) {
                $options[] = 'tags:' . implode(',', $params['tags']);
            } else {
                $options[] = 'tags:' . $params['tags'];
            }
        }

        if (array_key_exists('depends', $params)) {
            if (is_array($params['depends'])) {
                $options[] = 'depends:' . implode(',', $params['depends']);
            } else {
                $options[] = 'depends:' . $params['depends'];
            }
        }

        if (array_key_exists('status', $params)) {
            $options[] = 'status:' . $params['status'];
        }

        if (array_key_exists('description', $params)) {
            $options[] = 'description:' . $params['description'];
        }

        return $options;
    }

    /**
     * @param string $string
     * @return array
     */
    private function parseResult($string)
    {
        return array_filter(explode("\n", $string), 'strlen');
    }

    /**
     * @param array $parts
     * @return string
     */
    private function createCommandLine(array $parts)
    {
        $parts = array_map(function ($part) {
            return "'" . str_replace("'", "'\\''", $part) . "'";
        }, $parts);

        return implode(' ', $parts);
    }

    /**
     * @param string $string
     * @return string|null
     */
    public static function parseUuid($string)
    {
        if (preg_match('/([0-9a-f]{8}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{4}\-[0-9a-f]{12})/', $string, $matches)) {
            return $matches[1];
        }

        return null;
    }
}
