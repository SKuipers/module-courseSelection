<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Module\CourseSelection\Domain\BlocksGateway;

$_POST['address'] = '/modules/Course Selection/blocks_manage_addEdit.php';

require_once '../../gibbon.php';

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_addEdit.php') == false) {
    exit;
} else {

    //Proceed!
    $data = array();
    $data['courseSelectionBlockID'] = $_POST['data']['courseSelectionBlockID'] ?? '';

    $courseList = json_decode($_POST['order']);

    if (empty($data['courseSelectionBlockID']) || empty($courseList)) {
        exit;
    } else {
        $gateway = $container->get(BlocksGateway::class);

        $count = 1;
        foreach ($courseList as $gibbonCourseID) {
            $data['gibbonCourseID'] = $gibbonCourseID;
            $data['sequenceNumber'] = $count;

            $updated = $gateway->updateBlockOrder($data);
            $count++;
        }
    }
}
