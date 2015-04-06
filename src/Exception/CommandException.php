<?php

namespace DavidBadura\Taskwarrior\Exception;

use Symfony\Component\Process\Process;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class CommandException extends TaskwarriorException
{
    /**
     * @var string
     */
    private $command;

    /**
     * @var int
     */
    private $exitCode;

    /**
     * @var string
     */
    private $output;

    /**
     * @var string
     */
    private $errorOutput;

    /**
     * @param Process $process
     */
    public function __construct(Process $process)
    {
        $this->command     = $process->getCommandLine();
        $this->exitCode    = $process->getExitCode();
        $this->output      = $process->getOutput();
        $this->errorOutput = $process->getErrorOutput();

        if (!$message = $this->getCleanErrorOutput()) {
            $message = $this->output;
        }

        parent::__construct($message, $this->getExitCode());
    }

    /**
     * @return string
     */
    public function getCommand()
    {
        return $this->command;
    }

    /**
     * @return int
     */
    public function getExitCode()
    {
        return $this->exitCode;
    }

    /**
     * @return string
     */
    public function getOutput()
    {
        return $this->output;
    }

    /**
     * @return string
     */
    public function getErrorOutput()
    {
        return $this->errorOutput;
    }

    /**
     * @return string
     */
    public function getCleanErrorOutput()
    {
        $message = '';

        foreach (explode("\n", $this->errorOutput) as $line) {
            if (strpos($line, 'Using alternate') === 0 || strpos($line, 'Configuration override') === 0) {
                continue;
            }

            $message .= $line . "\n";
        }

        return trim($message);
    }
}