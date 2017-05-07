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

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tt_resultsByCourse.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('View Results by Course', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
    $sort = $_GET['sort'] ?? 'surname';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    $timetableGateway = new TimetableGateway($pdo);
    $classResults = $timetableGateway->selectCourseResultsBySchoolYear($gibbonSchoolYearID, $sort);

    if (!$classResults || $classResults->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {
        echo '<h2>';
        echo __('Filter');
        echo '</h2>';

        $form = Form::create('resultsByCourse', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

        $form->setClass('noIntBorder fullWidth');
        $form->addHiddenValue('q', '/modules/Course Selection/tt_resultsByCourse.php');

        $row = $form->addRow();
            $row->addLabel('sort', __('Sort By'));
            $row->addSelect('sort')->fromArray(array('nameShort' => __('Course Code'), 'name' => __('Course Name'), 'order' => __('Report Order'), 'count' => __('Students')))->selected($sort);

        $row = $form->addRow();
            $row->addSubmit('Go');

        echo $form->getOutput();

        echo '<h2>';
        echo __('Report Data');
        echo '</h2>';

        echo '<div class="paginationTop">';
        echo __('Records').': '.$classResults->rowCount();
        echo '</div>';

        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
            echo '<th>';
                echo __('Course');
            echo '</th>';
            echo '<th>';
                echo __('Class');
            echo '</th>';
            echo '<th>';
                echo __('Students');
            echo '</th>';
            echo '<th>';
                echo __('Gender Balance');
            echo '</th>';
            echo '<th style="width: 80px;">';
                echo __('Actions');
            echo '</th>';
        echo '</tr>';

        while ($class = $classResults->fetch()) {
            $rowClass = ($class['students'] < 8)? 'dull' : '';
            echo '<tr class="'.$rowClass.'">';
                echo '<td>'.$class['courseName'].'</td>';
                echo '<td>'.$class['courseNameShort'].'.'.$class['classNameShort'].'</td>';
                echo '<td>'.$class['students'].'</td>';
                echo '<td>';
                    if ($class['students'] > 0) {
                    $balance = (($class['studentsFemale'] / $class['students']) * 100.0);
                        echo '<div class="progressBar fill" style="width:100%" title="'.__('Male').' '.$class['studentsMale'].'">';
                            echo '<div class="complete" style="width:'.$balance.'%;" title="'.__('Female').' '.$class['studentsFemale'].'"></div>';
                    }
                    echo '</div>';
                echo '</td>';
                echo '<td>';

                echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}
