<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use CourseSelection\SchoolYearNavigation;
use CourseSelection\Domain\SelectionsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/report_studentsNotSelected.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Students Not Selected', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
    $sort = $_GET['sort'] ?? 'surname';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    echo '<p>';
    echo __("This report shows students who have not completed the course selection process. Completion is determined by the minimum selections required for each course offering.");
    echo '<p>';

    echo '<h2>';
    echo __('Filter');
    echo '</h2>';

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setClass('noIntBorder fullWidth');
    $form->addHiddenValue('q', '/modules/Course Selection/report_studentsNotSelected.php');

    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray(array('surname' => __('Surname'), 'rollGroup' => __('Roll Group'), 'choiceCount' => __('Selections')))->selected($sort);

    $row = $form->addRow();
        $row->addSubmit('Go');

    echo $form->getOutput();

    echo '<h2>';
    echo __('Report Data');
    echo '</h2>';

    $selectionsGateway = $container->get('CourseSelection\Domain\SelectionsGateway');

    $students = $selectionsGateway->selectStudentsWithIncompleteSelections($gibbonSchoolYearID, $sort);

    if ($students->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {
        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
            echo '<th>';
                echo __('Student');
            echo '</th>';
            echo '<th>';
                echo __('Roll Group');
            echo '</th>';
            echo '<th>';
                echo __('Course Selections');
            echo '</th>';
            echo '<th>';
                echo __('Status');
            echo '</th>';
            echo '<th style="width: 80px;">';
                echo __('Actions');
            echo '</th>';
        echo '</tr>';

        $count = 0;
        while ($student = $students->fetch()) {

            // Skip complete and/or approved selections
            if ($student['choiceCount'] >= $student['minSelect'] && $student['minSelect'] > 0) continue;
            if ($student['approvalCount'] >= $student['choiceCount'] && $student['approvalCount'] > 0) continue;

            $status = 'In Progress';
            $rowClass = '';

            if (empty($student['selectedOfferingID'])) {
                $status = 'Not Started';
                $rowClass = 'dull';
            } else if ($student['choiceCount'] >= $student['minSelect']) {
                if ($student['approvalCount'] >= $student['choiceCount']) {
                    $status = 'Approved';
                    $rowClass = 'current';
                } else {
                    $status = 'Complete';
                    $rowClass = 'message';
                }
            }

            echo '<tr class="'.$rowClass.'">';
                echo '<td>';
                    echo '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'" target="_blank">';
                    echo formatName('', $student['preferredName'], $student['surname'], 'Student', true);
                    echo '</a>';
                echo '</td>';

                echo '<td>'.$student['rollGroupName'].'</td>';
                echo '<td><span title="Min: '.$student['minSelect'].' Max: '.$student['maxSelect'].'">'.$student['choiceCount'].'</span></td>';
                echo '<td>';
                if (!empty($student['selectedOfferingName'])) {
                    echo '<span title="'.__('Offering').': '.$student['selectedOfferingName'].'">'.$status.'</span>';
                } else {
                    echo $status;
                }
                echo '</td>';
                echo '<td>';
                    echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/selectionChoices.php&sidebar=false&gibbonPersonIDStudent=".$student['gibbonPersonID']."&courseSelectionOfferingID=".$student['courseSelectionOfferingID']."'><img title='".__('View Course Selections')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                echo '</td>';
            echo '</tr>';

            $count++;
        }

        echo '</table>';

        echo '<div class="paginationBottom">';
        echo __('Records').': '.$count;
        echo '</div>';
    }
}
