<?php

namespace DavidBadura\Taskwarrior\Serializer\Handler;

use Carbon\Carbon;
use JMS\Serializer\Context;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class CarbonHandler implements SubscribingHandlerInterface
{
    /**
     * @var string
     */
    private $defaultFormat;

    /**
     * @var \DateTimeZone
     */
    private $defaultTimezone;

    /**
     * @param string $defaultFormat
     * @param string $defaultTimezone
     */
    public function __construct($defaultFormat = 'Ymd\THis\Z', $defaultTimezone = 'UTC')
    {
        $this->defaultFormat   = $defaultFormat;
        $this->defaultTimezone = new \DateTimeZone($defaultTimezone);
    }

    /**
     * @return array
     */
    public static function getSubscribingMethods()
    {
        $methods = array();
        $types   = array(Carbon::class, 'Carbon');

        foreach ($types as $type) {
            $methods[] = array(
                'type'      => $type,
                'format'    => 'json',
                'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
                'method'    => 'deserializeCarbon'
            );

            $methods[] = array(
                'type'      => $type,
                'format'    => 'json',
                'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
                'method'    => 'serializeCarbon'
            );
        }

        return $methods;
    }

    /**
     * @param VisitorInterface $visitor
     * @param Carbon $date
     * @param array $type
     * @param Context $context
     * @return string
     */
    public function serializeCarbon(VisitorInterface $visitor, Carbon $date, array $type, Context $context)
    {
        $date = clone $date;
        $date->setTimezone($this->defaultTimezone);

        return $visitor->visitString($date->format($this->getFormat($type)), $type, $context);
    }

    /**
     * @param VisitorInterface $visitor
     * @param string $data
     * @param array $type
     * @return \DateTime|null
     */
    public function deserializeCarbon(VisitorInterface $visitor, $data, array $type)
    {
        if (null === $data) {
            return null;
        }

        $timezone = isset($type['params'][1]) ? new \DateTimeZone($type['params'][1]) : $this->defaultTimezone;
        $format   = $this->getFormat($type);

        $datetime = Carbon::createFromFormat($format, (string)$data, $timezone);

        if (false === $datetime) {
            throw new RuntimeException(sprintf('Invalid datetime "%s", expected format %s.', $data, $format));
        }

        $datetime->setTimezone(new \DateTimeZone(date_default_timezone_get()));

        return $datetime;
    }

    /**
     * @param array $type
     * @return string
     */
    private function getFormat(array $type)
    {
        return isset($type['params'][0]) ? $type['params'][0] : $this->defaultFormat;
    }
}
