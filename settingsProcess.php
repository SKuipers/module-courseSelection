<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\Domain\SettingsGateway;

// Module Bootstrap
require 'module.php';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/settings.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/settings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $partialFail = false;
    $settingsGateway = new SettingsGateway($pdo);

    $activeSchoolYear = $_POST['activeSchoolYear'] ?? $_SESSION[$guid]['gibbonSchoolYearID'];
    $partialFail &= !$settingsGateway->update('Course Selection', 'activeSchoolYear', $activeSchoolYear);

    $requireApproval = $_POST['requireApproval'] ?? 'Y';
    $partialFail &= !$settingsGateway->update('Course Selection', 'requireApproval', $requireApproval);

    $infoTextOfferings = $_POST['infoTextOfferings'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'infoTextOfferings', $infoTextOfferings);

    $infoTextSelectionBefore = $_POST['infoTextSelectionBefore'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'infoTextSelectionBefore', $infoTextSelectionBefore);

    $infoTextSelectionAfter = $_POST['infoTextSelectionAfter'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'infoTextSelectionAfter', $infoTextSelectionAfter);

    $selectionComplete = $_POST['selectionComplete'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'selectionComplete', $selectionComplete);

    $selectionInvalid = $_POST['selectionInvalid'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'selectionInvalid', $selectionInvalid);

    $selectionContinue = $_POST['selectionContinue'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'selectionContinue', $selectionContinue);

    $classEnrolmentMinimum = $_POST['classEnrolmentMinimum'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'classEnrolmentMinimum', $classEnrolmentMinimum);

    $classEnrolmentTarget = $_POST['classEnrolmentTarget'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'classEnrolmentTarget', $classEnrolmentTarget);

    $classEnrolmentMaximum = $_POST['classEnrolmentMaximum'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'classEnrolmentMaximum', $classEnrolmentMaximum);

    if ($partialFail == true) {
        $URL .= '&return=warning2';
        header("Location: {$URL}");
        exit;
    } else {
        $URL .= "&return=success0";
        header("Location: {$URL}");
        exit;
    }
}
