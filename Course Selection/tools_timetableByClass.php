<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Domain\System\SettingGateway;
use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Services\Format;
use CourseSelection\SchoolYearNavigation;
use CourseSelection\Domain\ToolsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tools_timetableByClass.php') == false) {
    //Acess denied
    $page->addError(__('You do not have access to this action.'));
} else {
    $page->breadcrumbs->add(__('Edit Timetable by Class'));

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $toolsGateway = $container->get('CourseSelection\Domain\ToolsGateway');
    $settingGateway = $container->get(SettingGateway::class);

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $settingGateway->getSettingByScope('Course Selection', 'activeSchoolYear');
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);


    // SELECT TIMETABLE & CLASS
    $form = Form::create('timetableByClass', $gibbon->session->get('absoluteURL') . '/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Course Selection/tools_timetableByClass.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $timetableResults = $toolsGateway->selectTimetablesBySchoolYear($gibbonSchoolYearID);
    $classesResults = $toolsGateway->selectAllCourseClassesBySchoolYear($gibbonSchoolYearID);

    $classes = ($classesResults->rowCount() > 0)? $classesResults->fetchAll() : array();
    $classesChained = array_combine(array_column($classes, 'value'), array_column($classes, 'gibbonTTID'));
    $classesOptions = array_combine(array_column($classes, 'value'), array_column($classes, 'name'));

    $row = $form->addRow();
        $row->addLabel('gibbonTTID', __('Timetable'));
        $row->addSelect('gibbonTTID')
            ->fromResults($timetableResults)
            ->required()
            ->placeholder()
            ->selected($gibbonTTID);

    $row = $form->addRow();
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelect('gibbonCourseClassID')
            ->fromArray($classesOptions)
            ->required()
            ->placeholder()
            ->selected($gibbonCourseClassID);
            //->chainedTo('gibbonTTID', $classesChained);

    $row = $form->addRow();
        $row->addSubmit('Next');

    echo $form->getOutput();

    if (!empty($gibbonCourseClassID)) {
        $form = Form::create('ttAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/tools_timetableByClass_addProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        $form->addHiddenValue('gibbonTTID', $gibbonTTID);
        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $dayResults = $toolsGateway->selectTimetableDaysByTimetable($gibbonTTID);
        $columnResults = $toolsGateway->selectTimetableColumnsByTimetable($gibbonTTID);

        $columnRows = ($columnResults->rowCount() > 0)? $columnResults->fetchAll() : array();
        $columnRowsChained = array_combine(array_column($columnRows, 'value'), array_column($columnRows, 'gibbonTTDayID'));
        $columnRowsOptions = array_combine(array_column($columnRows, 'value'), array_column($columnRows, 'name'));

        $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
        $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
        $gibbonTTSpaceID = $_GET['gibbonTTSpaceID'] ?? '';

        $ttBlock = $form->getFactory()->createTable()->setClass('blank');
            $row = $ttBlock->addRow();
                $row->addLabel('gibbonTTDayID', __('Timetable Day'))->addClass('mx-1');
                $row->addSelect('gibbonTTDayID')
                    ->fromResults($dayResults)
                    ->required()
                    ->selected($gibbonTTDayID);

                $row->addLabel('gibbonTTColumnRowID', __('Timetable Column Row'))->addClass('mx-5');
                $row->addSelect('gibbonTTColumnRowID')
                    ->fromArray($columnRowsOptions)
                    ->required()
                    ->chainedTo('', $columnRowsChained)
                    ->addClass('chainTo')
                    ->selected($gibbonTTColumnRowID);

            $row = $ttBlock->addRow();
                $row->addLabel('gibbonTTSpaceID', __('Location'))->addClass('mx-1');
                $row->addSelectSpace('gibbonTTSpaceID')->selected($gibbonTTSpaceID);

        $addTTButton = $form->getFactory()->createButton(__('Add Timetable Entry'))->addClass('addBlock');

        $row = $form->addRow();
            $ttBlocks = $row->addCustomBlocks('ttBlocks', $gibbon->session)
                ->fromTemplate($ttBlock)
                ->settings([
                    'placeholder' => __('Timetable Entries will appear here.')
                ])
                ->addToolInput($addTTButton);

        $ttResults = $toolsGateway->selectTTDayRowClasses($gibbonCourseClassID, $gibbonTTID);

        while ($ttDay = $ttResults->fetch()) {
            $ttDay['gibbonTTColumnRowID'] .= '-' . $ttDay['gibbonTTDayID'];
            $ttDay['gibbonTTSpaceID'] = $ttDay['gibbonSpaceID'];
            $ttBlocks->addBlock($ttDay['gibbonTTDayRowClassID'], $ttDay);
        }

        $row = $form->addRow();
            $row->addSubmit(__('Submit'));

        echo $form->getOutput();

        $classResults = $toolsGateway->selectCourseClass($gibbonCourseClassID);
        $class = ($classResults->rowCount() > 0)? $classResults->fetch() : array();

        echo '<h4>';
            echo __('Rename Class');
        echo '</h4>';

        $form = Form::create('ttRenameClass', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/tools_timetableByClass_renameProcess.php');

        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        $form->addHiddenValue('gibbonTTID', $gibbonTTID);
        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('name', __('Name'));
            $row->addTextField('name')->required()->maxLength(12)->setValue($class['name']);

        $row = $form->addRow();
            $row->addLabel('nameShort', __('Short Name'));
            $row->addTextField('nameShort')->required()->maxLength(5)->setValue($class['nameShort']);

        $row = $form->addRow();
            $row->addSubmit(__('Rename'));

        echo $form->getOutput();
    }
    ?>
    <script>
        function chainSelects() {
             $('div.blocks').find('select.chainTo').each(function () {
                var index = $(this).attr('id').replace('gibbonTTColumnRowID' ,'');
                $(this).removeClass('chainTo').chainedTo('#gibbonTTDayID' + index);
            });
        }

        $(document).ready(chainSelects);

        $(document).on('click', '.addBlock', chainSelects);
    </script>
    <?php
}
