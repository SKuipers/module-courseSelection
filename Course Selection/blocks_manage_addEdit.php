<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\CourseSelection\Domain\BlocksGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_addEdit.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $gateway = $container->get(BlocksGateway::class);

    $count = 0;

    $values = array(
        'courseSelectionBlockID' => '',
        'gibbonSchoolYearID'     => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
        'gibbonDepartmentIDList' => '',
        'name'                   => '',
        'description'            => '',
        'countable'              => 'Y',
    );

    $gibbonDepartmentIDList = '';

    if (isset($_GET['courseSelectionBlockID'])) {
        $result = $gateway->selectOne($_GET['courseSelectionBlockID']);
        if ($result && $result->rowCount() == 1) {
            $values = $result->fetch();

            $gibbonDepartmentIDList = explode(',', $values['gibbonDepartmentIDList']);
        }

        $action = 'edit';
        $actionName = __('Edit Block');
        $actionURL = $session->get('absoluteURL').'/modules/'.$session->get('module').'/blocks_manage_editProcess.php';
    } else {
        $action = 'add';
        $actionName = __('Add Block');
        $actionURL = $session->get('absoluteURL').'/modules/'.$session->get('module').'/blocks_manage_addProcess.php';
    }

	$page->breadcrumbs
		->add(__m('Manage Course Blocks'), 'blocks_manage.php')
		->add(__m($actionName));

    if (isset($_GET['return'])) {
        $editLink = (isset($_GET['editID']))? $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/blocks_manage_addEdit.php&courseSelectionBlockID='.$_GET['editID'] : '';
        $page->return->setEditLink($editLink);
    }

    $form = Form::create('blocksAddEdit', $actionURL);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('courseSelectionBlockID', $values['courseSelectionBlockID']);
    $form->addHiddenValue('address', $session->get('address'));

    if ($action == 'edit') {
        $form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID']);
        $row = $form->addRow();
            $row->addLabel('schoolYearName', __('School Year'));
            $row->addTextField('schoolYearName')->readonly()->setValue($values['schoolYearName']);
    } else {
        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearID', __('School Year'));
            $row->addSelectSchoolYear('gibbonSchoolYearID', 'Active')->required()->selected($values['gibbonSchoolYearID']);
    }

    $row = $form->addRow();
            $row->addLabel('gibbonDepartmentIDList', __('Departments'))->description(__('This determines courses available to add, and course marks associated with this block. Leave blank to select from any courses.'));
            $row->addSelectDepartment('gibbonDepartmentIDList')->selectMultiple()->selected($gibbonDepartmentIDList);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->required()->maxLength(90)->setValue($values['name']);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->maxLength(255)->setValue($values['description']);

    $row = $form->addRow();
        $row->addLabel('countable', __('Countable?'))->description(__('Should courses from this block be counted against the min and max selections for a course offering?'));
        $row->addYesNo('countable')->selected($values['countable']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($action == 'edit' && !empty($values['courseSelectionBlockID'])) {
        echo '<h3>';
        echo __('Manage Courses');
        echo '</h3>';

        $courses = $gateway->selectAllCoursesByBlock($values['courseSelectionBlockID']);

        // DATA TABLE
        $table = DataTable::create('courses');
        $table->setTitle(__('Manage Courses'));

        $table->addDraggableColumn('gibbonCourseID', $session->get('absoluteURL').'/modules/Course Selection/blocks_manage_orderAjax.php', ['courseSelectionBlockID' => $values['courseSelectionBlockID']]);

        $table->addColumn('courseNameShort', __('Short Name'));
        $table->addColumn('courseName', __('Name'));

        $table->addActionColumn()
            ->addParam('courseSelectionBlockID')
            ->addParam('gibbonCourseID')
            ->format(function ($values, $actions) {
                $actions->addAction('deleteDirect', __('Delete'))
                    ->setIcon('garbage')
                    ->setURL('/modules/Course Selection/blocks_manage_course_deleteProcess.php')
                    ->addConfirmation(__('Are you sure you want to delete this record? Unsaved changes will be lost.'))
                    ->directLink();
            });

        echo $table->render($courses->fetchAll());

        $form = Form::create('blocksCourseAdd', $session->get('absoluteURL').'/modules/'.$session->get('module').'/blocks_manage_course_addProcess.php');

        $form->addHiddenValue('courseSelectionBlockID', $values['courseSelectionBlockID']);
        $form->addHiddenValue('sequenceNumber', $gateway->getNextSequenceNumber($values['courseSelectionBlockID']));
        $form->addHiddenValue('address', $session->get('address'));

        if (!empty($values['gibbonDepartmentIDList'])) {
            $courseList = $gateway->selectAvailableCoursesByDepartments($values['courseSelectionBlockID'], $values['gibbonDepartmentIDList']);
        } else {
            $courseList = $gateway->selectAvailableCourses($values['courseSelectionBlockID']);
        }

        $row = $form->addRow();
            $row->addLabel('gibbonCourseID', __('Course'));
            $row->addSelect('gibbonCourseID')
                ->fromResults($courseList)
                ->required()
                ->selectMultiple();

        $row = $form->addRow();
            $row->addSubmit(__('Add'));

        echo $form->getOutput();
    }
}
