<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use CourseSelection\SchoolYearNavigation;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/upcomingTimetable.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Upcoming Timetable', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $highestGroupedAction = getHighestGroupedAction($guid, '/modules/Course Selection/upcomingTimetable.php', $connection2);

    if ($highestGroupedAction == 'Upcoming Timetable_all') {
        $gibbonPersonIDStudent = isset($_POST['gibbonPersonIDStudent'])? $_POST['gibbonPersonIDStudent'] : 0;

        $form = Form::create('selectStudent', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/upcomingTimetable.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDStudent', __('Student'));
            $row->addSelectStudent('gibbonPersonIDStudent', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonPersonIDStudent);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    } else if ($highestGroupedAction == 'Upcoming Timetable_my') {
        $gibbonPersonIDStudent = $_SESSION[$guid]['gibbonPersonID'];
    }

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    $nextSchoolYear = $navigation->selectNextSchoolYearByID($_SESSION[$guid]['gibbonSchoolYearID']);

    // Cancel out early if there's no valid student selected
    if (empty($nextSchoolYear) || empty($gibbonPersonIDStudent)) return;

    $timetableGateway = $container->get('CourseSelection\Domain\TimetableGateway');
    $courses = $timetableGateway->selectEnroledCoursesBySchoolYearAndStudent($nextSchoolYear['gibbonSchoolYearID'], $gibbonPersonIDStudent);

    if (!$courses || $courses->rowCount() == 0) {
        echo '<div class="error">';
        echo __('There are no records to display.');
        echo '</div>';
    } else {
        echo '<h3>';
        echo sprintf(__('Timetable Courses for %1$s'), $nextSchoolYear['name']);
        echo '</h3>';

        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
            echo '<th style="width: 14%;">';
                echo __('Day');
            echo '</th>';
            echo '<th style="width: 12%;">';
                echo __('Period');
            echo '</th>';
            echo '<th>';
                echo __('Course Name');
            echo '</th>';
            echo '<th>';
                echo __('Course Code');
            echo '</th>';
        echo '</tr>';

        while ($course = $courses->fetch()) {
            $dayShort = substr($course['className'], 0, 1);

            switch ($dayShort) {
                case 'A':   $dayName = 'Day 1'; break;
                case 'B':   $dayName = 'Day 2'; break;
                case '1':   $dayName = 'Semester 1'; break;
                case '2':   $dayName = 'Semester 2'; break;
            }

            echo '<tr>';
                echo '<td>'.$dayName.'</td>';
                echo '<td>'.$course['period'].'</td>';
                echo '<td>'.$course['courseName'].'</td>';
                echo '<td>'.$course['courseNameShort'].'.'.$course['className'].'</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}
