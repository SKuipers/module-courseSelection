<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Module\CourseSelection\Domain\OfferingsGateway;

require_once '../../gibbon.php';

// Module Bootstrap
require 'module.php';

$courseSelectionOfferingID = $_GET['courseSelectionOfferingID'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/offerings_manage_addEdit.php&courseSelectionOfferingID='.$courseSelectionOfferingID;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $courseSelectionBlockID = $_GET['courseSelectionBlockID'] ?? '';

    if (empty($courseSelectionOfferingID) || empty($courseSelectionBlockID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $gateway = $container->get(OfferingsGateway::class);

        $deleted = $gateway->deleteBlock($courseSelectionOfferingID, $courseSelectionBlockID);

        if ($deleted == false) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        } else {
            $URL .= "&return=success0";
            header("Location: {$URL}");
            exit;
        }
    }
}
