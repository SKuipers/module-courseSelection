<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use CourseSelection\Domain\TimetableGateway;
use CourseSelection\SchoolYearNavigation;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tt_resultsByStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('View Results by Student', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
    $gibbonCourseID = $_REQUEST['gibbonCourseID'] ?? null;

    $sort = $_GET['sort'] ?? 'surname';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    $timetableGateway = $container->get('CourseSelection\Domain\TimetableGateway');
    $studentResults = $timetableGateway->selectStudentResultsBySchoolYear($gibbonSchoolYearID, $sort, $gibbonCourseID);

    if (!$studentResults || $studentResults->rowCount() == 0) {
        echo '<div class="error">';
        echo __('There are no records to display.') ;
        echo '</div>';
    } else {
        echo '<h2>';
        echo __('Filter');
        echo '</h2>';

        $form = Form::create('resultsByStudent', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

        $form->setClass('noIntBorder fullWidth');
        $form->addHiddenValue('q', '/modules/Course Selection/tt_resultsByStudent.php');
        $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);

        $row = $form->addRow();
            $row->addLabel('sort', __('Sort By'));
            $row->addSelect('sort')->fromArray(array('surname' => __('Surname'), 'rollGroup' => __('Roll Group'), 'count' => __('Classes'), 'count' => __('Issues'), 'weight' => __('Weight')))->selected($sort);

        $row = $form->addRow();
            $row->addSubmit('Go');

        echo $form->getOutput();

        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        $students = $studentResults->fetchAll(\PDO::FETCH_GROUP);

        echo '<div class="paginationTop">';
        echo __('Records').': '.count($students);
        echo '</div>';

        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
            echo '<th width="20%">';
                echo __('Name');
            echo '</th>';
            echo '<th>';
                echo __('Grade');
            echo '</th>';
            echo '<th style="width:50%">';
                echo __('Classes');
            echo '</th>';
            echo '<th>';
                echo __('Weight');
            echo '</th>';
            echo '<th>';
                echo __('Issues');
            echo '</th>';
        echo '</tr>';

        foreach ($students as $studentClasses) {
            $rowClass = '';

            $student = current($studentClasses);

            echo '<tr class="'.$rowClass.'">';
                echo '<td>';
                    echo '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$student['gibbonPersonID'].'" target="_blank">';
                    echo formatName('', $student['preferredName'], $student['surname'], 'Student', true);
                    echo '</a>';
                echo '</td>';

                echo '<td>'.$student['rollGroupName'].'</td>';
                echo '<td>';

                $conflictCount = 0;
                if (!empty($studentClasses) && !empty($student['classNameShort'])) {
                    usort($studentClasses, function($a, $b) { return strnatcmp($a['classNameShort'], $b['classNameShort']); } );

                    foreach ($studentClasses as $class) {
                        $status = ($class['status'] != 'Complete')? 'Conflict' : '';

                        echo '<div class="courseChoiceContainer" data-status="'.$status.'">';
                        echo '<span style="width:35px;">'.$class['classNameShort'].'</span>';
                        echo '<span title="'.$class['courseNameShort'].'.'.$class['classNameShort'].'">';
                            echo $class['courseName'];
                        echo '</span>';

                        if ($status != '') {
                            echo '<span class="pullRight courseTag small emphasis" title="'.$class['reason'].'">'.__($class['flag']).'</span>';
                        }
                        echo '</div>';

                        $conflictCount += (!empty($class['flag']))? 1 : 0;
                    }
                }

                $incompleteResults = $timetableGateway->selectIncompleteResultsBySchoolYearAndStudent($gibbonSchoolYearID, $student['gibbonPersonID'], $gibbonCourseID);
                if ($incompleteResults && $incompleteResults->rowCount() > 0) {
                    while ($class = $incompleteResults->fetch()) {
                        echo '<div class="courseChoiceContainer" data-status="Failed" title="'.$class['courseNameShort'].'">';
                        echo '<span style="width:35px; display:inline-block;"></span>';
                        echo $class['courseName'];

                        echo '<span class="pullRight courseTag small emphasis">'.__('Failed!').'</span>';
                        echo '</div>';
                    }
                }

                echo '</td>';
                echo '<td>'.$student['weight'].'</td>';
                echo '<td>'.$conflictCount.'</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

}
