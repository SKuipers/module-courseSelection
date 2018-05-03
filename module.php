<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

// Include the Composer autoloader
require __DIR__ . '/vendor/autoload.php';

// Module Functions
require __DIR__ . '/moduleFunctions.php';

// Add module namespace to Gibbon autoloader
$autoloader->addPsr4('CourseSelection\\', $_SESSION[$guid]['absolutePath'].'/modules/Course Selection/src/');

// Setup the DI Container
$container = new League\Container\Container;

// Register the reflection container as a delegate to enable auto wiring
$container->delegate(
    new League\Container\ReflectionContainer
);


// Register the core services as instances
$container->share('Gibbon\Contracts\Database\Connection', $pdo);
$container->share('Gibbon\session', $gibbon->session);
$container->add('pdo', $pdo, true);
