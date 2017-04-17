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

use Gibbon\Modules\CourseSelection\Domain\SettingsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');

$URL = $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/settings.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/settings.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $partialFail = false;
    $settingsGateway = new SettingsGateway($pdo);

    $requireApproval = $_POST['requireApproval'] ?? 'Y';
    $partialFail &= !$settingsGateway->update('Course Selection', 'requireApproval', $requireApproval);

    $infoTextOfferings = $_POST['infoTextOfferings'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'infoTextOfferings', $infoTextOfferings);

    $infoTextSelectionBefore = $_POST['infoTextSelectionBefore'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'infoTextSelectionBefore', $infoTextSelectionBefore);

    $infoTextSelectionAfter = $_POST['infoTextSelectionAfter'] ?? '';
    $partialFail &= !$settingsGateway->update('Course Selection', 'infoTextSelectionAfter', $infoTextSelectionAfter);

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
