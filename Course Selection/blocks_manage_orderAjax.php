<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Module\CourseSelection\Domain\BlocksGateway;

require_once '../../gibbon.php';

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_addEdit.php') == false) {
    exit;
} else {

    //Proceed!
    $courseSelectionBlockID = $_POST['courseSelectionBlockID'] ?? '';
    $courseList = $_POST['order'] ?? [];

    if (empty($courseSelectionBlockID) || empty($courseList)) {
        exit;
    } else {
        $gateway = $container->get(BlocksGateway::class);

        $count = 1;
        foreach ($courseList as $gibbonCourseID) {
            $updated = $gateway->updateBlockOrder($courseSelectionBlockID, $gibbonCourseID, $count);
            $count++;
        }
    }
}
