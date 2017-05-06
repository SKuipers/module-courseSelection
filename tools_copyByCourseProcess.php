<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
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
