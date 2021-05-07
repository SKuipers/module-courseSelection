<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use CourseSelection\SchoolYearNavigation;
use CourseSelection\Domain\ToolsGateway;
use CourseSelection\Domain\SelectionsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/approval_byCourse.php') == false) {
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

    $toolsGateway = $container->get('CourseSelection\Domain\ToolsGateway');
    $selectionsGateway = $container->get('CourseSelection\Domain\SelectionsGateway');

    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
    
    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    // SELECT COURSE
    $form = Form::create('courseApprovalByCourse', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Course Selection/approval_byCourse.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('sidebar', 'false');

    $courseResults = $toolsGateway->selectCoursesOfferedBySchoolYear($gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('gibbonCourseID', __('Course'));
        $row->addSelect('gibbonCourseID')->fromResults($courseResults)->required()->selected($gibbonCourseID);

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();

    // LIST STUDENTS
    if (!empty($gibbonCourseID)) {
        $studentChoicesResults = $selectionsGateway->selectChoicesByCourse($gibbonCourseID, array('Removed'));

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
                    echo __('Form Group');
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
                    echo '<td>'.$student['formGroupName'].'</td>';
                    echo '<td>'.$student['status'];
                    if (!($student['blockIsCountable'] == 'Y' || empty($student['courseSelectionBlockID']))) {
                        echo ' <i>('.__('Alternate').')</i>';
                    }
                    echo '</td>';
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
                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/selectionChoices.php&sidebar=false&gibbonPersonIDStudent=".$student['gibbonPersonID']."&courseSelectionOfferingID=".$student['courseSelectionOfferingID']."'><img title='".__('View')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a> &nbsp;";

                            echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/approval_byOffering.php&sidebar=false&courseSelectionOfferingID=".$student['courseSelectionOfferingID']."&gibbonSchoolYearID=".$gibbonSchoolYearID."#".$student['gibbonPersonID']."'><img title='".__('Go to Approval')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/page_right.png'/></a>";
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
