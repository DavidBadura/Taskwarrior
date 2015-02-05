<?php

namespace DavidBadura\Taskwarrior;

use JMS\Serializer\Annotation as JMS;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Task
{
    /**
     * @var string
     *
     * @JMS\Type(name="string")
     */
    private $uuid;

    /**
     * @var string
     *
     * @JMS\Type(name="string")
     */
    private $description;

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }

    /**
     * @param string $uuid
     */
    public function setUuid($uuid)
    {
        $this->uuid = $uuid;
    }

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
}