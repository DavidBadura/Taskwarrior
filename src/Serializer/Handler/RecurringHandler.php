<?php

namespace DavidBadura\Taskwarrior\Serializer\Handler;

use Carbon\Carbon;
use DavidBadura\Taskwarrior\Recurring;
use JMS\Serializer\Context;
use JMS\Serializer\Exception\RuntimeException;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class RecurringHandler implements SubscribingHandlerInterface
{
    /**
     * @return array
     */
    public static function getSubscribingMethods()
    {
        $methods = array();

        $methods[] = array(
            'type'      => 'Recurring',
            'format'    => 'json',
            'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
            'method'    => 'deserializeCarbon'
        );

        $methods[] = array(
            'type'      => 'Recurring',
            'format'    => 'json',
            'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
            'method'    => 'serializeCarbon'
        );

        return $methods;
    }

    /**
     * @param VisitorInterface $visitor
     * @param Recurring $recurring
     * @param array $type
     * @param Context $context
     * @return string
     */
    public function serializeCarbon(VisitorInterface $visitor, Recurring $recurring, array $type, Context $context)
    {
        return $visitor->visitString((string)$recurring, $type, $context);
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

        return new Recurring((string) $data);
    }
}
