<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';
include '../../config.php';

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

        if (empty($secureFilePath)) {
            $secureFilePath = $_SESSION[$guid]['absolutePath'].'/uploads';
            if (!is_dir($secureFilePath.'/engine')) {
                mkdir($secureFilePath.'/engine', 0755);
            }
        }

        $cmd = PHP_BINDIR.'/php tt_engineRun.php '.escapeshellarg('gibbonSchoolYearID');

        $outputfile = $secureFilePath. '/engine/batchOutput.txt';
        $pidfile = $secureFilePath. '/engine/batchProcessing.txt';

        exec(sprintf("%s > %s 2>&1 & echo $! > %s", $cmd, $outputfile, $pidfile));

        $URL .= "&return=success0";
        header("Location: {$URL}");
        exit;
    }
}
