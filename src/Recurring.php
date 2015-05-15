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
    const MONTHLY    = 'monthly';
    const BIMONTHLY  = 'bimonthly';
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

        // seconds
        if (preg_match('/^[0-9]*\s?se?c?o?n?d?s?$/', $recur)) {
            return true;
        }

        // minutes
        if (preg_match('/^[0-9]*\s?mi?n?u?t?e?s?$/', $recur)) {
            return true;
        }

        // hours
        if (preg_match('/^[0-9]*\s?ho?u?r?s?$/', $recur)) {
            return true;
        }

        // days
        if (preg_match('/^[0-9]*\s?da?y?s?$/', $recur)) {
            return true;
        }

        // weeks
        if (preg_match('/^[0-9]*\s?we?e?k?s?$/', $recur)) {
            return true;
        }

        // months
        if (preg_match('/^[0-9]*\s?mo?n?t?h?s?$/', $recur)) {
            return true;
        }

        // quarters
        if (preg_match('/^[0-9]*\s?qu?a?r?t?e?r?(s|ly)?/', $recur)) {
            return true;
        }

        // years
        if (preg_match('/^[0-9]*\s?ye?a?r?s?$/', $recur)) {
            return true;
        }

        // fortnight | sennight
        if (preg_match('/^[0-9]*\s?(fortnight|sennight)$/', $recur)) {
            return true;
        }

        return false;
    }
}