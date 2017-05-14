<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\Domain\MetaDataGateway;

// Module Bootstrap
require 'module.php';

$courseSelectionMetaDataID = $_POST['courseSelectionMetaDataID'] ?? '';
$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/meta_manage_addEdit.php&gibbonSchoolYearID'.$gibbonSchoolYearID.'&courseSelectionMetaDataID='.$courseSelectionMetaDataID;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/meta_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $data = array();
    $data['courseSelectionMetaDataID'] = $courseSelectionMetaDataID;
    $data['gibbonCourseID'] = $_POST['gibbonCourseID'] ?? '';
    $data['enrolmentGroup'] = $_POST['enrolmentGroup'] ?? '';
    $data['timetablePriority'] = $_POST['timetablePriority'] ?? '';
    $data['tags'] = $_POST['tags'] ?? '';

    if (empty($data['courseSelectionMetaDataID']) || empty($data['gibbonCourseID']) || empty($data['enrolmentGroup']) || empty($data['timetablePriority']) || empty($data['tags'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $gateway = new MetaDataGateway($pdo);

        $updated = $gateway->update($data);

        if ($updated == false) {
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
