<?php

namespace DavidBadura\Taskwarrior\Test;

use DavidBadura\Taskwarrior\Taskwarrior;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author David Badura <badura@simplethings.de>
 */
class TaskwarriorTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Taskwarrior
     */
    protected $taskwarrior;

    public function setUp()
    {
        $this->tearDown();
        $this->taskwarrior = new Taskwarrior(__DIR__ . '/.taskrc', __DIR__ . '/.task');
        $this->taskwarrior->version(); // to initialise
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/.taskrc');
        $fs->remove(__DIR__ . '/.task');
    }


    public function testConfig()
    {
        $config = $this->taskwarrior->config();

        $this->assertInstanceOf('DavidBadura\Taskwarrior\Config', $config);
        $this->assertTrue($config->has('alias._query'));
        $this->assertEquals('export', $config->get('alias._query'));
    }
}