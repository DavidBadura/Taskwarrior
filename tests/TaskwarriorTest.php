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

        $this->assertInstanceOf('DavidBadura\Taskwarrior\Config\Config', $config);
        $this->assertTrue($config->has('urgency.age.max'));
        $this->assertEquals('365', $config->get('urgency.age.max'));
    }

    public function testTaskrcNotFound()
    {
        $this->setExpectedException('DavidBadura\Taskwarrior\Exception\TaskwarriorException');

        new Taskwarrior('/not/found/.taskrc', __DIR__ . '/.task');
    }

    public function testTaskDataNotFound()
    {
        $this->setExpectedException('DavidBadura\Taskwarrior\Exception\TaskwarriorException');

        new Taskwarrior(__DIR__ . '/.taskrc', '/not/found/.task');
    }
}