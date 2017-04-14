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

use Modules\CourseSelection\Domain\AccessGateway;

// Autoloader & Module includes
$loader->addNameSpace('Modules\CourseSelection\\', 'modules/Course Selection/src/');

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/access_manage_addEdit.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/access_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $data = array();
    $data['gibbonSchoolYearID'] = $_POST['gibbonSchoolYearID'] ?? '';
    $data['dateStart'] = $_POST['dateStart'] ?? '';
    $data['dateEnd'] = $_POST['dateEnd'] ?? '';
    $data['accessType'] = $_POST['accessType'] ?? '';
    $data['gibbonRollGroupIDList'] = $_POST['gibbonRollGroupIDList'] ?? array();

    $data['dateStart'] = dateConvert($guid, $data['dateStart']);
    $data['dateEnd'] = dateConvert($guid, $data['dateEnd']);
    $data['gibbonRollGroupIDList'] = implode(',', $data['gibbonRollGroupIDList']);

    if (empty($data['gibbonSchoolYearID']) || empty($data['dateStart']) || empty($data['dateEnd']) || empty($data['accessType']) || empty($data['gibbonRollGroupIDList'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $gateway = new AccessGateway($pdo);

        $insertID = $gateway->insert($data);

        if (empty($insertID)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        } else {
            $URL .= "&return=success0&editID=$insertID";
            header("Location: {$URL}");
            exit;
        }
    }
}
