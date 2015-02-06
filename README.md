# Taskwarrior

[![Build Status](https://travis-ci.org/DavidBadura/Taskwarrior.svg?branch=master)](https://travis-ci.org/DavidBadura/Taskwarrior)

```php
$tm = \DavidBadura\Taskwarrior\TaskManager::create();

$task = new \DavidBadura\Taskwarrior\Task();
$task->addTag('home');

$tm->save($task);

$tasks = $tm->filter('+home');
```
