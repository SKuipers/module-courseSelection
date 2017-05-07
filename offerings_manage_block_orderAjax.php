<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\Domain\OfferingsGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_addEdit.php') == false) {
    exit;
} else {
    //Proceed!
    $data = array();
    $data['courseSelectionOfferingID'] = $_POST['courseSelectionOfferingID'] ?? '';

    $courseSelectionBlockIDList = json_decode($_POST['blocklist']);

    if (empty($data['courseSelectionOfferingID']) || empty($courseSelectionBlockIDList)) {
        exit;
    } else {
        $gateway = new OfferingsGateway($pdo);

        $count = 1;
        foreach ($courseSelectionBlockIDList as $courseSelectionBlockID) {

            $data['courseSelectionBlockID'] = $courseSelectionBlockID;
            $data['sequenceNumber'] = $count;

            $inserted = $gateway->updateBlockOrder($data);
            $count++;
        }
    }
}
