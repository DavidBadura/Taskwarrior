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
            'method'    => 'deserializeCarbon'
        );

        $methods[] = array(
            'type'      => 'Depends',
            'format'    => 'json',
            'direction' => GraphNavigator::DIRECTION_SERIALIZATION,
            'method'    => 'serializeCarbon'
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
    public function serializeCarbon(VisitorInterface $visitor, $tasks, array $type, Context $context)
    {
        $list = [];

        foreach ($tasks as $task) {
            if (!$task->getUuid()) {
                throw new ReferenceException("you can't save a task that has dependencies to tasks that have not been saved");
            }

            $list[] = $task->getUuid();
        }

        return $visitor->visitString(implode(',', $list), $type, $context);
    }

    /**
     * @param VisitorInterface $visitor
     * @param string $data
     * @param array $type
     * @return ArrayCollection
     */
    public function deserializeCarbon(VisitorInterface $visitor, $data, array $type)
    {
        dump($data);

        if (!$data) {
            return new ArrayCollection();
        }

        $tasks = [];

        foreach (explode(',', $data) as $uuid) {
            $tasks[] = $this->taskManager->getReference($uuid);
        }

        return new ArrayCollection($tasks);
    }
}
