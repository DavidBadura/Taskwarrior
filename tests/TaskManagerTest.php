<?php

namespace DavidBadura\Taskwarrior\Test;

use DavidBadura\Taskwarrior\Task;
use DavidBadura\Taskwarrior\TaskManager;
use DavidBadura\Taskwarrior\Taskwarrior;
use Symfony\Component\Filesystem\Filesystem;

/**
 * @author David Badura <badura@simplethings.de>
 */
class TaskManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var Taskwarrior
     */
    protected $taskwarrior;

    /**
     * @var TaskManager
     */
    protected $taskManager;

    public function setUp()
    {
        $this->taskwarrior = new Taskwarrior(__DIR__ . '/.taskrc', __DIR__ . '/.task');
        $this->taskManager = new TaskManager($this->taskwarrior);
        $this->taskwarrior->version(); // to initialise
    }

    public function tearDown()
    {
        $fs = new Filesystem();
        $fs->remove(__DIR__ . '/.taskrc');
        $fs->remove(__DIR__ . '/.task');
    }

    public function testEmpty()
    {
        $tasks = $this->taskManager->filter();
        $this->assertEmpty($tasks);
    }

    public function testSaveTask()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskManager->save($task);
        $this->taskManager->clear();

        $this->assertNotEmpty($task->getUuid());

        $result = $this->taskManager->find($task->getUuid());

        $this->assertEquals($task, $result);
    }

    public function testSaveTaskWithoutDescription()
    {
        $this->setExpectedException('DavidBadura\Taskwarrior\TaskwarriorException');

        $task = new Task();

        $this->taskManager->save($task);
    }

    public function testFindFromCache()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskManager->save($task);

        $result = $this->taskManager->find($task->getUuid());

        $this->assertSame($task, $result);
    }

    public function testFilterFromCache()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskManager->save($task);

        $result = $this->taskManager->filterAll($task->getUuid());

        $this->assertSame($task, $result[0]);
    }

    public function testDontFind()
    {
        $task = $this->taskManager->find('56464asd46s4adas54da6');
        $this->assertNull($task);
    }

    public function testDoubleSave()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskManager->save($task);

        $this->assertNotEmpty($task->getUuid());
        $uuid = $task->getUuid();

        $this->taskManager->save($task);

        $this->assertEquals($uuid, $task->getUuid());
        $this->assertCount(1, $this->taskManager->filter());

        $this->taskManager->clear();

        $this->taskManager->save($task);

        $this->assertEquals($uuid, $task->getUuid());
        $this->assertCount(1, $this->taskManager->filter());
    }

    public function testClone()
    {
        $task = new Task();
        $task->setDescription('foo');

        $this->taskManager->save($task);
        $this->assertCount(1, $this->taskManager->filter());

        $task2 = clone $task;

        $this->taskManager->save($task2);
        $this->assertCount(2, $this->taskManager->filter());

        $this->taskManager->done($task);
        $this->isTrue($task->isCompleted());

        $task3 = clone $task;

        $this->isTrue($task3->isPending());
        $this->taskManager->save($task3);

        $this->isTrue($task->isCompleted());
        $this->isTrue($task3->isPending());
    }


    public function testFilterAll()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $task2 = new Task();
        $task2->setDescription('foo2');

        $this->taskManager->save($task1);
        $this->taskManager->save($task2);

        $this->assertCount(2, $this->taskManager->filter());
    }

    public function testMultiFilter()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $task2 = new Task();
        $task2->setDescription('foo2');
        $task2->setPriority(Task::PRIORITY_HIGH);
        $task2->setProject('home');
        $task2->addTag('now');

        $this->taskManager->save($task1);
        $this->taskManager->save($task2);

        $this->assertCount(1, $this->taskManager->filter('project:home prio:H +now'));
    }

    public function testPending()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->taskManager->save($task1);

        $this->assertEquals(Task::STATUS_PENDING, $task1->getStatus());
        $this->assertTrue($task1->isPending());

        $this->taskManager->clear();
        $result = $this->taskManager->find($task1->getUuid());

        $this->assertEquals(Task::STATUS_PENDING, $result->getStatus());
        $this->assertTrue($result->isPending());

        $this->assertCount(1, $this->taskManager->filter());
    }

    public function testDelete()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->assertCount(0, $this->taskManager->filter());

        $this->taskManager->save($task1);
        $this->assertCount(1, $this->taskManager->filterAll());
        $this->assertCount(1, $this->taskManager->filter());
        $this->assertFalse($task1->isDeleted());
        $this->assertEquals(Task::STATUS_PENDING, $task1->getStatus());

        $this->taskManager->delete($task1);
        $this->assertCount(1, $this->taskManager->filterAll());
        $this->assertCount(0, $this->taskManager->filter());
        $this->assertTrue($task1->isDeleted());
        $this->assertEquals(Task::STATUS_DELETED, $task1->getStatus());
    }

    public function testCompleted()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->assertCount(0, $this->taskManager->filter());

        $this->taskManager->save($task1);
        $this->assertCount(1, $this->taskManager->filterAll());
        $this->assertCount(1, $this->taskManager->filter());
        $this->assertFalse($task1->isCompleted());
        $this->assertEquals(Task::STATUS_PENDING, $task1->getStatus());

        $this->taskManager->done($task1);
        $this->assertCount(1, $this->taskManager->filterAll());
        $this->assertCount(0, $this->taskManager->filter());
        $this->assertTrue($task1->isCompleted());
        $this->assertEquals(Task::STATUS_COMPLETED, $task1->getStatus());
    }

    public function testModifyDescription()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->taskManager->save($task1);

        $this->assertEquals('foo1', $task1->getDescription());

        $task1->setDescription('bar1');
        $this->taskManager->save($task1);

        $this->taskManager->clear();

        $result = $this->taskManager->find($task1->getUuid());

        $this->assertEquals('bar1', $result->getDescription());
    }

    public function testDue()
    {
        $date = $this->createDateTime('1989-01-08 11:12:13');

        $task1 = new Task();
        $task1->setDescription('foo1');
        $task1->setDue($date);

        $this->taskManager->save($task1);
        $this->taskManager->clear();

        $task2 = $this->taskManager->find($task1->getUuid());
        $this->assertEquals($date, $task2->getDue());

        $newDate = $this->createDateTime('2002-02-20 11:12:13');

        $task2->setDue($newDate);

        $this->taskManager->save($task2);
        $this->taskManager->clear();

        $task3 = $this->taskManager->find($task1->getUuid());
        $this->assertEquals($newDate, $task3->getDue());

        $task2->setDue(null);

        $this->taskManager->save($task2);
        $this->taskManager->clear();

        $task3 = $this->taskManager->find($task1->getUuid());
        $this->assertNull($task3->getDue());
    }

    public function testUrgency()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');
        $task1->setDue($this->createDateTime('1989-01-08 11:12:13'));

        $this->taskManager->save($task1);

        $this->assertEquals(12, $task1->getUrgency());

        $task1->setDue(null);

        $this->taskManager->save($task1);

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

        $this->taskManager->save($task1);
        $this->taskManager->save($task2);
        $this->taskManager->save($task3);

        $this->assertEquals(0, $task1->getUrgency());
        $this->assertEquals(12, $task2->getUrgency());
        $this->assertEquals(0, $task3->getUrgency());

        $tasks = $this->taskManager->filter();

        $this->assertEquals(array($task2, $task1, $task3), $tasks);
    }

    public function testProject()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');
        $task1->setProject('home');

        $task2 = new Task();
        $task2->setDescription('foo2');
        $task2->setProject('office');

        $this->taskManager->save($task1);
        $this->taskManager->save($task2);

        $this->taskManager->clear();

        $task1 = $this->taskManager->find($task1->getUuid());
        $task2 = $this->taskManager->find($task2->getUuid());

        $this->assertEquals('home', $task1->getProject());
        $this->assertEquals('office', $task2->getProject());

        $this->assertCount(2, $this->taskManager->filter());
        $this->assertCount(1, $this->taskManager->filter('project:home'));
        $this->assertCount(1, $this->taskManager->filter('project:office'));
        $this->assertCount(0, $this->taskManager->filter('project:hobby'));

        $task2->setProject('home');
        $this->taskManager->save($task2);

        $this->assertCount(2, $this->taskManager->filter());
        $this->assertCount(2, $this->taskManager->filter('project:home'));
        $this->assertCount(0, $this->taskManager->filter('project:office'));
        $this->assertCount(0, $this->taskManager->filter('project:hobby'));
    }

    public function testProjects()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');
        $task1->setProject('home');

        $task2 = new Task();
        $task2->setDescription('foo2');
        $task2->setProject('office');

        $this->taskManager->save($task1);
        $this->taskManager->save($task2);

        $this->assertEquals(array('home', 'office'), $this->taskManager->projects());
    }

    public function testPriority()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');
        $task1->setPriority(Task::PRIORITY_MEDIUM);

        $this->taskManager->save($task1);
        $this->taskManager->clear();

        $task1 = $this->taskManager->find($task1->getUuid());

        $this->assertEquals(Task::PRIORITY_MEDIUM, $task1->getPriority());

        $task1->setPriority(Task::PRIORITY_HIGH);

        $this->taskManager->save($task1);
        $this->taskManager->clear();
        $task1 = $this->taskManager->find($task1->getUuid());

        $this->assertEquals(Task::PRIORITY_HIGH, $task1->getPriority());
    }

    public function testTag()
    {
        $task1 = new Task();
        $task1->setDescription('foo1');

        $this->taskManager->save($task1);
        $this->taskManager->clear();

        $task1 = $this->taskManager->find($task1->getUuid());
        $this->assertEmpty($task1->getTags());

        $task1->removeTag('a');
        $task1->addTag('a');
        $task1->setTags(array('a', 'b', 'c'));
        $this->taskManager->save($task1);
        $this->taskManager->clear();

        $task1 = $this->taskManager->find($task1->getUuid());
        $this->assertEquals(array('a', 'b', 'c'), $task1->getTags());

        $task1->addTag('d');
        $task1->removeTag('a');

        $this->taskManager->save($task1);
        $this->taskManager->clear();

        $task1 = $this->taskManager->find($task1->getUuid());
        $this->assertEquals(array('b', 'c', 'd'), $task1->getTags());
        $this->assertCount(0, $this->taskManager->filter('+a'));
        $this->assertCount(1, $this->taskManager->filter('+b'));
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