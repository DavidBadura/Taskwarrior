<?php

namespace DavidBadura\Taskwarrior;

use Exception;

/**
 * @author David Badura <badura@simplethings.de>
 */
class TaskwarriorException extends \Exception
{
    /**
     * @var string
     */
    private $command;

    /**
     * @param string    $message
     * @param int       $code
     * @param string    $command
     */
    public function __construct($message = "", $code = 0, $command = '')
    {
        parent::__construct($message, $code, null);
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }
}