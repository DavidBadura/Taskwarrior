<?php

namespace DavidBadura\Taskwarrior;

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

    const PRIORITY_LOW    = 'L';
    const PRIORITY_MEDIUM = 'M';
    const PRIORITY_HIGH   = 'H';

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
     * @var string
     *
     * @JMS\Type(name="string")
     */
    private $priority;

    /**
     * @var string
     *
     * @JMS\Type(name="string")
     */
    private $project;

    /**
     * @var \DateTime
     *
     * @JMS\Type(name="DateTime<'Ymd\THis\Z'>")
     */
    private $due;

    /**
     * @var array
     *
     * @JMS\Type(name="array<string>")
     */
    private $tags;

    /**
     * @var float
     *
     * @JMS\Type(name="float")
     */
    private $urgency;

    /**
     * @var \DateTime
     *
     * @JMS\Type(name="DateTime<'Ymd\THis\Z'>")
     */
    private $entry;

    /**
     * @var string
     *
     * @JMS\Type(name="string")
     */
    private $status;

    /**
     *
     */
    public function __construct()
    {
        $this->urgency = 0;
        $this->entry   = new \DateTime('now', new \DateTimeZone('UTC'));
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
     * @param \DateTime $due
     */
    public function setDue(\DateTime $due = null)
    {
        $this->due = $due;
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
     * @return \DateTime
     */
    public function getEntry()
    {
        return $this->entry;
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
}