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

use Gibbon\Modules\CourseSelection\Domain\SelectionsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');

$gibbonSchoolYearID = $_POST['gibbonSchoolYearID'] ?? '';

$gibbonCourseID = $_POST['gibbonCourseID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Course Selection/tools_copy.php&gibbonSchoolYearID={$gibbonSchoolYearID}&gibbonCourseID={$gibbonCourseID}";

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tools_copy.php') == false) {
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

    if (empty($gibbonSchoolYearID) || empty($gibbonSchoolYearIDCopyTo) || empty($gibbonCourseID) || empty($gibbonCourseIDCopyTo) || empty($studentList) || empty($status)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $data = array();
        $data['gibbonSchoolYearID'] = $gibbonSchoolYearIDCopyTo;
        $data['gibbonCourseID'] = $gibbonCourseIDCopyTo;
        $data['courseSelectionBlockID'] = null;
        $data['gibbonPersonIDSelected'] = $_POST['gibbonPersonIDSelected'] ?? '';
        $data['timestampSelected'] = date('Y-m-d H:i:s');
        $data['status'] = $status;
        $data['notes'] = '';

        $overwriteExistingSelections = $_POST['overwrite'] ?? 'Y';

        foreach ($studentList as $gibbonPersonIDStudent) {
            $data['gibbonPersonIDStudent'] = $gibbonPersonIDStudent;

            $choiceRequest = $selectionsGateway->selectChoiceByCourseAndPerson($gibbonCourseIDCopyTo, $gibbonPersonIDStudent);
            if ($choiceRequest && $choiceRequest->rowCount() > 0) {
                $choice = $choiceRequest->fetch();

                if ($overwriteExistingSelections == 'Y') {
                    $partialFail &= !$selectionsGateway->updateChoice($data);
                }
            } else {
                $partialFail &= !$selectionsGateway->insertChoice($data);
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
