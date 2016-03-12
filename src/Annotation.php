<?php

namespace DavidBadura\Taskwarrior;

use Carbon\Carbon;
use JMS\Serializer\Annotation as JMS;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Annotation
{
    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    private $description;

    /**
     * @var Carbon
     *
     * @JMS\Type("Carbon")
     */
    private $entry;

    /**
     * @param string $description
     */
    public function __construct($description = null)
    {
        $this->entry = new Carbon(); // todo https://bug.tasktools.org/browse/TW-1780
        $this->description = $description;
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

    /**
     * @return Carbon
     */
    public function getEntry()
    {
        return $this->entry;
    }
}