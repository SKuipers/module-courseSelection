<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\Domain\TimetableGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/tt_engine.php';

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

        $timetableGateway = new TimetableGateway($pdo);

        $copied = $timetableGateway->transformResultsIntoClassEnrolments($gibbonSchoolYearID);

        if ($copied) {
            $deleted = $timetableGateway->deleteAllResultsBySchoolYear($gibbonSchoolYearID);
        }

        if ($copied == false || $deleted == false) {
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
