<?php

namespace DavidBadura\Taskwarrior;

use DavidBadura\Taskwarrior\Exception\TaskwarriorException;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class QueryBuilder
{
    const SORT_URGENCY     = 'urgency';
    const SORT_DESCRIPTION = 'description';
    const SORT_ENTRY       = 'entry';

    /**
     * @var TaskManager
     */
    protected $taskManager;

    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var string
     */
    protected $sortBy = self::SORT_URGENCY;

    /**
     * @param TaskManager $taskManager
     */
    public function __construct(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
    }

    /**
     * @param string $project
     * @return $this
     */
    public function whereProject($project)
    {
        return $this->where('project:' . $project);
    }

    /**
     * @param string $tag
     * @return $this
     */
    public function whereTag($tag)
    {
        return $this->where('+' . $tag);
    }

    /**
     * @param string $priority
     * @return $this
     */
    public function wherePriority($priority)
    {
        return $this->where('priority:' . $priority);
    }

    /**
     * @param string $status
     * @return $this
     */
    public function whereStatus($status)
    {
        return $this->where('status:' . $status);
    }

    /**
     * @param string $where
     * @return $this
     */
    public function where($where)
    {
        $this->filter[] = $where;

        return $this;
    }

    /**
     * @param string $by
     * @return $this
     */
    public function sortBy($by = self::SORT_URGENCY)
    {
        $this->sortBy = $by;

        return $this;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return implode(' ', $this->filter);
    }

    /**
     * @return Task[]
     */
    public function getResult()
    {
        $result = $this->taskManager->filter($this->getFilter());

        return $this->sort($result, $this->sortBy);
    }

    /**
     * @param Task[] $tasks
     * @param string $by
     * @return Task[]
     * @throws TaskwarriorException
     */
    protected function sort(array $tasks, $by)
    {
        switch ($by) {
            case self::SORT_ENTRY:

                $callback = function (Task $a, Task $b) {
                    return $a->getEntry() >= $b->getEntry() ? 1 : -1;
                };

                break;

            case self::SORT_DESCRIPTION:

                $callback = function (Task $a, Task $b) {
                    return strcmp($b->getDescription(), $a->getDescription());
                };

                break;

            case self::SORT_URGENCY:

                $callback = function (Task $a, Task $b) {
                    if (0 != $diff = $b->getUrgency() - $a->getUrgency()) {
                        return $diff;
                    }

                    return $a->getEntry() >= $b->getEntry() ? 1 : -1;
                };

                break;

            default:
                throw new TaskwarriorException('sorting by "%s" is not supported', $by);
        }

        usort($tasks, $callback);

        return $tasks;
    }
}