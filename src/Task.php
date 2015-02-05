<?php

namespace DavidBadura\Taskwarrior;

use JMS\Serializer\Annotation as JMS;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Task
{
    const STATUS_PENDING = 'pending';
    const STATUS_COMPLETED = 'completed';
    const STATUS_DELETED = 'deleted';
    const STATUS_WAITING = 'waiting';

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
    private $status;

    /**
     *
     */
    public function __construct()
    {
        $this->status = self::STATUS_PENDING;
    }

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

    /**
     * @return string
     */
    public function getStatus()
    {
        return $this->status;
    }

    /**
     * @param string $status
     */
    public function setStatus($status)
    {
        $this->status = $status;
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