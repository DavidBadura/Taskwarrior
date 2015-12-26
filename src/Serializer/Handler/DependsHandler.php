<?php

namespace DavidBadura\Taskwarrior\Serializer\Handler;

use DavidBadura\Taskwarrior\Exception\ReferenceException;
use DavidBadura\Taskwarrior\Task;
use DavidBadura\Taskwarrior\TaskManager;
use Doctrine\Common\Collections\ArrayCollection;
use JMS\Serializer\Context;
use JMS\Serializer\GraphNavigator;
use JMS\Serializer\Handler\SubscribingHandlerInterface;
use JMS\Serializer\VisitorInterface;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class DependsHandler implements SubscribingHandlerInterface
{
    /**
     * @var TaskManager
     */
    protected $taskManager;

    /**
     * @param TaskManager $taskManager
     */
    function __construct(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
    }

    /**
     * @return array
     */
    public static function getSubscribingMethods()
    {
        $methods = array();

        $methods[] = array(
            'type'      => 'Depends',
            'format'    => 'json',
            'direction' => GraphNavigator::DIRECTION_DESERIALIZATION,
            'method'    => 'deserialize'
        );

        $methods[] = array(
            'type'      => 'Depends',
            'format'    => 'json',
            'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
            'method'    => 'serialize'
        );

        return $methods;
    }

    /**
     * @param VisitorInterface $visitor
     * @param Task[] $tasks
     * @param array $type
     * @param Context $context
     * @return string
     * @throws ReferenceException
     */
    public function serialize(VisitorInterface $visitor, $tasks, array $type, Context $context)
    {
        $list = [];

        foreach ($tasks as $task) {
            if (!$task->getUuid()) {
                throw new ReferenceException("you can't save a task that has dependencies to tasks that have not been saved");
            }

            $list[] = $task->getUuid();
        }

        return $visitor->visitArray($list, $type, $context);
    }

    /**
     * @param VisitorInterface $visitor
     * @param array $data
     * @param array $type
     * @return ArrayCollection
     */
    public function deserialize(VisitorInterface $visitor, $data, array $type)
    {
        if (!$data) {
            return new ArrayCollection();
        }

        $tasks = [];

        foreach ($data as $uuid) {
            $tasks[] = $this->taskManager->getReference($uuid);
        }

        return new ArrayCollection($tasks);
    }
}
