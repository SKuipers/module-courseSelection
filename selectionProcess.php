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

$courseSelectionOfferingID = $_POST['courseSelectionOfferingID'] ?? '';
$gibbonPersonIDStudent = $_POST['gibbonPersonIDStudent'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Course Selection/selectionChoices.php&sidebar=false&gibbonPersonIDStudent={$gibbonPersonIDStudent}&courseSelectionOfferingID={$courseSelectionOfferingID}";
$failURL = $_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/Course Selection/selection.php&gibbonPersonIDStudent={$gibbonPersonIDStudent}";

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/selection.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$failURL}");
    exit;
} else {
    //Proceed!
    $data = array();
    $data['gibbonSchoolYearID'] = $_POST['gibbonSchoolYearID'] ?? '';
    $data['gibbonPersonIDStudent'] = $gibbonPersonIDStudent;
    $data['courseSelectionOfferingID'] = $courseSelectionOfferingID ?? '';

    if (empty($data['gibbonSchoolYearID']) || empty($data['gibbonPersonIDStudent']) || empty($data['courseSelectionOfferingID'])) {
        $URL .= '&return=error1';
        header("Location: {$failURL}");
        exit;
    } else {
        $partialFail = false;
        $gateway = new SelectionsGateway($pdo);

        $insertID = $gateway->insertChoiceOffering($data);

        if (!empty($insertID)) {
            $URL .= '&return=error2';
            header("Location: {$failURL}");
            exit;
        } else {
            $URL .= "&return=success0";
            header("Location: {$URL}");
            exit;
        }
    }
}
