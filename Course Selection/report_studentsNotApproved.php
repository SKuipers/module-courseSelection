<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use CourseSelection\Domain\SelectionsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/report_studentsNotApproved.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $page->breadcrumbs
    	->add(__m('Students Not Approved'));

	$settingGateway = $container->get(SettingGateway::class);
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $settingGateway->getSettingByScope('Course Selection', 'activeSchoolYear');
    $sort = $_GET['sort'] ?? 'surname';

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    echo '<p>';
    echo __("This report shows students who have completed the course selection process but their courses have not yet been approved.");
    echo '<p>';

    echo '<h2>';
    echo __('Filter');
    echo '</h2>';

    $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');

    $form->setClass('noIntBorder fullWidth');
    $form->addHiddenValue('q', '/modules/Course Selection/report_studentsNotApproved.php');

    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray(array('surname' => __('Surname'), 'formGroup' => __('Form Group'), 'approvalCount' => __('Approvals')))->selected($sort);

    $row = $form->addRow();
        $row->addSubmit('Go');

    echo $form->getOutput();

    $selectionsGateway = $container->get('CourseSelection\Domain\SelectionsGateway');

    // QUERY
    $criteria = $selectionsGateway->newQueryCriteria(true)
        ->pageSize(50)
        ->fromPOST();
    if ($sort == 'approvalCount') {
        $criteria->sortBy(['approvalCount'], 'DESC')
            ->sortBy(['gibbonFormGroup.nameShort', 'gibbonPerson.surname', 'gibbonPerson.preferredName']);
    } else if ($sort == 'formGroup') {
        $criteria->sortBy(['gibbonFormGroup.nameShort', 'gibbonPerson.surname', 'gibbonPerson.preferredName']);
    } else {
        $criteria->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName']);
    }

    $students = $selectionsGateway->queryStudentsWithIncompleteSelections($criteria, $gibbonSchoolYearID, $sort);

    // TABLE
    $table = DataTable::createPaginated('courses', $criteria);
    $table->setTitle(__('Report Data'));

    $table->modifyRows(function ($student, $row) {
        if ($student['approvalCount'] > 0) {
            $student['status'] = 'Partially Approved';
            return $row->addClass('dull');
        } else {
            $student['status'] = 'Partially Approved';
            return $row;
        }
    });

    $table->addColumn('name', __('Name'))
       ->format(Format::using('nameLinked', ['personIDStudent', '', 'preferredName', 'surname', 'Student', true]));

    $table->addColumn('formGroupName', __('Form Group'));
    $table->addColumn('choiceCount', __m('Course Selections'));
    $table->addColumn('approvalCount', __m('Approved'));

    $actions = $table->addActionColumn()
        ->addParam('sidebar', 'false')
        ->format(function ($student, $actions) use ($gibbonSchoolYearID) {
            $actions->addAction('view', __('View'))
                ->setURL('/modules/Course Selection/selectionChoices.php')
                ->addParam('gibbonPersonIDStudent', $student['gibbonPersonID'])
                ->addParam('courseSelectionOfferingID', $student['selectedOfferingID']);
            $actions->addAction('go', __('Go To Approval'))
                ->setURL('/modules/Course Selection/approval_byOffering.php', $student['gibbonPersonID'])
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->addParam('courseSelectionOfferingID', $student['selectedOfferingID'])
                ->setIcon('page_right');
        });

    echo $table->render($students);
}
