<?php

namespace DavidBadura\Taskwarrior\Proxy;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class UuidContainer
{
    /**
     * @var string
     */
    protected $uuid;

    /**
     * @param string $uuid
     */
    public function __construct($uuid)
    {
        $this->uuid = $uuid;
    }

    /**
     * @return string
     */
    public function getUuid()
    {
        return $this->uuid;
    }
}