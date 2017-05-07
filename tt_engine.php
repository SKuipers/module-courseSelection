<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use CourseSelection\Domain\TimetableGateway;
use CourseSelection\SchoolYearNavigation;

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

    if (empty($secureFilePath)) {
        $secureFilePath = $_SESSION[$guid]['absolutePath'].'/uploads';
    }

    if (file_exists($secureFilePath. '/engine/batchProcessing.txt')) {
        echo '<table class="mini" id="repTable" cellspacing=0 style="width: 440px;margin: 0 auto;">';
            echo '<tbody><tr>';
            echo '<td style="text-align:center;padding: 0px 40px 15px 40px !important;">';
                echo "<img style='margin:15px;' src='./themes/".$_SESSION[$guid]["gibbonThemeName"]."/img/loading.gif'/><br/>";
                echo '<span>'.__('Processing!').'</span><br/>';
            echo '</td>';
            echo '</tr></tbody>';
        echo '</table>';


        echo '<script>';
        echo "$( document ).ready(function() { checkTimetablingEngineStatus('".$_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/'."'); });";
        echo '</script>';
        return;
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);


    $form = Form::create('action', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tt_engineProcess.php');

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    // $row = $form->addRow();
    //     $row->addLabel('', __(''));
    //     $row->addTextField('');

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();


}
