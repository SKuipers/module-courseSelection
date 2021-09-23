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

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/meta_manage_addEdit.php&gibbonSchoolYearID'.$gibbonSchoolYearID.'&courseSelectionMetaDataID='.$courseSelectionMetaDataID;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/meta_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $data = array();
    $data['courseSelectionMetaDataID'] = $courseSelectionMetaDataID;
    $data['gibbonCourseID'] = $_POST['gibbonCourseID'] ?? '';
    $data['enrolmentGroup'] = $_POST['enrolmentGroup'] ?? null;
    $data['timetablePriority'] = $_POST['timetablePriority'] ?? null;
    $data['tags'] = $_POST['tags'] ?? null;
    $data['excludeClasses'] = $_POST['excludeClasses'] ?? '';

    if (is_array($data['excludeClasses'])) $data['excludeClasses'] = implode(',', $data['excludeClasses']);

    if (empty($data['courseSelectionMetaDataID']) || empty($data['gibbonCourseID'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $gateway = $container->get('CourseSelection\Domain\MetaDataGateway');

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
