<?php
/**
 * (c) SimpleThings GmbH
 */

namespace DavidBadura\Taskwarrior;

/**
 * @author David Badura <badura@simplethings.de>
 */
class Modify
{
    /**
     * @var string
     */
    private $description;

    /**
     * @return string
     */
    public function getDescription()
    {
        return $this->description;
    }

    /**
     * @param string $description
     */
    public function setDescription($description)
    {
        $this->description = $description;
    }

    /**
     * @param Task $task
     * @return Modify
     */
    public static function createFromTask(Task $task)
    {
        $modify = new self();
        $modify->setDescription($task->getDescription());

        return $modify;
    }
}