<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use CourseSelection\Domain\TimetableGateway;
use CourseSelection\SchoolYearNavigation;
use CourseSelection\BackgroundProcess;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tt_engine.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Timetabling Engine', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    $process = new BackgroundProcess($_SESSION[$guid]['absolutePath'].'/uploads/engine');

    if ($process->isProcessRunning('engine')) {
        echo '<table class="mini" id="repTable" cellspacing=0 style="width: 440px;margin: 0 auto;">';
            echo '<tbody><tr>';
            echo '<td style="text-align:center;padding: 0px 40px 15px 40px !important;">';
                echo "<img style='margin:15px;' src='./themes/".$_SESSION[$guid]["gibbonThemeName"]."/img/loading.gif'/><br/>";
                echo '<span>'.__('Processing! Please wait a moment ...').'</span><br/>';
            echo '</td>';
            echo '</tr></tbody>';
        echo '</table>';

        echo '<script>';
        echo "$( document ).ready(function() { checkTimetablingEngineStatus('".$_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/'."'); });";
        echo '</script>';
        return;
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    $timetableGateway = new TimetableGateway($pdo);

    $engineResults = $timetableGateway->countResultsBySchoolYear($gibbonSchoolYearID);
    $engineResultCount = ($engineResults->rowCount() > 0)? $engineResults->fetchColumn(0) : 0;

    if ($engineResultCount == 0) {
        // RUN
        $form = Form::create('engineRun', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tt_engineProcess.php');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        // $row = $form->addRow();
        //     $row->addLabel('', __(''));
        //     $row->addTextField('');

        $row = $form->addRow();
            $row->addContent('<input type="submit" value="'.__('Run').'" class="shortWidth">')->setClass('right');

        echo $form->getOutput();
    } else {
        // RESULTS
        $timetablingResults = getSettingByScope($connection2, 'Course Selection', 'timetablingResults');
        $timetablingResults = json_decode($timetablingResults);

        echo '<pre>';
        print_r($timetablingResults);
        echo '</pre>';

        // GO LIVE
        $form = Form::create('engineGoLive', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tt_engine_goLive.php');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $thickboxGoLive = "onclick=\"tb_show('','".$_SESSION[$guid]['absoluteURL']."/fullscreen.php?q=/modules/Course%20Selection/tt_engine_goLive.php&gibbonSchoolYearID=".$gibbonSchoolYearID."&width=650&height=200',false)\"";
            $row->addContent('<input type="button" value="'.__('Go Live!').'" class="shortWidth" style="background: #444444;color:#ffffff;" '.$thickboxGoLive.'>')->setClass('right');

        echo $form->getOutput();

        echo '<h4>';
        echo __('Reset');
        echo '</h4>';

        // RESET
        $form = Form::create('engineClear', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tt_engine_clear.php');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $row->addAlert(__('Resetting the engine will delete ALL timetabling results, which allows the engine to run again. Course selections will not be affected.'), 'error');

        $row = $form->addRow();
            $thickboxClear = "onclick=\"tb_show('','".$_SESSION[$guid]['absoluteURL']."/fullscreen.php?q=/modules/Course%20Selection/tt_engine_clear.php&gibbonSchoolYearID=".$gibbonSchoolYearID."&width=650&height=200',false)\"";
            $row->addContent('<input type="button" value="'.__('Clear All Results').'" class="shortWidth" style="background: #B10D0D;color:#ffffff;" '.$thickboxClear.'>')->setClass('right');

        echo $form->getOutput();

    }
}
