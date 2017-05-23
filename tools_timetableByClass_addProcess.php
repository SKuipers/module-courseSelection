<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\Domain\ToolsGateway;

// Module Bootstrap
require 'module.php';

$gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
$gibbonCourseClassID = $_REQUEST['gibbonCourseClassID'] ?? '';
$gibbonTTID = $_REQUEST['gibbonTTID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Course Selection/tools_timetableByClass.php&gibbonSchoolYearID={$gibbonSchoolYearID}&gibbonTTID={$gibbonTTID}&gibbonCourseClassID={$gibbonCourseClassID}";

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tools_timetableByClass.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $gibbonTTDayID = $_POST['gibbonTTDayID'] ?? '';
    $gibbonTTColumnRowID = $_POST['gibbonTTColumnRowID'] ?? '';

    $gibbonTTColumnRowID = strstr($gibbonTTColumnRowID, '-', true);

    if (empty($gibbonCourseClassID) || empty($gibbonTTDayID) || empty($gibbonTTColumnRowID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $gateway = $container->get('CourseSelection\Domain\ToolsGateway');

        $data = array(
            'gibbonTTColumnRowID' => $gibbonTTColumnRowID,
            'gibbonTTDayID'       => $gibbonTTDayID,
            'gibbonCourseClassID' => $gibbonCourseClassID,
            'gibbonSpaceID'       => null,
        );

        $inserted = $gateway->insertTTDayRowClass($data);

        if ($inserted == false) {
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
