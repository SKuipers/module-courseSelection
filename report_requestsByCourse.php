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

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/report_requestsByCourse.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Total Requests by Course', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
    $sort = $_GET['sort'] ?? 'surname';
    $min = $_GET['min'] ?? '';
    $max = $_GET['max'] ?? '';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    echo '<h2>';
    echo __('Filter');
    echo '</h2>';

    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');

    $form->setClass('noIntBorder fullWidth');
    $form->addHiddenValue('q', '/modules/Course Selection/report_requestsByCourse.php');

    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray(array('nameShort' => __('Course Code'), 'name' => __('Course Name'), 'order' => __('Report Order'), 'count' => __('Requests')))->selected($sort);

    $row = $form->addRow();
        $row->addLabel('highlight', __('Target Range'))->description(__('Highlight courses outside this range.'));
        $column = $row->addColumn()->addClass('inline right');
            $column->addTextField('min')->setClass('shortWidth')->placeholder(__('Minimum'))->setValue($min);
            $column->addTextField('max')->setClass('shortWidth')->placeholder(__('Maximum'))->setValue($max);

    $row = $form->addRow();
        $row->addSubmit('Go');

    echo $form->getOutput();

    echo '<h2>';
    echo __('Report Data');
    echo '</h2>';

    $selectionsGateway = new SelectionsGateway($pdo);
    $courses = $selectionsGateway->selectChoiceCountsBySchoolYear($gibbonSchoolYearID, $sort);

    if ($courses->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {

        echo '<div class="paginationTop">';
        echo __('Records').': '.$courses->rowCount();
        echo '</div>';

        echo '<table class="fullWidth colorOddEven" cellspacing="0">';
        echo '<tr class="head">';
            echo '<th>';
                echo __('Course');
            echo '</th>';
            echo '<th>';
                echo __('Name');
            echo '</th>';
            echo '<th>';
                echo __('Requests');
            echo '</th>';
            echo '<th style="width: 80px;">';
                echo __('Actions');
            echo '</th>';
        echo '</tr>';

        $count = 0;
        while ($course = $courses->fetch()) {
            $trClass = '';

            if (!empty($min) && $course['count'] < $min) $trClass = 'warning';
            if (!empty($max) && $course['count'] > $max) $trClass = 'warning';

            echo '<tr class="'.$trClass.'">';
                echo '<td>'.$course['courseNameShort'].'</td>';
                echo '<td>'.$course['courseName'].'</td>';
                echo '<td>'.$course['count'].'</td>';

                echo '<td>';
                    // echo "<a href='".$_SESSION[$guid]['absoluteURL']."/index.php?q=/modules/".$_SESSION[$guid]['module']."/selectionChoices.php&sidebar=false&gibbonPersonIDStudent=".$student['gibbonPersonID']."&courseSelectionOfferingID=".$student['courseSelectionOfferingID']."'><img title='".__('View Course Selections')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/plus.png'/></a>";
                echo '</td>';
            echo '</tr>';

            $count++;
        }

        echo '</table>';


    }
}
