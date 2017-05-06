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

use CourseSelection\Domain\OfferingsGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');

$courseSelectionOfferingID = $_GET['courseSelectionOfferingID'] ?? '';

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/offerings_manage_addEdit.php&courseSelectionOfferingID='.$courseSelectionOfferingID;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $courseSelectionBlockID = $_GET['courseSelectionBlockID'] ?? '';

    if (empty($courseSelectionOfferingID) || empty($courseSelectionBlockID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {
        $gateway = new OfferingsGateway($pdo);

        $deleted = $gateway->deleteBlock($courseSelectionOfferingID, $courseSelectionBlockID);

        if ($deleted == false) {
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
