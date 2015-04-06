<?php
/**
 *
 */

namespace DavidBadura\Taskwarrior;

use DavidBadura\Taskwarrior\Exception\RecurringParseException;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class Recurring
{
    const DAILY      = 'daily';
    const WEEKDAYS   = 'weekdays';
    const WEEKLY     = 'weekly';
    const BIWEEKLY   = 'biweekly';
    const QUARTERLY  = 'quarterly';
    const SEMIANNUAL = 'semiannual';
    const ANNUAL     = 'annual';
    const YEARLY     = 'yearly';
    const BIANNUAL   = 'biannual';
    const BIYEARLY   = 'biyearly';

    /**
     * @var string
     */
    private $recurring;

    /**
     * @param string $recurring
     * @throws RecurringParseException
     */
    public function __construct($recurring)
    {
        if (!self::isValid($recurring)) {
            throw new RecurringParseException(sprintf('recurring "%s" is not valid', $recurring));
        }

        $this->recurring = $recurring;
    }

    /**
     * @return string
     */
    public function getValue()
    {
        return $this->recurring;
    }

    /**
     * @return string
     */
    public function __toString()
    {
        return $this->recurring;
    }

    /**
     * @param string $recur
     * @return bool
     */
    public static function isValid($recur)
    {
        $refClass  = new \ReflectionClass(__CLASS__);
        $constants = $refClass->getConstants();

        if (in_array($recur, $constants)) {
            return true;
        }

        if (preg_match('/^[0-9]+d$/', $recur)) {
            return true;
        }

        if (preg_match('/^[0-9]+w$/', $recur)) {
            return true;
        }

        if (preg_match('/^[0-9]+q$/', $recur)) {
            return true;
        }

        if (preg_match('/^[0-9]+y$/', $recur)) {
            return true;
        }

        return false;
    }
}