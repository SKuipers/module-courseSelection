<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use CourseSelection\Domain\MetaDataGateway;
use CourseSelection\Domain\ToolsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/meta_manage_addEdit.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $metaDataGateway = $container->get('CourseSelection\Domain\MetaDataGateway');
    $toolsGateway = $container->get('CourseSelection\Domain\ToolsGateway');

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

    $values = array(
        'courseSelectionMetaDataID' => '',
        'gibbonCourseID'            => '',
        'enrolmentGroup'            => '',
        'timetablePriority'         => '',
        'tags'                      => '',
        'excludeClasses'            => '',
    );

    if (isset($_GET['courseSelectionMetaDataID'])) {
        $result = $metaDataGateway->selectOne($_GET['courseSelectionMetaDataID']);
        if ($result && $result->rowCount() == 1) {
            $values = $result->fetch();
        }

        $actionName = __('Edit Meta Data');
        $actionURL = $session->get('absoluteURL').'/modules/'.$session->get('module').'/meta_manage_editProcess.php';
    } else {
        $actionName = __('Add Meta Data');
        $actionURL = $session->get('absoluteURL').'/modules/'.$session->get('module').'/meta_manage_addProcess.php';
    }

    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='".$session->get('absoluteURL')."'>".__('Home')."</a> > <a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.getModuleName($_GET['q'])."/meta_manage.php'>".__('Manage Meta Data')."</a> > </div><div class='trailEnd'>".$actionName.'</div>';
    echo "</div>" ;

    if (isset($_GET['return'])) {
        $editLink = (isset($_GET['editID']))? $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/meta_manage_addEdit.php&courseSelectionMetaDataID='.$_GET['editID'] : '';
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('metaAddEdit', $actionURL);

    $form->addHiddenValue('courseSelectionMetaDataID', $values['courseSelectionMetaDataID']);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
    $form->addHiddenValue('address', $session->get('address'));

    $courseResults = $toolsGateway->selectCoursesOfferedBySchoolYear($gibbonSchoolYearID);

    if (!empty($values['gibbonCourseID'])) {
        $form->addHiddenValue('gibbonCourseID', $values['gibbonCourseID']);

        $courses = $courseResults->fetchAll(\PDO::FETCH_KEY_PAIR);
        $courseName = $courses[$values['gibbonCourseID']];

        $row = $form->addRow()->addClass('courseEnrolment');
            $row->addLabel('gibbonCourseName', __('Course'));
            $row->addTextField('gibbonCourseName')->readonly()->setValue($courseName);
    } else {
        $row = $form->addRow()->addClass('courseEnrolment');
            $row->addLabel('gibbonCourseID', __('Course'));
            $row->addSelect('gibbonCourseID')->fromResults($courseResults)->required();
    }


    $row = $form->addRow();
        $row->addLabel('timetablePriority', __('Timetable Priority'))->description(__('Helps determine the priority of a course when auto-resolving timetabling conflicts. '));
        $row->addTextField('timetablePriority')->setValue($values['timetablePriority']);

    $row = $form->addRow();
        $row->addLabel('enrolmentGroup', __('Enrolment Group'))->description(__('The timetabling engine will group student enrolment counts for classes together that share the same timetable period and enrolment group.'));
        $row->addTextField('enrolmentGroup')->setValue($values['enrolmentGroup']);

    $row = $form->addRow();
        $row->addLabel('tags', __('Tags'))->description(__('Comma-separated values. Tagged courses are counted during the course selection approval process.'));
        $row->addTextField('tags')->setValue($values['tags']);

    if (!empty($values['gibbonCourseID'])) {
        $classResults = $toolsGateway->selectClassesByCourse($values['gibbonCourseID']);
        $excludeClassesSelected = explode(',', $values['excludeClasses']);

        $row = $form->addRow();
            $row->addLabel('excludeClasses', __('Exclude Classes'))->description(__('Any classes selected here will be omitted from the timetabling engine.'));
            $row->addSelect('excludeClasses')->fromResults($classResults)->selectMultiple()->selected($excludeClassesSelected);
    }

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
