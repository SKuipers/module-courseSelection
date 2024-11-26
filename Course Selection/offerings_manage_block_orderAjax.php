<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Module\CourseSelection\Domain\OfferingsGateway;

$_POST['address'] = '/modules/Course Selection/offerings_manage_addEdit.php';

require_once '../../gibbon.php';

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_addEdit.php') == false) {
    exit;
} else {
    //Proceed!
    $courseSelectionOfferingID = $_POST['courseSelectionOfferingID'] ?? '';
    $courseSelectionBlockIDList = $_POST['order'] ?? [];

    if (empty($courseSelectionOfferingID) || empty($courseSelectionBlockIDList)) {
        exit;
    } else {
        $gateway = $container->get(OfferingsGateway::class);

        $count = 1;
        foreach ($courseSelectionBlockIDList as $courseSelectionBlockID) {
            $inserted = $gateway->updateBlockOrder($courseSelectionOfferingID, $courseSelectionBlockID, $count);
            $count++;
        }
    }
}
