<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\BackgroundProcess;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');

$process = new BackgroundProcess($_SESSION[$guid]['absolutePath'].'/uploads/engine');

echo ($process->isProcessRunning('engine'))? '1' : '0';

