<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

require_once '../../gibbon.php';

use use Gibbon\Module\CourseSelection\Domain\BlocksGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_addEdit.php') == false) {
    exit;
} else {

    /*
    // Proceed!
    //$data = $_POST['data'] ?? [];

    $data = array();
    //$data['courseSelectionBlockID'] = $_POST['data']['courseSelectionBlockID'] ?? '';
    $order = json_decode($_POST['order']);

    if (empty($order)) {
        exit;
    } else {
        $gateway = $container->get(BlocksGateway::class);
        //$gateway = $container->get('CourseSelection\Domain\BlocksGateway');

        $count = 1;
        foreach ($order as $courseSelectionBlockID) {
            $updated = $gateway->update($courseSelectionBlockID, ['sequenceNumber' => $count]);
            $count++;
        }
    }
*/

    //Proceed!
    $data = array();
    $data['courseSelectionBlockID'] = $_POST['data']['courseSelectionBlockID'] ?? '';

    $courseList = json_decode($_POST['order']);

    if (empty($data['courseSelectionBlockID']) || empty($courseList)) {
        exit;
    } else {
        $gateway = $container->get('CourseSelection\Domain\BlocksGateway');

        $count = 1;
        foreach ($courseList as $gibbonCourseID) {

            $data['gibbonCourseID'] = $gibbonCourseID;
            $data['sequenceNumber'] = $count;

            $inserted = $gateway->updateBlockOrder($data);
            $count++;
        }
    }
}