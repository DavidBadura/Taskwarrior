<?php

namespace DavidBadura\Taskwarrior\Query;

use DavidBadura\Taskwarrior\Task;
use DavidBadura\Taskwarrior\TaskManager;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Criteria;

/**
 * @author David Badura <d.a.badura@gmail.com>
 */
class QueryBuilder
{
    /**
     * @var string
     */
    const ASC = Criteria::ASC;

    /**
     * @var string
     */
    const DESC = Criteria::DESC;

    /**
     * @var TaskManager
     */
    protected $taskManager;

    /**
     * @var array
     */
    protected $filter = [];

    /**
     * @var Criteria
     */
    protected $criteria;

    /**
     * @param TaskManager $taskManager
     */
    public function __construct(TaskManager $taskManager)
    {
        $this->taskManager = $taskManager;
        $this->criteria    = new Criteria();
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
     * @return $this
     */
    public function wherePriorityL()
    {
        return $this->wherePriority(Task::PRIORITY_LOW);
    }

    /**
     * @return $this
     */
    public function wherePriorityM()
    {
        return $this->wherePriority(Task::PRIORITY_MEDIUM);
    }

    /**
     * @return $this
     */
    public function wherePriorityH()
    {
        return $this->wherePriority(Task::PRIORITY_HIGH);
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
     * @return $this
     */
    public function wherePending()
    {
        return $this->whereStatus(Task::STATUS_PENDING);
    }

    /**
     * @return $this
     */
    public function whereWaiting()
    {
        return $this->whereStatus(Task::STATUS_WAITING);
    }

    /**
     * @return $this
     */
    public function whereCompleted()
    {
        return $this->whereStatus(Task::STATUS_COMPLETED);
    }

    /**
     * @return $this
     */
    public function whereRecurring()
    {
        return $this->whereStatus(Task::STATUS_RECURRING);
    }

    /**
     * @return $this
     */
    public function whereDeleted()
    {
        return $this->whereStatus(Task::STATUS_DELETED);
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
     * @param array $orderings
     * @return $this
     */
    public function orderBy(array $orderings)
    {
        $this->criteria->orderBy($orderings);

        return $this;
    }

    /**
     * @param int $firstResult
     * @return $this
     */
    public function setFirstResult($firstResult)
    {
        $this->criteria->setFirstResult($firstResult);

        return $this;
    }

    /**
     * @param int $maxResults
     * @return $this
     */
    public function setMaxResults($maxResults)
    {
        $this->criteria->setMaxResults($maxResults);

        return $this;
    }

    /**
     * @return string
     */
    public function getFilter()
    {
        return $this->filter;
    }

    /**
     * @return Task[]|ArrayCollection
     */
    public function getResult()
    {
        $result = $this->taskManager->filter($this->filter);

        return $result->matching($this->criteria);
    }

    /**
     * @return int
     */
    public function count()
    {
        return $this->taskManager->count($this->filter);
    }
}