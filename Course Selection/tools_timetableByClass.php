<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use CourseSelection\SchoolYearNavigation;
use CourseSelection\Domain\ToolsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tools_timetableByClass.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $session->get('absoluteURL') . "'>" . __($guid, "Home") . "</a> > <a href='" . $session->get('absoluteURL') . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Edit Timetable by Class', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $toolsGateway = $container->get('CourseSelection\Domain\ToolsGateway');

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
    $gibbonCourseClassID = $_GET['gibbonCourseClassID'] ?? '';
    $gibbonTTID = $_GET['gibbonTTID'] ?? '';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);


    // SELECT TIMETABLE & CLASS
    $form = Form::create('timetableByClass', $session->get('absoluteURL').'/index.php', 'get');
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
        $row->addSelect('gibbonTTID')->fromResults($timetableResults)->required()->placeholder()->selected($gibbonTTID);

    $row = $form->addRow();
        $row->addLabel('gibbonCourseClassID', __('Class'));
        $row->addSelect('gibbonCourseClassID')
            ->fromArray($classesOptions)
            ->required()
            ->placeholder()
            ->selected($gibbonCourseClassID);;
            //->chainedTo('gibbonTTID', $classesChained);;


    $row = $form->addRow();
        $row->addSubmit('Next');

    echo $form->getOutput();

    if (!empty($gibbonCourseClassID)) {
        $ttResults = $toolsGateway->selectTimetableDaysByClass($gibbonCourseClassID, $gibbonTTID);

        if (!$ttResults || $ttResults->rowCount() == 0) {
            echo '<div class="error">';
            echo __("There are no records to display.") ;
            echo '</div>';
        } else {
            echo '<table class="fullWidth colorOddEven" cellspacing="0">';

            echo '<tr class="head">';
                echo '<th>';
                    echo __('Timetable');
                echo '</th>';
                echo '<th>';
                    echo __('Day');
                echo '</th>';
                echo '<th>';
                    echo __('Column Row');
                echo '</th>';
                echo '<th>';
                    echo __('Space');
                echo '</th>';
                echo '<th style="width: 80px;">';
                    echo __('Actions');
                echo '</th>';
            echo '</tr>';

            while ($ttDay = $ttResults->fetch()) {
                echo '<tr>';
                    echo '<td>'.$ttDay['ttName'].'</td>';
                    echo '<td>'.$ttDay['dayName'].'</td>';
                    echo '<td>'.$ttDay['columnName'].'</td>';
                    echo '<td>'.$ttDay['spaceName'].'</td>';
                    echo '<td>';
                        echo "<a href='".$session->get('absoluteURL')."/modules/".$session->get('module')."/tools_timetableByClass_deleteProcess.php?gibbonTTDayRowClassID=".$ttDay['gibbonTTDayRowClassID']."&gibbonTTID={$gibbonTTID}&gibbonCourseClassID={$gibbonCourseClassID}&gibbonSchoolYearID={$gibbonSchoolYearID}'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a>";
                    echo '</td>';
                echo '</tr>';
            }

            echo '</table>';

        }

        $form = Form::create('ttAdd', $session->get('absoluteURL').'/modules/'.$session->get('module').'/tools_timetableByClass_addProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        $form->addHiddenValue('gibbonTTID', $gibbonTTID);
        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);

        $form->addHiddenValue('address', $session->get('address'));

        $dayResults = $toolsGateway->selectTimetableDaysByTimetable($gibbonTTID);
        $columnResults = $toolsGateway->selectTimetableColumnsByTimetable($gibbonTTID);

        $columnRows = ($columnResults->rowCount() > 0)? $columnResults->fetchAll() : array();
        $columnRowsChained = array_combine(array_column($columnRows, 'value'), array_column($columnRows, 'gibbonTTDayID'));
        $columnRowsOptions = array_combine(array_column($columnRows, 'value'), array_column($columnRows, 'name'));

        $gibbonTTDayID = $_GET['gibbonTTDayID'] ?? '';
        $gibbonTTColumnRowID = $_GET['gibbonTTColumnRowID'] ?? '';
        $gibbonTTSpaceID = $_GET['gibbonTTSpaceID'] ?? '';

        $row = $form->addRow();
            $row->addLabel('gibbonTTDayID', __('Timetable Day'));
            $row->addSelect('gibbonTTDayID')
                ->fromResults($dayResults)
                ->required()
                ->selected($gibbonTTDayID);

        $row = $form->addRow();
            $row->addLabel('gibbonTTColumnRowID', __('Timetable Column Row'));
            $row->addSelect('gibbonTTColumnRowID')
                ->fromArray($columnRowsOptions)
                ->required()
                ->chainedTo('gibbonTTDayID', $columnRowsChained)
                ->selected($gibbonTTColumnRowID);

        $row = $form->addRow();
            $row->addLabel('gibbonTTSpaceID', __('Location'));
            $row->addSelectSpace('gibbonTTSpaceID')->selected($gibbonTTSpaceID);

        $row = $form->addRow();
            $row->addSubmit(__('Add'));

        echo $form->getOutput();

        $classResults = $toolsGateway->selectCourseClass($gibbonCourseClassID);
        $class = ($classResults->rowCount() > 0)? $classResults->fetch() : array();

        echo '<h4>';
            echo __('Rename Class');
        echo '</h4>';

        $form = Form::create('ttRenameClass', $session->get('absoluteURL').'/modules/'.$session->get('module').'/tools_timetableByClass_renameProcess.php');

        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        $form->addHiddenValue('gibbonTTID', $gibbonTTID);
        $form->addHiddenValue('gibbonCourseClassID', $gibbonCourseClassID);
        $form->addHiddenValue('address', $session->get('address'));

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

}
