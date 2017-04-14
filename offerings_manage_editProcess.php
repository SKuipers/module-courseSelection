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

use Modules\CourseSelection\Domain\OfferingsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Modules\CourseSelection\\', 'modules/Course Selection/src/');

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/offerings_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $data = array();
    $data['courseSelectionOfferingID'] = $_POST['courseSelectionOfferingID'] ?? '';
    $data['gibbonSchoolYearID'] = $_POST['gibbonSchoolYearID'] ?? '';
    $data['gibbonYearGroupIDList'] = implode(',', $_POST['gibbonYearGroupIDList']) ?? '';
    $data['name'] = $_POST['name'] ?? '';
    $data['description'] = $_POST['description'] ?? '';
    $data['minSelect'] = $_POST['minSelect'] ?? '';
    $data['maxSelect'] = $_POST['maxSelect'] ?? '';
    $data['sequenceNumber'] = $_POST['sequenceNumber'] ?? '';

    if (empty($data['gibbonSchoolYearID']) || empty($data['gibbonYearGroupIDList']) || empty($data['name']) || empty($data['sequenceNumber'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $gateway = new OfferingsGateway($pdo);

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
