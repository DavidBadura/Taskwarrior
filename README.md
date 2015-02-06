# Taskwarrior

[![Build Status](https://travis-ci.org/DavidBadura/Taskwarrior.svg?branch=master)](https://travis-ci.org/DavidBadura/Taskwarrior)

```php
$tm = \DavidBadura\Taskwarrior\TaskManager::create();

$task = new \DavidBadura\Taskwarrior\Task();
$task->setDesciption('program this lib');
$task->setProject('hobby');
$task->setDue(new \DateTime('tomorrow'));

$task->addTag('next'); // todo :D

$tm->save($task);

$tasks = $tm->filter('project:hobby');
```
