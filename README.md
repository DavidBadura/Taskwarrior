# Taskwarrior PHP lib

[![Build Status](https://travis-ci.org/DavidBadura/Taskwarrior.svg?branch=master)](https://travis-ci.org/DavidBadura/Taskwarrior)

![WOW](http://i.imgur.com/mvSQh0M.gif)

## Install

```bash
composer require 'davidbadura/taskwarrior'
```

**Requirements: Taskwarrior >=2.1**

## Usage

```php
use DavidBadura\Taskwarrior\TaskManager;
use DavidBadura\Taskwarrior\Task;
use DavidBadura\Taskwarrior\Recurring;

$tm = TaskManager::create();

$task = new Task();
$task->setDescription('program this lib');
$task->setProject('hobby');
$task->setDue('tomorrow');
$task->setPriority(Task::PRIORITY_HIGH);
$task->addTag('next');
$task->setRecurring(Recurring::DAILY);

$tm->save($task);

$tasks = $tm->filter('project:hobby'); // one task

$tm->done($task);

$tasks = $tm->filter('project:hobby'); // empty

$tasks = $tm->filterByReport('waiting'); 
```

## API

todo...

### QueryBuilder

```php
$tasks = $taskManager->createQueryBuilder()
    ->whereProject('hobby')
    ->orderBy(['entry' => 'DESC'])
    ->getResult()
```
