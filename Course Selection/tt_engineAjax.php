<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

$_POST['address'] = '/modules/Course Selection/tt_engine.php';

use Gibbon\Module\CourseSelection\BackgroundProcess;

require_once '../../gibbon.php';

// Module Bootstrap
require 'module.php';

$process = new BackgroundProcess($session->get('absolutePath').'/uploads/engine');

echo ($process->isProcessRunning('engine'))? '1' : '0';

