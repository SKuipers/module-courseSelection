<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

use CourseSelection\Domain\SelectionsGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

$gibbonCourseID = $_POST['gibbonCourseID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Course Selection/tools_copyByCourse.php&gibbonSchoolYearID={$gibbonSchoolYearID}&gibbonCourseID={$gibbonCourseID}";

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tools_copyByCourse.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $partialFail = false;
    $selectionsGateway = new SelectionsGateway($pdo);

    $gibbonSchoolYearIDCopyTo = $_POST['gibbonSchoolYearIDCopyTo'] ?? '';
    $gibbonCourseIDCopyTo = $_POST['gibbonCourseIDCopyTo'] ?? '';
    $studentList = $_POST['studentList'] ?? '';
    $status = $_POST['status'] ?? '';
    $overwrite = $_POST['overwrite'] ?? 'Y';

    if (empty($gibbonSchoolYearID) || empty($gibbonSchoolYearIDCopyTo) || empty($gibbonCourseID) || empty($gibbonCourseIDCopyTo) || empty($studentList) || empty($status)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $data = array();
        $data['gibbonSchoolYearID'] = $gibbonSchoolYearIDCopyTo;
        $data['gibbonCourseID'] = $gibbonCourseIDCopyTo;
        $data['courseSelectionBlockID'] = null;
        $data['gibbonPersonIDSelected'] = $_SESSION[$guid]['gibbonPersonID'];
        $data['timestampSelected'] = date('Y-m-d H:i:s');
        $data['status'] = ($status != 'Approved')? $status : 'Requested';
        $data['notes'] = '';

        $dataApproval = array();
        $dataApproval['gibbonPersonIDApproved'] = $_SESSION[$guid]['gibbonPersonID'];
        $dataApproval['timestampApproved'] = date('Y-m-d H:i:s');

        foreach ($studentList as $gibbonPersonIDStudent) {
            $data['gibbonPersonIDStudent'] = $gibbonPersonIDStudent;
            $courseSelectionChoiceID = null;

            $choiceRequest = $selectionsGateway->selectChoiceByCourseAndPerson($gibbonCourseIDCopyTo, $gibbonPersonIDStudent);
            if ($choiceRequest && $choiceRequest->rowCount() > 0) {
                $choice = $choiceRequest->fetch();
                $courseSelectionChoiceID = $choice['courseSelectionChoiceID'];

                if ($overwrite == 'Y') {
                    $partialFail &= !$selectionsGateway->updateChoice($data);
                }
            } else {
                $courseSelectionChoiceID = $selectionsGateway->insertChoice($data);
                $partialFail &= !$courseSelectionChoiceID;
            }

            if ($status == 'Approved' && !empty($courseSelectionChoiceID)) {
                $dataApproval['courseSelectionChoiceID'] = $courseSelectionChoiceID;
                $partialFail &= !$selectionsGateway->insertApproval($dataApproval);
            }
        }

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
}
