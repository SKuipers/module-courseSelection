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
use Gibbon\Modules\CourseSelection\SchoolYearNavigation;
use Gibbon\Modules\CourseSelection\Domain\ToolsGateway;
use Gibbon\Modules\CourseSelection\Domain\SelectionsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/approval_byClass.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Course Approval by Class', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $toolsGateway = new ToolsGateway($pdo);
    $selectionsGateway = new SelectionsGateway($pdo);

    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    $showRemoved = $_GET['showRemoved'] ?? 'N';

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    // SELECT COURSE
    $form = Form::create('courseApprovalByClass', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Course Selection/approval_byClass.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('sidebar', 'false');

    $courseResults = $toolsGateway->selectCoursesOfferedBySchoolYear($gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('gibbonCourseID', __('Course'));
        $row->addSelect('gibbonCourseID')->fromResults($courseResults)->isRequired()->selected($gibbonCourseID);

    $row = $form->addRow();
        $row->addLabel('showRemoved', __('Show removed selections?'));
        $row->addYesNo('showRemoved')->selected($showRemoved);

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();

    // LIST STUDENTS
    if (!empty($gibbonCourseID)) {
        $studentChoicesResults = $selectionsGateway->selectChoicesByCourse($gibbonCourseID, ($showRemoved == 'N')? array('Removed') : array());

        if ($studentChoicesResults->rowCount() == 0) {
            echo '<div class="error">';
            echo __("There are no records to display.") ;
            echo '</div>';
        } else {

            echo '<br/><p>';
            echo sprintf(__('Showing %1$s student course selections:'), $studentChoicesResults->rowCount());
            echo '</p>';

            echo '<table class="fullWidth colorOddEven" cellspacing="0">';

            echo '<tr class="head">';
                echo '<th>';
                    echo __('Student');
                echo '</th>';
                echo '<th>';
                    echo __('Status');
                echo '</th>';
                echo '<th>';
                    echo __('Selected By');
                echo '</th>';
                echo '<th style="width: 80px;">';
                    echo __('Actions');
                echo '</th>';
            echo '</tr>';

            while ($student = $studentChoicesResults->fetch()) {
                switch ($student['status']) {
                    case 'Removed': $class = 'error'; break;
                    case 'Approved': $class = 'success'; break;
                    default: $class = ''; break;
                }

                echo '<tr class="'.$class.'">';
                    echo '<td>';
                        echo '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'" target="_blank">';
                        echo formatName('', $student['preferredName'], $student['surname'], 'Student', true);
                        echo '</a>';
                    echo '</td>';

                    echo '<td>'.$student['status'].'</td>';
                    echo '<td>';

                        echo '<span title="'.date('M j, Y \a\t g:i a', strtotime($student['timestampSelected'])).'">';
                        if ($student['selectedPersonID'] == $student['gibbonPersonID']) {
                            echo 'Student';
                        } else {
                            echo formatName('', $student['selectedPreferredName'], $student['selectedSurname'], 'Student', false);
                        }
                        echo '</span>';
                    echo '</td>';
                    echo '<td>';
                        if (!empty($student['courseSelectionOfferingID'])) {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/selectionChoices.php&sidebar=false&gibbonPersonIDStudent=".$student['gibbonPersonID']."&courseSelectionOfferingID=".$student['courseSelectionOfferingID']."'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                        } else {
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/selection.php&sidebar=false&gibbonPersonIDStudent=".$student['gibbonPersonID']."'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                        }
                    echo '</td>';
                echo '</tr>';
            }

            // if ($count == 0) {
            //     echo '<tr>';
            //         echo '<td colspan="5">';

            //         echo '</td>';
            //     echo '</tr>';
            // }

            echo '</table>';


        }
    }
}
