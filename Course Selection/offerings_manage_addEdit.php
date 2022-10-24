<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Tables\DataTable;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Module\CourseSelection\Domain\OfferingsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_addEdit.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $gateway = $container->get(OfferingsGateway::class);

    $values = array(
        'courseSelectionOfferingID' => '',
        'gibbonSchoolYearID'        => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
        'gibbonYearGroupIDList'     => '',
        'name'                      => '',
        'description'               => '',
        'minSelect'                 => '0',
        'maxSelect'                 => '1',
        'sequenceNumber'            => $gateway->getNextSequenceNumber()
    );

    if (isset($_GET['courseSelectionOfferingID'])) {
        $result = $gateway->selectOne($_GET['courseSelectionOfferingID']);
        if ($result && $result->rowCount() == 1) {
            $values = $result->fetch();
        }

        $action = 'edit';
        $actionName = __('Edit Course Offering');
        $actionURL = $session->get('absoluteURL').'/modules/'.$session->get('module').'/offerings_manage_editProcess.php';
    } else {
        $action = 'add';
        $actionName = __('Add Course Offering');
        $actionURL = $session->get('absoluteURL').'/modules/'.$session->get('module').'/offerings_manage_addProcess.php';
    }

	$page->breadcrumbs
		->add(__m('Manage Course Offerings'), 'offerings_manage.php')
		->add(__m($actionName));

    if (isset($_GET['return'])) {
        $editLink = (isset($_GET['editID']))? $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/offerings_manage_addEdit.php&courseSelectionOfferingID='.$_GET['editID'] : '';
        $page->return->setEditLink($editLink);
    }

    $form = Form::create('offeringsAddEdit', $actionURL);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('courseSelectionOfferingID', $values['courseSelectionOfferingID']);
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
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->required()->maxLength(90)->setValue($values['name']);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->maxLength(255)->setValue($values['description']);

    $row = $form->addRow();
        $row->addLabel('minSelect', __('Min Selections'))->description(__('Across all course blocks.'));
        $row->addNumber('minSelect')->required()->minimum(0)->maximum(100)->setValue($values['minSelect']);

    $row = $form->addRow();
        $row->addLabel('maxSelect', __('Max Selections'))->description(__('Across all course blocks.'));
        $row->addNumber('maxSelect')->required()->minimum(0)->maximum(100)->setValue($values['maxSelect']);

    $row = $form->addRow();
        $row->addLabel('sequenceNumber', __('Sequence Number'));
        $row->addNumber('sequenceNumber')->required()->minimum(0)->maximum(999)->setValue($values['sequenceNumber']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($action == 'edit' && !empty($values['courseSelectionOfferingID'])) {
        $blocks = $gateway->selectAllRestrictionsByOffering($values['courseSelectionOfferingID']);

        // DATA TABLE
        $table = DataTable::create('restrictions');
        $table->setTitle(__('Manage Enrolment Restrictions'));

        $table->addColumn('schoolYearName', __('School Year'));
        $table->addColumn('yearGroupName', __('Year Group'));

        $table->addActionColumn()
            ->addParam('courseSelectionOfferingID')
            ->addParam('courseSelectionOfferingRestrictionID')
            ->format(function ($values, $actions) {
                $actions->addAction('deleteDirect', __('Delete'))
                    ->setIcon('garbage')
                    ->setURL('/modules/Course Selection/offerings_manage_restriction_deleteProcess.php')
                    ->addConfirmation(__('Are you sure you want to delete this record? Unsaved changes will be lost.'))
                    ->directLink();
            });

        echo $table->render($blocks->fetchAll());

        $form = Form::create('offeringsRestrictionAdd', $session->get('absoluteURL').'/modules/'.$session->get('module').'/offerings_manage_restriction_addProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('courseSelectionOfferingID', $values['courseSelectionOfferingID']);
        $form->addHiddenValue('address', $session->get('address'));

        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearID', __('Student Enrolment'));
            $row->addSelectSchoolYear('gibbonSchoolYearID', 'Active')->required()->setClass('mediumWidth');
            $row->addSelectYearGroup('gibbonYearGroupID')->required()->setClass('mediumWidth');

        $row = $form->addRow();
            $row->addSubmit(__('Add'));

        echo $form->getOutput();

        // BLOCKS
        $blocks = $gateway->selectAllBlocksByOffering($values['courseSelectionOfferingID']);
        $blockList = $gateway->selectAvailableBlocksBySchoolYear($values['courseSelectionOfferingID'], $values['gibbonSchoolYearID']);

        // DATA TABLE
        $table = DataTable::create('blocks');
        $table->setTitle(__('Manage Blocks'));

        $table->addDraggableColumn('courseSelectionBlockID', $session->get('absoluteURL').'/modules/Course Selection/offerings_manage_block_orderAjax.php', ['courseSelectionOfferingID' => $values['courseSelectionOfferingID']]);
        $table->addColumn('blockName', __('Course Block'));
        $table->addColumn('courseCount', __('Courses'));
        $table->addColumn('minSelect', __('Min Selections'));
        $table->addColumn('maxSelect', __('Max Selections'));

        $table->addActionColumn()
            ->addParam('courseSelectionOfferingID')
            ->addParam('courseSelectionBlockID')
            ->format(function ($values, $actions) {
                $actions->addAction('deleteDirect', __('Delete'))
                    ->setIcon('garbage')
                    ->setURL('/modules/Course Selection/offerings_manage_block_deleteProcess.php')
                    ->addConfirmation(__('Are you sure you want to delete this record? Unsaved changes will be lost.'))
                    ->directLink();
            });

        echo $table->render($blocks->fetchAll());

        // FORM
        $form = Form::create('offeringsBlockAdd', $session->get('absoluteURL').'/modules/'.$session->get('module').'/offerings_manage_block_addProcess.php');

        $form->addHiddenValue('courseSelectionOfferingID', $values['courseSelectionOfferingID']);
        $form->addHiddenValue('address', $session->get('address'));

        $row = $form->addRow();
            $row->addLabel('courseSelectionBlockID', __('Course Block'));
            $row->addSelect('courseSelectionBlockID')
                ->fromResults($blockList)
                ->required()
                ->selectMultiple();

        $row = $form->addRow();
            $row->addLabel('minSelect', __('Min Selections'));
            $row->addNumber('minSelect')->required()->minimum(0)->maximum(100)->setValue(1);

        $row = $form->addRow();
            $row->addLabel('maxSelect', __('Max Selections'));
            $row->addNumber('maxSelect')->required()->minimum(0)->maximum(100)->setValue(1);

        $row = $form->addRow();
            $row->addSubmit(__('Add'));

        echo $form->getOutput();
    }
}
