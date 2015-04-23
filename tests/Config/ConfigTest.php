<?php

namespace DavidBadura\Taskwarrior\Test;

use DavidBadura\Taskwarrior\Config\Config;

/**
 * @author David Badura <badura@simplethings.de>
 */
class ConfigTest extends \PHPUnit_Framework_TestCase
{
    public function testParse()
    {
        $content = <<<CONFIG
foo.bar=test
baz=1
on=on
off=off
yes=yes
no=no
CONFIG;

        $config = Config::create($content);

        $this->assertTrue($config->has('foo.bar'));
        $this->assertTrue($config->has('baz'));
        $this->assertFalse($config->has('bla'));

        $this->assertEquals('test', $config->get('foo.bar'));
        $this->assertEquals(1, $config->get('baz'));
        $this->assertEquals(true, $config->get('on'));
        $this->assertEquals(false, $config->get('off'));
        $this->assertEquals(true, $config->get('yes'));
        $this->assertEquals(false, $config->get('no'));
    }

    public function testContext()
    {
        $content = <<<CONFIG
context.work=+work or +freelance
CONFIG;

        $config = Config::create($content);

        $this->assertTrue($config->hasContext('work'));

        $context = $config->getContext('work');

        $this->assertEquals('work', $context->name);
        $this->assertEquals('+work or +freelance', $context->filter);
    }

    public function testReport()
    {
        $content = <<<CONFIG
report.active.columns=id,start,start.age
report.active.description=Active tasks
report.active.filter=status:pending and +ACTIVE
report.active.labels=ID,Started,Active,Age
report.active.sort=project+,start+
CONFIG;

        $config = Config::create($content);

        $this->assertTrue($config->hasReport('active'));

        $report = $config->getReport('active');

        $this->assertEquals('active', $report->name);
        $this->assertEquals(['id', 'start', 'start.age'], $report->columns);
        $this->assertEquals('Active tasks', $report->description);
        $this->assertEquals('status:pending and +ACTIVE', $report->filter);
        $this->assertEquals(['ID', 'Started', 'Active', 'Age'], $report->labels);
        $this->assertEquals(['project' => 'ASC', 'start' => 'ASC'], $report->sort);
    }

    public function testUda()
    {
        $content = <<<CONFIG
uda.priority.label=Priority
uda.priority.type=string
uda.priority.values=H,M,L,
CONFIG;

        $config = Config::create($content);

        $this->assertTrue($config->hasUda('priority'));

        $uda = $config->getUda('priority');

        $this->assertEquals('priority', $uda->name);
        $this->assertEquals('Priority', $uda->label);
        $this->assertEquals('string', $uda->type);
        $this->assertEquals(['H', 'M', 'L', ''], $uda->values);
    }
}