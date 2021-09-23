<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\Domain\TimetableGateway;

// Module Bootstrap
require 'module.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/tt_engine.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tt_engine.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

    if (empty($gibbonSchoolYearID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $timetableGateway = $container->get('CourseSelection\Domain\TimetableGateway');

        $copied = $timetableGateway->transformResultsIntoClassEnrolments($gibbonSchoolYearID);

        if ($copied == false) {
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
