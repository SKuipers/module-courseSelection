<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../gibbon.php';

use CourseSelection\Domain\MetaDataGateway;

// Module Bootstrap
require 'module.php';

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
$gibbonSchoolYearIDNext = $_REQUEST['gibbonSchoolYearIDNext'] ?? '';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/meta_manage.php&gibbonSchoolYearID='.$gibbonSchoolYearIDNext;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/meta_manage.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!

    if (empty($gibbonSchoolYearID) || empty($gibbonSchoolYearIDNext)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $gateway = $container->get('CourseSelection\Domain\MetaDataGateway');

        $updated = $gateway->copyAllBySchoolYear($gibbonSchoolYearID, $gibbonSchoolYearIDNext);

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
