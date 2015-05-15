<?php

namespace DavidBadura\Taskwarrior\Test;

use DavidBadura\Taskwarrior\Recurring;

/**
 * @author David Badura <badura@simplethings.de>
 */
class RecurringTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @see http://taskwarrior.org/docs/durations.html
     * @return array
     */
    public function validData()
    {
        return [
            ['5 seconds'],
            ['5 second'],
            ['5 secs'],
            ['5 sec'],
            ['5 s'],
            ['5seconds'],
            ['5second'],
            ['5secs'],
            ['5sec'],
            ['5s'],
            ['second'],
            ['sec'],
            ['5 minutes'],
            ['5 minute'],
            ['5 mins'],
            ['5 min'],
            ['5minutes'],
            ['5minute'],
            ['5mins'],
            ['5min'],
            ['minute'],
            ['min'],
            ['3 hours'],
            ['3 hour'],
            ['3 hrs'],
            ['3 hr'],
            ['3 h'],
            ['3hours'],
            ['3hour'],
            ['3hrs'],
            ['3hr'],
            ['3h'],
            ['hour'],
            ['hr'],
            ['2 days'],
            ['2 day'],
            ['2 d'],
            ['2days'],
            ['2day'],
            ['2d'],
            ['daily'],
            ['day'],
            ['3 weeks'],
            ['3 week'],
            ['3 wks'],
            ['3 wk'],
            ['3 w'],
            ['3weeks'],
            ['3week'],
            ['3wks'],
            ['3wk'],
            ['3w'],
            ['weekly'],
            ['week'],
            ['wk'],
            ['weekdays'],
            ['2 fortnight'],
            ['2 sennight'],
            ['2fortnight'],
            ['2sennight'],
            ['biweekly'],
            ['fortnight'],
            ['sennight'],
            ['5 months'],
            ['5 month'],
            ['5 mnths'],
            ['5 mths'],
            ['5 mth'],
            ['5 mo'],
            ['5 m'],
            ['5months'],
            ['5month'],
            ['5mnths'],
            ['5mths'],
            ['5mth'],
            ['5mo'],
            ['5m'],
            ['monthly'],
            ['month'],
            ['mth'],
            ['mo'],
            ['bimonthly'],
            ['1 quarterly'],
            ['1 quarters'],
            ['1 quarter'],
            ['1 qrtrs'],
            ['1 qrtr'],
            ['1 qtr'],
            ['1 q'],
            ['1quarterly'],
            ['1quarters'],
            ['1quarter'],
            ['1qrtrs'],
            ['1qrtr'],
            ['1qtr'],
            ['1q'],
            ['quarterly'],
            ['quarter'],
            ['qrtr'],
            ['qtr'],
            ['semiannual'],
            ['1 years'],
            ['1 year'],
            ['1 yrs'],
            ['1 yr'],
            ['1 y'],
            ['1years'],
            ['1year'],
            ['1yrs'],
            ['1yr'],
            ['1y'],
            ['annual'],
            ['yearly'],
            ['year'],
            ['yr'],
            ['biannual'],
            ['biyearly']
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
            ['a2w']
        ];
    }

    /**
     * @dataProvider invalidData
     * @param $recur
     */
    public function testInvalid($recur)
    {
        $this->setExpectedException('DavidBadura\Taskwarrior\Exception\RecurringParseException');

        new Recurring($recur);
    }
}