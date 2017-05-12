<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';
include '../../config.php';

use CourseSelection\BackgroundProcess;
use CourseSelection\Domain\SettingsGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/tt_engine.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tt_engine.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $gibbonSchoolYearID = (isset($_POST['gibbonSchoolYearID']))? $_POST['gibbonSchoolYearID'] : null;

    if (empty($gibbonSchoolYearID)) {
        $URL .= "&return=error1";
        header("Location: {$URL}");
        exit;
    } else {
        $settingsGateway = new SettingsGateway($pdo);

        // Save any changes made to timetabling settings
        $genderBalancePriority = $_POST['genderBalancePriority'] ?? '0.5';
        $settingsGateway->update('Course Selection', 'genderBalancePriority', $genderBalancePriority);

        $targetEnrolmentPriority = $_POST['targetEnrolmentPriority'] ?? '1.0';
        $settingsGateway->update('Course Selection', 'targetEnrolmentPriority', $targetEnrolmentPriority);

        $coreCoursePriority = $_POST['coreCoursePriority'] ?? '1.0';
        $settingsGateway->update('Course Selection', 'coreCoursePriority', $coreCoursePriority);

        $avoidConflictPriority = $_POST['avoidConflictPriority'] ?? '2.0';
        $settingsGateway->update('Course Selection', 'avoidConflictPriority', $avoidConflictPriority);

        $timetableConflictTollerance = $_POST['timetableConflictTollerance'] ?? '0';
        $settingsGateway->update('Course Selection', 'timetableConflictTollerance', $timetableConflictTollerance);

        $autoResolveConflicts = $_POST['autoResolveConflicts'] ?? 'Y';
        $settingsGateway->update('Course Selection', 'autoResolveConflicts', $autoResolveConflicts);

        $process = new BackgroundProcess($_SESSION[$guid]['absolutePath'].'/uploads/engine');
        $process->startProcess('engine', __DIR__.'/tt_engineRun.php', array($gibbonSchoolYearID));

        header("Location: {$URL}");
        exit;
    }
}
