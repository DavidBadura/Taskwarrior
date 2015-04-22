<?php

namespace DavidBadura\Taskwarrior;

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
     * @param array $config
     */
    public function __construct(array $config)
    {
        $this->config = $config;
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