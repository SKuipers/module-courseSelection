<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use CourseSelection\Domain\AccessGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/access_manage_addEdit.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $gateway = $container->get('CourseSelection\Domain\AccessGateway');

    $values = array(
        'courseSelectionAccessID' => '',
        'gibbonSchoolYearID'      => $_REQUEST['gibbonSchoolYearID'] ?? $session->get('gibbonSchoolYearID'),
        'dateStart'               => '',
        'dateEnd'                 => '',
        'accessType'              => '',
        'gibbonRoleIDList'   => ''
    );

    if (isset($_GET['courseSelectionAccessID'])) {
        $result = $gateway->selectOne($_GET['courseSelectionAccessID']);
        if ($result && $result->rowCount() == 1) {
            $values = $result->fetch();
        }

        $actionName = __('Edit Access');
        $actionURL = $session->get('absoluteURL').'/modules/'.$session->get('module').'/access_manage_editProcess.php';
    } else {
        $actionName = __('Add Access');
        $actionURL = $session->get('absoluteURL').'/modules/'.$session->get('module').'/access_manage_addProcess.php';
    }

    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='".$session->get('absoluteURL')."'>".__('Home')."</a> > <a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['q'])."/access_manage.php'>".__('Course Selection Access')."</a> > </div><div class='trailEnd'>".$actionName.'</div>';
    echo "</div>" ;

    if (isset($_GET['return'])) {
        $editLink = (isset($_GET['editID']))? $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/access_manage_addEdit.php&courseSelectionAccessID='.$_GET['editID'] : '';
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('accessAddEdit', $actionURL);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('courseSelectionAccessID', $values['courseSelectionAccessID']);
    $form->addHiddenValue('address', $session->get('address'));

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearID', __('School Year'));
        $row->addSelectSchoolYear('gibbonSchoolYearID', 'Active')->required()->selected($values['gibbonSchoolYearID']);

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->required()->setValue(dateConvertBack($guid, $values['dateStart']));

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->required()->setValue(dateConvertBack($guid, $values['dateEnd']));

    $row = $form->addRow();
        $row->addLabel('accessType', __('Access Type'));
        $row->addSelect('accessType')->fromArray(array(
                'View' => __('View'),
                'Request' => __('Request Courses (approval)'),
                'Select' => __('Select Courses (no approval)')
            ))->required()->selected($values['accessType']);

    $roleResults = $gateway->getAccessRolesWithSelectionPermission();
    $roles = ($roleResults && $roleResults->rowCount() > 0)? $roleResults->fetchAll(\PDO::FETCH_KEY_PAIR) : array();

    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDList', __('Roles'))->description(__('Available to roles with access to Course Selection page.'));
        $row->addSelect('gibbonRoleIDList')
            ->fromArray($roles)
            ->required()
            ->selectMultiple()
            ->placeholder(null)
            ->selected(explode(',', $values['gibbonRoleIDList']));

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
