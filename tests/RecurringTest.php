<?php

namespace DavidBadura\Taskwarrior\Test;

use DavidBadura\Taskwarrior\Recurring;

/**
 * @author David Badura <badura@simplethings.de>
 */
class RecurringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @return array
     */
    public function validData()
    {
        return [
            ['daily'],
            ['weekdays'],
            ['weekly'],
            ['biweekly'],
            ['quarterly'],
            ['semiannual'],
            ['annual'],
            ['yearly'],
            ['biannual'],
            ['biyearly'],
            ['2d'],
            ['12d'],
            ['2w'],
            ['12w'],
            ['2q'],
            ['12q'],
            ['2y'],
            ['12y']
        ];
    }

    /**
     * @dataProvider validData
     * @param $recur
     */
    public function testValid($recur)
    {
        $this->assertEquals($recur, (string)new Recurring($recur));
    }

    /**
     * @return array
     */
    public function invalidData()
    {
        return [
            ['dailya'],
            ['asdasd'],
            ['foo'],
            ['weekday'],
            ['2x'],
            ['a2w'],
            ['d']
        ];
    }

    /**
     * @dataProvider invalidData
     * @param $recur
     */
    public function testInvalid($recur)
    {
        $this->setExpectedException('DavidBadura\Taskwarrior\Exception\RecurringParseException');

        $obj = new Recurring($recur);
    }
}