# Taskwarrior

[![Build Status](https://travis-ci.org/DavidBadura/Taskwarrior.svg?branch=master)](https://travis-ci.org/DavidBadura/Taskwarrior)

```php
$tw = new \DavidBadura\Taskwarrior\Taskwarrior();

$task = new \DavidBadura\Taskwarrior\Task();
$task->addTag('home');

$tw->save($task);

$tasks = $tw->filter('+home');
```
