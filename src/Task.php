<?php

namespace DavidBadura\Taskwarrior;

use Carbon\Carbon;
use JMS\Serializer\Annotation as JMS;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Task
{
    const STATUS_PENDING   = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DELETED   = 'deleted';
    const STATUS_WAITING   = 'waiting';
    const STATUS_RECURRING = 'recurring';

    const PRIORITY_LOW    = 'L';
    const PRIORITY_MEDIUM = 'M';
    const PRIORITY_HIGH   = 'H';

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    private $uuid;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    private $description;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    private $priority;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    private $project;

    /**
     * @var Carbon
     *
     * @JMS\Type("Carbon")
     */
    private $due;

    /**
     * @var Carbon
     *
     * @JMS\Type("Carbon")
     */
    private $wait;

    /**
     * @var array
     *
     * @JMS\Type("array<string>")
     */
    private $tags;

    /**
     * @var float
     *
     * @JMS\Type("float")
     */
    private $urgency;

    /**
     * @var Carbon
     *
     * @JMS\Type("Carbon")
     */
    private $entry;

    /**
     * @var string
     *
     * @JMS\Type("Recurring")
     */
    private $recur;

    /**
     * @var Carbon
     *
     * @JMS\Type("Carbon")
     */
    private $until;

    /**
     * @var Carbon
     *
     * @JMS\Type("Carbon")
     */
    private $modified;

    /**
     * @var Carbon
     *
     * @JMS\Type("Carbon")
     */
    private $end;

    /**
     * @var string
     *
     * @JMS\Type("string")
     */
    private $status;

    /**
     *
     */
    public function __construct()
    {
        $this->urgency = 0;
        $this->entry   = new Carbon('now', new \DateTimeZone('UTC'));
        $this->status  = self::STATUS_PENDING;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
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
     * @return string
     */
    public function getPriority()
    {
        return $this->priority;
    }

    /**
     * @param string $priority
     */
    public function setPriority($priority)
    {
        $this->priority = $priority;
    }

    /**
     * @return string
     */
    public function getProject()
    {
        return $this->project;
    }

    /**
     * @param string $project
     */
    public function setProject($project)
    {
        $this->project = $project;
    }

    /**
     * @return \DateTime
     */
    public function getDue()
    {
        return $this->due;
    }

    /**
     * @param \DateTime|string $due
     */
    public function setDue($due = null)
    {
        $this->due = $this->parseDateTime($due);
    }

    /**
     * @return \DateTime
     */
    public function getWait()
    {
        return $this->wait;
    }

    /**
     * @param \DateTime|string $wait
     */
    public function setWait($wait = null)
    {
        $this->wait = $this->parseDateTime($wait);
    }

    /**
     * @return array
     */
    public function getTags()
    {
        return (array)$this->tags;
    }

    /**
     * @param array $tags
     */
    public function setTags(array $tags = array())
    {
        $this->tags = $tags;
    }

    /**
     * @param string $tag
     */
    public function addTag($tag)
    {
        if (!$this->tags) {
            $this->tags = [$tag];
        }

        if (!in_array($tag, $this->tags)) {
            $this->tags[] = $tag;
        }
    }

    /**
     * @param string $tag
     */
    public function removeTag($tag)
    {
        if (!$this->tags) {
            return;
        }

        if (false !== $key = array_search($tag, $this->tags)) {
            unset($this->tags[$key]);
        }
    }

    /**
     * @return string
     */
    public function getRecur()
    {
        return $this->recur;
    }

    /**
     * @param string|Recurring $recur
     * @throws TaskwarriorException
     */
    public function setRecur($recur)
    {
        if (is_string($recur)) {
            $this->recur = new Recurring($recur);
        } elseif ($recur instanceof Recurring) {
            $this->recur = $recur;
        } else {
            throw new TaskwarriorException();

        }
    }

    /**
     * @return \DateTime
     */
    public function getUntil()
    {
        return $this->until;
    }

    /**
     * @param \DateTime|string $until
     */
    public function setUntil($until = null)
    {
        $this->until = $this->parseDateTime($until);
    }

    /**
     * @return \DateTime
     */
    public function getEntry()
    {
        return $this->entry;
    }

    /**
     * @return \DateTime
     */
    public function getModified()
    {
        return $this->modified;
    }

    /**
     * @return \DateTime
     */
    public function getEnd()
    {
        return $this->end;
    }

    /**
     * @return float
     */
    public function getUrgency()
    {
        return $this->urgency;
    }

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @return bool
     */
    public function isPending()
    {
        return $this->status == self::STATUS_PENDING;
    }

    /**
     * @return bool
     */
    public function isCompleted()
    {
        return $this->status == self::STATUS_COMPLETED;
    }

    /**
     * @return bool
     */
    public function isWaiting()
    {
        return $this->status == self::STATUS_WAITING;
    }

    /**
     * @return bool
     */
    public function isDeleted()
    {
        return $this->status == self::STATUS_DELETED;
    }

    /**
     * @return bool
     */
    public function isReccuring()
    {
        return $this->status == self::STATUS_RECURRING;
    }

    /**
     * @param string|\DateTime|null $date
     * @return \DateTime|null
     * @throws TaskwarriorException
     */
    private function parseDateTime($date)
    {
        if ($date instanceof \DateTime) {
            return new Carbon($date->format('Y-m-d H:i:s'), new \DateTimeZone('UTC'));
        }

        if ($date instanceof Carbon) {
            $date = clone $date;
            $date->setTimezone(new \DateTimeZone('UTC'));

            return $date;
        }

        if (is_string($date)) {
            return new Carbon($date, new \DateTimeZone('UTC'));
        }

        if ($date === null) {
            return null;
        }

        throw new TaskwarriorException();
    }

    /**
     *
     */
    public function __clone()
    {
        $this->uuid   = null;
        $this->entry  = new Carbon('now', new \DateTimeZone('UTC'));
        $this->status = self::STATUS_PENDING;
    }
}