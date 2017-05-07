<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\Domain\BlocksGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');

$courseSelectionBlockID = $_POST['courseSelectionBlockID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/blocks_manage_addEdit.php&courseSelectionBlockID='.$courseSelectionBlockID;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $data = array();
    $data['courseSelectionBlockID'] = $_POST['courseSelectionBlockID'] ?? '';
    $gibbonCourseIDList = $_POST['gibbonCourseID'] ?? '';

    if (empty($data['courseSelectionBlockID']) || empty($gibbonCourseIDList)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $gateway = new BlocksGateway($pdo);

        $partialFail = false;
        foreach ($gibbonCourseIDList as $gibbonCourseID) {
            $data['gibbonCourseID'] = $gibbonCourseID;
            $inserted = $gateway->insertCourse($data);

            $partialFail = $partialFail && !$inserted;
        }

        if ($partialFail == true) {
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
