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

use Gibbon\Forms\Form;
use Gibbon\Modules\CourseSelection\SchoolYearNavigation;
use Gibbon\Modules\CourseSelection\Domain\SelectionsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/report_courseSelectionLog.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Activity Log', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    $selectionsGateway = new SelectionsGateway($pdo);

    $logs = $selectionsGateway->selectAllLogsBySchoolYear($gibbonSchoolYearID, 1, 50);

    if ($logs->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {
        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
            echo '<th>';
                echo __('Course Offering');
            echo '</th>';
            echo '<th>';
                echo __('Student');
            echo '</th>';
            echo '<th>';
                echo __('Type');
            echo '</th>';
            echo '<th>';
                echo __('Date');
            echo '</th>';
            echo '<th style="width: 80px;">';
                echo __('Actions');
            echo '</th>';
        echo '</tr>';

        while ($log = $logs->fetch()) {
            echo '<tr>';
                echo '<td>'.$log['offeringName'].'</td>';
                echo '<td>';
                    echo '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$log['gibbonPersonIDStudent'].'" target="_blank">';
                    echo formatName('', $log['studentPreferredName'], $log['studentSurname'], 'Student', true);
                    echo '</a>';
                echo '</td>';
                echo '<td>'.$log['action'].'</td>';
                echo '<td>';
                    echo date('M j, Y \a\t g:i a', strtotime($log['timestampChanged']));
                echo '</td>';
                echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/selectionChoices.php&sidebar=false&gibbonPersonIDStudent=".$log['gibbonPersonIDStudent']."&courseSelectionOfferingID=".$log['courseSelectionOfferingID']."'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";

                echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}
