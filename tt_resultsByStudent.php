<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use CourseSelection\Domain\TimetableGateway;
use CourseSelection\SchoolYearNavigation;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

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
    $sort = $_GET['sort'] ?? 'surname';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    $timetableGateway = new TimetableGateway($pdo);
    $studentResults = $timetableGateway->selectStudentResultsBySchoolYear($gibbonSchoolYearID, $sort);

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

        $row = $form->addRow();
            $row->addLabel('sort', __('Sort By'));
            $row->addSelect('sort')->fromArray(array('surname' => __('Surname'), 'rollGroup' => __('Roll Group'), 'count' => __('Classes')))->selected($sort);

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
            echo '<th>';
                echo __('Classes');
            echo '</th>';
            echo '<th>';
                echo __('Weight');
            echo '</th>';
            echo '<th>';
                echo __('Flags');
            echo '</th>';
            echo '<th style="width: 80px;">';
                echo __('Actions');
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

                if (!empty($studentClasses) && !empty($student['classNameShort'])) {
                    usort($studentClasses, function($a, $b) { return strnatcmp($a['classNameShort'], $b['classNameShort']); } );
                    $conflicts = array_count_values(array_column($studentClasses, 'classNameShort'));

                    foreach ($studentClasses as $class) {
                        $status = ($conflicts[$class['classNameShort']] > 1)? 'Conflict' : '';

                        echo '<div class="courseChoiceContainer" data-status="'.$status.'" title="'.$class['courseNameShort'].'.'.$class['classNameShort'].'">';
                        echo '<span style="width:35px; display:inline-block;">'.$class['classNameShort'].'</span>';
                        echo $class['courseName'];

                        if ($status != '') {
                            echo '<span class="pullRight courseTag small emphasis">'.__($status).'</span>';
                        }
                        echo '</div>';
                    }
                }
                echo '</td>';
                echo '<td>'.$student['weight'].'</td>';

                echo '<td>';
                echo '</td>';

                echo '<td>';
                echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }

}
