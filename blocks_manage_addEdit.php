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
use Gibbon\Modules\CourseSelection\Domain\BlocksGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_addEdit.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $gateway = new BlocksGateway($pdo);

    $values = array(
        'courseSelectionBlockID' => '',
        'gibbonSchoolYearID'     => '',
        'gibbonDepartmentIDList' => '',
        'name'                   => '',
        'description'            => '',
        'minSelect'              => '0',
        'maxSelect'              => '1'
    );

    if (isset($_GET['courseSelectionBlockID'])) {
        $result = $gateway->selectOne($_GET['courseSelectionBlockID']);
        if ($result && $result->rowCount() == 1) {
            $values = $result->fetch();
        }

        $action = 'edit';
        $actionName = __('Edit Block');
        $actionURL = $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/blocks_manage_editProcess.php';
    } else {
        $action = 'add';
        $actionName = __('Add Block');
        $actionURL = $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/blocks_manage_addProcess.php';
    }

    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/blocks_manage.php'>".__('Manage Course Blocks', 'Course Selection')."</a> > </div><div class='trailEnd'>".$actionName.'</div>';
    echo "</div>" ;

    if (isset($_GET['return'])) {
        $editLink = (isset($_GET['editID']))? $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/blocks_manage_addEdit.php&courseSelectionBlockID='.$_GET['editID'] : '';
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('blocksAddEdit', $actionURL);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('courseSelectionBlockID', $values['courseSelectionBlockID']);
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    if ($action == 'edit') {
        $form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID']);
        $row = $form->addRow();
            $row->addLabel('schoolYearName', __('School Year'));
            $row->addTextField('schoolYearName')->readonly()->setValue($values['schoolYearName']);
    } else {
        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearID', __('School Year'));
            $row->addSelectSchoolYear('gibbonSchoolYearID', 'Active')->isRequired()->selected($values['gibbonSchoolYearID']);
    }

    $row = $form->addRow();
            $row->addLabel('gibbonDepartmentIDList', __('Departments'))->description(__('This determines courses available to add, and course marks associated with this block. Leave blank to select from any courses.'));
            $row->addSelectDepartment('gibbonDepartmentIDList')->selectMultiple()->selected($values['gibbonDepartmentIDList']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->isRequired()->maxLength(90)->setValue($values['name']);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->maxLength(255)->setValue($values['description']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($action == 'edit' && !empty($values['courseSelectionBlockID'])) {
        echo '<h3>';
        echo __('Manage Courses');
        echo '</h3>';

        $courses = $gateway->selectAllCoursesByBlock($values['courseSelectionBlockID']);

        if ($courses->rowCount() == 0) {
            echo '<div class="error">';
            echo __("There are no records to display.") ;
            echo '</div>';
        } else {
            echo '<table class="fullWidth colorOddEven" cellspacing="0">';

            echo '<tr class="head">';
                echo '<th>';
                    echo __('Short Name');
                echo '</th>';
                echo '<th>';
                    echo __('Name');
                echo '</th>';
                echo '<th style="width: 80px;">';
                    echo __('Actions');
                echo '</th>';
            echo '</tr>';

            while ($course = $courses->fetch()) {
                echo '<tr>';
                    echo '<td>'.$course['courseNameShort'].'</td>';
                    echo '<td>'.$course['courseName'].'</td>';
                    echo '<td>';
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/".$_SESSION[$guid]['module']."/blocks_manage_course_deleteProcess.php?courseSelectionBlockID=".$course['courseSelectionBlockID']."&gibbonCourseID=".$course['gibbonCourseID']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                echo '</tr>';
            }

            echo '</table>';
        }

        $form = Form::create('blocksCourseAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/blocks_manage_course_addProcess.php');

        $form->addHiddenValue('courseSelectionBlockID', $values['courseSelectionBlockID']);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        if (!empty($values['gibbonDepartmentIDList'])) {
            $courseList = $gateway->selectAvailableCoursesByDepartments($values['courseSelectionBlockID'], $values['gibbonDepartmentIDList']);
        } else {
            $courseList = $gateway->selectAvailableCourses($values['courseSelectionBlockID']);
        }

        $row = $form->addRow();
            $row->addLabel('gibbonCourseID', __('Course'));
            $row->addSelect('gibbonCourseID')
                ->fromResults($courseList)
                ->isRequired()
                ->selectMultiple();

        $row = $form->addRow();
            $row->addSubmit(__('Add'));

        echo $form->getOutput();
    }
}
