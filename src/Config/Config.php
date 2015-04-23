<?php

namespace DavidBadura\Taskwarrior\Config;

use DavidBadura\Taskwarrior\Exception\ConfigException;
use Doctrine\Common\Collections\Criteria;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Config implements \IteratorAggregate, \Countable
{
    /**
     * @var array
     */
    private $config;

    /**
     * @var Uda[]
     */
    private $udas = [];

    /**
     * @var Context[]
     */
    private $contexts = [];

    /**
     * @var Report[]
     */
    private $reports = [];

    /**
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;

        $this->initContexts();
        $this->initReports();
        $this->initUdas();
    }

    /**
     * @param string $path
     * @param mixed $default
     * @return array
     */
    public function get($path, $default = null)
    {
        return array_key_exists($path, $this->config) ? $this->config[$path] : $default;
    }

    /**
     * @param string $key
     * @return bool
     */
    public function has($key)
    {
        return array_key_exists($key, $this->config);
    }

    /**
     * @return array
     */
    public function keys()
    {
        return array_keys($this->config);
    }

    /**
     * @return array
     */
    public function all()
    {
        return $this->config;
    }

    /**
     * @return \ArrayIterator
     */
    public function getIterator()
    {
        return new \ArrayIterator($this->config);
    }

    /**
     * @return int
     */
    public function count()
    {
        return count($this->config);
    }

    /**
     * @param string $name
     * @return Context
     * @throws ConfigException
     */
    public function getContext($name)
    {
        if (!$this->hasContext($name)) {
            throw new ConfigException();
        }

        return $this->contexts[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasContext($name)
    {
        return isset($this->contexts[$name]);
    }

    /**
     * @return Context[]
     */
    public function getContexts()
    {
        return $this->contexts;
    }

    /**
     * @param string $name
     * @return Report
     * @throws ConfigException
     */
    public function getReport($name)
    {
        if (!$this->hasReport($name)) {
            throw new ConfigException();
        }

        return $this->reports[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasReport($name)
    {
        return isset($this->reports[$name]);
    }

    /**
     * @return Report[]
     */
    public function getReports()
    {
        return $this->reports;
    }

    /**
     * @param string $name
     * @return Uda
     * @throws ConfigException
     */
    public function getUda($name)
    {
        if (!$this->hasUda($name)) {
            throw new ConfigException();
        }

        return $this->udas[$name];
    }

    /**
     * @param string $name
     * @return bool
     */
    public function hasUda($name)
    {
        return isset($this->udas[$name]);
    }

    /**
     * @return Uda[]
     */
    public function getUdas()
    {
        return $this->udas;
    }

    /**
     *
     */
    private function initContexts()
    {
        foreach ($this->config as $key => $value) {
            if (!preg_match('/context\.(\w+)/', $key, $matches)) {
                continue;
            }

            $context         = new Context();
            $context->name   = $matches[1];
            $context->filter = $value;

            $this->contexts[$context->name] = $context;
        }
    }

    /**
     *
     */
    private function initReports()
    {
        foreach ($this->config as $key => $value) {
            if (!preg_match('/report\.(\w+)\.(\w+)/', $key, $matches)) {
                continue;
            }

            $name = $matches[1];
            $attr = $matches[2];

            if (!isset($this->reports[$name])) {
                $this->reports[$name]       = new Report();
                $this->reports[$name]->name = $name;
            }

            $report = $this->reports[$name];

            switch ($attr) {
                case 'description':
                case 'filter':
                    $report->$attr = $value;
                    break;
                case 'columns':
                case 'labels':
                    $report->$attr = explode(',', $value);
                    break;
                case 'sort':
                    $report->$attr = $this->parseOrder($value);
                    break;
            }
        }
    }

    /**
     *
     */
    private function initUdas()
    {
        foreach ($this->config as $key => $value) {
            if (!preg_match('/uda\.(\w+)\.(\w+)/', $key, $matches)) {
                continue;
            }

            $name = $matches[1];
            $attr = $matches[2];

            if (!isset($this->udas[$name])) {
                $this->udas[$name]       = new Uda();
                $this->udas[$name]->name = $name;
            }

            $uda = $this->udas[$name];

            switch ($attr) {
                case 'label':
                case 'type':
                    $uda->$attr = $value;
                    break;
                case 'values':
                    $uda->$attr = explode(',', $value);
                    break;
            }
        }
    }

    /**
     * @param string $string
     * @return array
     */
    private function parseOrder($string)
    {
        $parts = explode(',', $string);
        $order = [];

        foreach ($parts as $part) {
            $part = trim($part);

            if (!$part) {
                continue;
            }

            str_replace('/', '', $part); // fix "report.minimal.sort=project+/,description+"

            $order[substr($part, 0, -1)] = substr($part, -1) == '+' ? Criteria::ASC : Criteria::DESC;
        }

        return $order;
    }

    /**
     * @param string $string
     * @return array
     */
    public static function parse($string)
    {
        $config = [];
        $lines  = explode("\n", $string);

        foreach ($lines as $line) {

            if (!trim($line)) {
                continue;
            }

            list($key, $value) = explode('=', $line);

            if ($value == 'no' || $value == 'off') {
                $value = false;
            } elseif ($value == 'yes' || $value == 'on') {
                $value = true;
            }

            $config[$key] = $value;
        }

        return $config;
    }

    /**
     * @param string $string
     * @return self
     */
    public static function create($string)
    {
        return new self(self::parse(($string)));
    }
}