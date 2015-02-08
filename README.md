# Taskwarrior PHP lib

[![Build Status](https://travis-ci.org/DavidBadura/Taskwarrior.svg?branch=master)](https://travis-ci.org/DavidBadura/Taskwarrior)

![WOW](http://i.imgur.com/mvSQh0M.gif)

```php
use DavidBadura\Taskwarrior\TaskManager;
use DavidBadura\Taskwarrior\Task;
use DavidBadura\Taskwarrior\Recurring;

$tm = TaskManager::create();

$task = new Task();
$task->setDescription('program this lib');
$task->setProject('hobby');
$task->setDue(new \DateTime('tomorrow'));
$task->setPriority(Task::PRIORITY_HIGH);
$task->addTag('next');
$task->setRecur(new Recurring(Recurring::DAILY));

$tm->save($task);

$tasks = $tm->filter('project:hobby'); // one task

$tm->done($task);

$tasks = $tm->filter('project:hobby'); // empty
```
