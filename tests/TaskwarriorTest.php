<?php

namespace DavidBadura\Taskwarrior\Test;

use DavidBadura\Taskwarrior\Task;
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
        $this->taskwarrior = new Taskwarrior(__DIR__ . '/.taskrc', __DIR__ . '/.task');
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/.taskrc');
        $fs->remove(__DIR__ . '/.task');
    }

    public function testEmpty()
    {
        $tasks = $this->taskwarrior->filter();
        $this->assertEmpty($tasks);
    }

    public function testSaveTask()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskwarrior->save($task);
        $this->taskwarrior->clear();

        $this->assertNotEmpty($task->getUuid());

        $result = $this->taskwarrior->find($task->getUuid());

        $this->assertEquals($task, $result);
    }

    public function testFindFromCache()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskwarrior->save($task);

        $result = $this->taskwarrior->find($task->getUuid());

        $this->assertSame($task, $result);
    }

    public function testFilterFromCache()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskwarrior->save($task);

        $result = $this->taskwarrior->filter($task->getUuid());

        $this->assertSame($task, $result[0]);
    }

    public function testDontFind()
    {
        $task = $this->taskwarrior->find('56464asd46s4adas54da6');
        $this->assertNull($task);
    }

    public function testDoubleSave()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskwarrior->save($task);

        $this->assertNotEmpty($task->getUuid());
        $uuid = $task->getUuid();

        $this->taskwarrior->save($task);

        $this->assertEquals($uuid, $task->getUuid());
        $this->assertCount(1, $this->taskwarrior->filter());

        $this->taskwarrior->clear();

        $this->taskwarrior->save($task);

        $this->assertEquals($uuid, $task->getUuid());
        $this->assertCount(1, $this->taskwarrior->filter());
    }

    public function testFilterAll()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $task2 = new Task();
        $task2->setDescription('foo2');

        $this->taskwarrior->save($task1);
        $this->taskwarrior->save($task2);

        $this->assertCount(2, $this->taskwarrior->filter());
    }

    public function testModifyDescription()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->taskwarrior->save($task1);

        $this->assertEquals('foo1', $task1->getDescription());

        $task1->setDescription('bar1');
        $this->taskwarrior->save($task1);

        $this->taskwarrior->clear();

        $result = $this->taskwarrior->find($task1->getUuid());

        $this->assertEquals('bar1', $result->getDescription());
    }
}