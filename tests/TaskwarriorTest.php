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

        $result = $this->taskwarrior->filterAll($task->getUuid());

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

    public function testPending()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->taskwarrior->save($task1);

        $this->assertEquals(Task::STATUS_PENDING, $task1->getStatus());
        $this->assertTrue($task1->isPending());

        $this->taskwarrior->clear();
        $result = $this->taskwarrior->find($task1->getUuid());

        $this->assertEquals(Task::STATUS_PENDING, $result->getStatus());
        $this->assertTrue($result->isPending());

        $this->assertCount(1, $this->taskwarrior->filter());
    }

    public function testDelete()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->assertCount(0, $this->taskwarrior->filter());

        $this->taskwarrior->save($task1);
        $this->assertCount(1, $this->taskwarrior->filterAll());
        $this->assertCount(1, $this->taskwarrior->filter());
        $this->assertFalse($task1->isDeleted());
        $this->assertEquals(Task::STATUS_PENDING, $task1->getStatus());

        $this->taskwarrior->delete($task1);
        $this->assertCount(1, $this->taskwarrior->filterAll());
        $this->assertCount(0, $this->taskwarrior->filter());
        $this->assertTrue($task1->isDeleted());
        $this->assertEquals(Task::STATUS_DELETED, $task1->getStatus());
    }

    public function testCompleted()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->assertCount(0, $this->taskwarrior->filter());

        $this->taskwarrior->save($task1);
        $this->assertCount(1, $this->taskwarrior->filterAll());
        $this->assertCount(1, $this->taskwarrior->filter());
        $this->assertFalse($task1->isCompleted());
        $this->assertEquals(Task::STATUS_PENDING, $task1->getStatus());

        $this->taskwarrior->done($task1);
        $this->assertCount(1, $this->taskwarrior->filterAll());
        $this->assertCount(0, $this->taskwarrior->filter());
        $this->assertTrue($task1->isCompleted());
        $this->assertEquals(Task::STATUS_COMPLETED, $task1->getStatus());
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

    public function testDue()
    {
        $date = $this->createDateTime('1989-01-08 11:12:13');

        $task1 = new Task();
        $task1->setDescription('foo1');
        $task1->setDue($date);

        $this->taskwarrior->save($task1);
        $this->taskwarrior->clear();

        $task2 = $this->taskwarrior->find($task1->getUuid());
        $this->assertEquals($date, $task2->getDue());

        $newDate = $this->createDateTime('2002-02-20 11:12:13');

        $task2->setDue($newDate);

        $this->taskwarrior->save($task2);
        $this->taskwarrior->clear();

        $task3 = $this->taskwarrior->find($task1->getUuid());
        $this->assertEquals($newDate, $task3->getDue());

        $task2->setDue(null);

        $this->taskwarrior->save($task2);
        $this->taskwarrior->clear();

        $task3 = $this->taskwarrior->find($task1->getUuid());
        $this->assertNull($task3->getDue());
    }

    public function testUrgency()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');
        $task1->setDue($this->createDateTime('1989-01-08 11:12:13'));

        $this->taskwarrior->save($task1);

        $this->assertEquals(12, $task1->getUrgency());

        $task1->setDue(null);

        $this->taskwarrior->save($task1);

        $this->assertEquals(0, $task1->getUrgency());
    }

    public function testUrgencySort()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $task2 = new Task();
        $task2->setDescription('foo2');
        $task2->setDue($this->createDateTime('1989-01-08 11:12:13'));

        $task3 = new Task();
        $task3->setDescription('foo3');

        $this->taskwarrior->save($task1);
        $this->taskwarrior->save($task2);
        $this->taskwarrior->save($task3);

        $this->assertEquals(0, $task1->getUrgency());
        $this->assertEquals(12, $task2->getUrgency());
        $this->assertEquals(0, $task3->getUrgency());

        $tasks = $this->taskwarrior->filter();

        $this->assertEquals(array($task2, $task1, $task3), $tasks);
    }

    /**
     * @param string $string
     * @return \DateTime
     */
    private function createDateTime($string = 'now')
    {
        return new \DateTime($string, new \DateTimeZone('UTC'));
    }
}