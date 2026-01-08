<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\CourseSelection\Domain\ToolsGateway;
use Gibbon\Module\CourseSelection\Domain\SelectionsGateway;

// Module includes
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/approval_byCourse.php') == false) {
    // Access denied
    $page->addError(__('You do not have access to this action.'));
} else {
	$page->breadcrumbs
         ->add(__m('Course Approval by Class'));

    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    
    $toolsGateway = $container->get(ToolsGateway::class);
    $selectionsGateway = $container->get(SelectionsGateway::class);
	$settingGateway = $container->get(SettingGateway::class);

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $settingGateway->getSettingByScope('Course Selection', 'activeSchoolYear');

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    // SELECT COURSE
    $form = Form::create('filter', $session->get('absoluteURL') . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));
    $form->setClass('noIntBorder w-full');
    $form->setTitle(__m('Choose Course'));

    $form->addHiddenValue('q', '/modules/Course Selection/approval_byCourse.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('sidebar', 'false');

    $courseResults = $toolsGateway->selectCoursesOfferedBySchoolYear($gibbonSchoolYearID);

    $row = $form->addRow();
        $row->addLabel('gibbonCourseID', __('Course'));
        $row->addSelect('gibbonCourseID')->fromResults($courseResults)->required()->selected($gibbonCourseID)->placeholder();

    $row = $form->addRow();
       $row->addSearchSubmit($session);

    echo $form->getOutput();

    // LIST STUDENTS
    if (!empty($gibbonCourseID)) {
        // QUERY
        $criteria = $selectionsGateway->newQueryCriteria(true)
            ->sortBy(['gibbonPerson.surname', 'gibbonPerson.preferredName'])
            ->pageSize(50)
            ->fromPOST();

        $studentChoicesResults = $selectionsGateway->queryChoicesByCourse($criteria, $gibbonCourseID, array('Removed'));

        $table = DataTable::create('requests');
        $table->setTitle(__m('Course Requests'));

        $table->addColumn('name', __('Name'))
           ->format(Format::using('nameLinked', ['gibbonPersonID', '', 'preferredName', 'surname', 'Student']));

        $table->addColumn('formGroupName', __('Form Group'));

        $table->addColumn('status', __('Status'))
            ->format(function ($values) {
                $return = $values['status'];

                if (!($values['blockIsCountable'] == 'Y' || empty($values['courseSelectionBlockID']))) {
                    $return .= ' <i>('.__('Alternate').')</i>';
                }

                return $return;
            });

            $table->addColumn('selectedBy', __('Selected By'))
                ->format(function ($values) {
                    $return = '<span title="'.date('M j, Y \a\t g:i a', strtotime($values['timestampSelected'])).'">';
                    if ($values['selectedPersonID'] == $values['gibbonPersonID']) {
                        $return .= 'Student';
                    } else {
                        $return .= Format::name('', $values['selectedPreferredName'], $values['selectedSurname'], 'Student', false);
                    }
                    $return .= '</span>';

                    return $return;
                });

            $actions = $table->addActionColumn()
                ->addParam('courseSelectionOfferingID')
                ->addParam('sidebar', 'false')
                ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
                ->format(function($values, $actions) use ($session) {
                   if (!empty($values['courseSelectionOfferingID'])) {
                       $actions->addAction('view', __('View'))
                           ->setURL('/modules/' . $session->get('module') . '/selectionChoices.php')
                           ->addParam('gibbonPersonIDStudent', $values['gibbonPersonID'])
                           ->addParam('courseSelectionOfferingID', $values['courseSelectionOfferingID']);

                       $actions->addAction('page_right', __('Go to Approval'))
                           ->setURL('/modules/' . $session->get('module') . '/approval_byOffering.php', $values['gibbonPersonID'])
                           ->addParam('gibbonPersonIDStudent', $values['gibbonPersonID'])
                           ->addParam('courseSelectionOfferingID', $values['courseSelectionOfferingID'])
                           ->setIcon('page_right');
                    } else {
                       $actions->addAction('view', __('View'))
                           ->setURL('/modules/' . $session->get('module') . '/selection.php')
                           ->addParam('gibbonPersonIDStudent', $values['gibbonPersonID']);
                   }
               });

        echo $table->render($studentChoicesResults);
    }
}
