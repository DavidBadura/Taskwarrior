<?php

namespace DavidBadura\Taskwarrior\Config;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Report
{
    /**
     * @var string
     */
    public $name;

    /**
     * @var string
     */
    public $description = '';

    /**
     * @var string
     */
    public $filter = '';

    /**
     * @var string[]
     */
    public $columns = [];

    /**
     * @var string[]
     */
    public $labels = [];

    /**
     * @var string[]
     */
    public $sort = [];
}