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
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Modules\CourseSelection\Domain\ToolsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tools_selectByStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Manual Course Selection', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $toolsGateway = new ToolsGateway($pdo);

    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';

    if ($gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    } else {
        if (empty($gibbonSchoolYearID)) {
            $gibbonSchoolYearID = getNextSchoolYearID($_SESSION[$guid]['gibbonSchoolYearID'], $connection2);
        }

        $schoolYearResults = $toolsGateway->selectSchoolYear($gibbonSchoolYearID);
        if ($schoolYearResults->rowCount() > 0) {
            $row = $schoolYearResults->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if (empty($gibbonSchoolYearID)) {
        echo "<div class='error'>";
        echo __($guid, 'The specified record does not exist.');
        echo '</div>';
        return;
    }

    echo '<h2>';
    echo $gibbonSchoolYearName;
    echo '</h2>';

    echo "<div class='linkTop'>";
        //Print year picker
        $previousYear = getPreviousSchoolYearID($gibbonSchoolYearID, $connection2);
        $nextYear = getNextSchoolYearID($gibbonSchoolYearID, $connection2);
        if (!empty($previousYear)) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tools_selectByStudent.php&gibbonSchoolYearID='.$previousYear."'>".__($guid, 'Previous Year').'</a> ';
        } else {
            echo __($guid, 'Previous Year').' ';
        }
        echo ' | ';
        if (!empty($nextYear)) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tools_selectByStudent.php&gibbonSchoolYearID='.$nextYear."'>".__($guid, 'Next Year').'</a> ';
        } else {
            echo __($guid, 'Next Year').' ';
        }
    echo '</div>';

    // SELECT COURSE
    $form = Form::create('selectByStudent', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tools_selectByStudentProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDStudent', __('Student'));
        $row->addSelectStudent('gibbonPersonIDStudent')->isRequired();

    $courses = array();
    $courseResults = $toolsGateway->selectAllCoursesBySchoolYear($gibbonSchoolYearID);
    if ($courseResults && $courseResults->rowCount() > 0) {
        while ($row = $courseResults->fetch()) {
            $courses[$row['grouping']][$row['value']] = $row['name'];
        }
    }

    $row = $form->addRow();
        $row->addLabel('gibbonCourseID', __('Course'));
        $row->addSelect('gibbonCourseID')->fromArray($courses)->isRequired();

    $row = $form->addRow();
            $row->addLabel('status', __('Selection Status'));
            $row->addSelect('status')->fromArray(array('Required', 'Recommended', 'Selected', 'Approved', 'Requested', 'Removed'))->isRequired();

        $row = $form->addRow();
            $row->addLabel('overwrite', __('Overwrite?'))->description(__('Replace the course selection status if one already exists for that student and course.'));
            $row->addYesNo('overwrite')->isRequired()->selected('Y');

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();


}
