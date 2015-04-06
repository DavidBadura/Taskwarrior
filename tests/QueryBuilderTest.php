<?php

namespace DavidBadura\Taskwarrior\Test;

use DavidBadura\Taskwarrior\QueryBuilder;

/**
 * @author David Badura <badura@simplethings.de>
 */
class QueryBuilderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var QueryBuilder
     */
    protected $builder;

    public function setUp()
    {
        $taskManager = $this->getMockBuilder('DavidBadura\Taskwarrior\TaskManager')
            ->disableOriginalConstructor()
            ->getMock();

        $this->builder = new QueryBuilder($taskManager);
    }

    public function testWhere()
    {
        $filter = $this->builder
            ->whereProject('testProject')
            ->whereTag('testTag')
            ->whereStatus('testStatus')
            ->wherePriority('testPriority')
            ->getFilter();

        $this->assertEquals('project:testProject +testTag status:testStatus priority:testPriority', $filter);
    }
}