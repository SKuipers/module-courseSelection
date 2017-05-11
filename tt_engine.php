<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use CourseSelection\Domain\TimetableGateway;
use CourseSelection\Domain\SelectionsGateway;
use CourseSelection\SchoolYearNavigation;
use CourseSelection\BackgroundProcess;
use Illuminate\Support\Collection;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');
$loader->addNameSpace('Illuminate\\', 'modules/Course Selection/src/Illuminate/');

include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;
require_once $_SESSION[$guid]['absolutePath'].'/modules/Course Selection/src/Illuminate/Support/helpers.php';


if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tt_engine.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Timetabling Engine', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

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
    $selectionsGateway = new selectionsGateway($pdo);

    $engineResults = $timetableGateway->selectStudentResultsBySchoolYear($gibbonSchoolYearID);

    if (!$engineResults || $engineResults->rowCount() == 0) {
        $studentResults = $timetableGateway->selectApprovedCourseSelectionsBySchoolYear($gibbonSchoolYearID);
        $studentCollection = collect($studentResults->fetchAll());

        if (!$studentResults || $studentResults->rowCount() == 0) {
            echo "<div class='error'>" ;
                echo __('There are no approved course selections for this school year.');
            echo "</div>" ;
            return;
        }

        $students = $studentCollection->reduce(function($students, $item){
            $students[$item['gibbonPersonIDStudent']] = 1;
            return $students;
        }, array());

        $courses = $studentCollection->reduce(function($courses, $item){
            $courses[$item['gibbonCourseID']] = $item['gibbonCourseClassID'];
            return $courses;
        }, array());

        $incompleteResults = $selectionsGateway->selectStudentsWithIncompleteSelections($gibbonSchoolYearID);
        $incompleteCollection = collect($incompleteResults->fetchAll());

        $unapprovedCount = $incompleteCollection->reduce(function($count, $item){
            $count += ($item['choiceCount'] > 0 && $item['approvalCount'] < $item['choiceCount'])? 1 : 0;
            return $count;
        }, 0);

        $classlessCount = collect($courses)->reduce(function($count, $item){
            $count += (count($item) == 0)? 1 : 0;
            return $count;
        }, 0);

        // RUN
        $form = Form::create('engineRun', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tt_engineProcess.php');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow();
            $column = $row->addColumn();
        if ($unapprovedCount > 0) {
            $column->addAlert(sprintf(__('There are %1$s students with unapproved or incomplete course requests. If you continue with the timetabling process, those course requests will not be included.'), $unapprovedCount), 'warning');
        } else {
            $column->addAlert(__("All student course requests have been approved. You're ready to do some timetabling!"), 'success');
        }

        if ($classlessCount > 0) {
            $column->addAlert(sprintf(__('There are %1$s requested courses  that do not have any classes. If you continue with the timetabling process, those courses will not be included.'), $classlessCount), 'warning');
        }

        $row = $form->addRow();
            $row->addLabel('studentCountInfo', __('Total Students'))->setClass('mediumWidth');
            $row->addTextField('studentCount')->readonly()->setValue(count($students));

        $row = $form->addRow();
            $row->addLabel('courseCountInfo', __('Total Courses'))->setClass('mediumWidth');
            $row->addTextField('courseCount')->readonly()->setValue(count($courses));

        $classEnrolmentMinimum = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentMinimum');
        $classEnrolmentTarget = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentTarget');
        $classEnrolmentMaximum = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentMaximum');

        $row = $form->addRow();
            $row->addLabel('enrolmentInfo', __('Enrolment Targets'))->description(__('Edit in Settings'))->wrap('<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/settings.php">', '</a>');
            $row->addTextField('enrolment')->readonly()->setValue(sprintf(__('Min: %1$s  Target: %2$s  Max: %3$s'), $classEnrolmentMinimum, $classEnrolmentTarget, $classEnrolmentMaximum));

        $enrolmentGoals = array('fill' => __('Fill to maximum (less classes)'), 'balance' => __('Balance each class (more classes)'));

        $row = $form->addRow();
            $row->addLabel('classEnrolmentGoal', __('Class Enrolment Goal'));
            $row->addSelect('classEnrolmentGoal')->fromArray($enrolmentGoals);

        $studentOrders = array('yearGroupDesc' => __('Year Group, descending'));

        $row = $form->addRow();
            $row->addLabel('studentOrder', __('Student Order'))->setClass('mediumWidth');
            $row->addSelect('studentOrder')->fromArray($studentOrders);

        $priorities = array('' => __('None'), '0.5' => __('Low'), '1.0' => __('Medium'), '1.5' => __('High'),);

        $row = $form->addRow();
            $row->addLabel('priorityTargetEnrolment', __('Target Enrolment Priority'));
            $row->addSelect('priorityTargetEnrolment')->fromArray($priorities)->selected('0.5');

        $row = $form->addRow();
            $row->addLabel('priorityGender', __('Gender Balance Priority'));
            $row->addSelect('priorityGender')->fromArray($priorities)->selected('0.5');

        $row = $form->addRow();
            $row->addLabel('priorityCoreCourse', __('Core Course Priority'));
            $row->addSelect('priorityCoreCourse')->fromArray($priorities)->selected('0.5');

        $setting = getSettingByScope($connection2, 'Course Selection', 'timetableConflictTollerance', true);
        $row = $form->addRow();
            $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
            $row->addNumber($setting['name'])->isRequired()->minimum(0)->maximum(5)->setValue($setting['value']);

        $row = $form->addRow();
            $row->addAlert(__("Click run when you're ready to begin timetabling. Once complete you'll see the results here, as well as be able to view them by Course and Student. The timetabling engine will take a moment to process: <b>it's okay to leave or close this page while waiting.</b>"), 'message');

        $row = $form->addRow();
            $row->addContent();
            $row->addContent('<input type="submit" value="'.__('Run').'" class="shortWidth">')->setClass('right');

        echo $form->getOutput();
    } else {
        // RESULTS
        $stats = getSettingByScope($connection2, 'Course Selection', 'timetablingResults');
        $stats = json_decode($stats, true);

        $resultsCollection = collect($engineResults->fetchAll());

        $conflicts = $resultsCollection->filter(function($item) {
            return (!empty($item['gibbonCourseClassID']) && $item['weight'] <= 0.0);
        })->groupBy('gibbonPersonIDStudent');

        $conflictCount = count($conflicts) ?? 0;

        // GO LIVE
        $form = Form::create('engineGoLive', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tt_engine_goLive.php');

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        $row = $form->addRow()->addHeading(__('Timetabling Results'));

        $progressPercent = round((($stats['totalResults'] - $stats['incompleteResults'] - $conflictCount) / $stats['totalResults']) * 100.0);
        $conflictPercent = round(($conflictCount / $stats['totalResults']) * 100.0);

        $progressBar = '<div class="progressBar" style="width:100%">';
        $progressBar .= '<div class="complete" style="width:'.$progressPercent.'%;" title="'.__('Successful').' '.$progressPercent.'%"></div>';
        $progressBar .= '<div class="highlight" style="width:'.$conflictPercent.'%;" title="'.__('Conflicts').' '.$conflictPercent.'%"></div>';
        $progressBar .= '</div>';

        $row = $form->addRow();
            $row->addContent($progressBar)->prepend(__('Success Rate'));

        $row = $form->addRow();
            $row->addLabel('', __('Total Timetables'));
            $row->addTextField('')->readonly()->setValue(strval($stats['totalResults']));

        $row = $form->addRow();
            $row->addLabel('', __('Timetabling Conflicts'));
            $row->addTextField('')->readonly()->setValue(strval($conflictCount));

        $row = $form->addRow();
            $row->addLabel('', __('Timetabling Failures'));
            $row->addTextField('')->readonly()->setValue(strval($stats['incompleteResults']));

        $row = $form->addRow();
            $row->addContent('');
            $row->addButton('View Results by Course', "window.location='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/tt_resultsByCourse.php'."'");
            $row->addButton('View Results by Student', "window.location='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/tt_resultsByStudent.php'."'")->addClass('shortWidth');

        $row = $form->addRow()->addHeading(__('Engine Stats'));

        $row = $form->addRow();
            $row->addLabel('', __('Time Elapsed'));
            $row->addTextField('')->readonly()->setValue(number_format(floatval($stats['time']), 2).' '.__('seconds'));

        $row = $form->addRow();
            $row->addLabel('', __('Memory Consumed'));
            $row->addTextField('')->readonly()->setValue($stats['memory']);

        $row = $form->addRow();
            $row->addLabel('', __('Total Engine Iterations'));
            $row->addTextField('')->readonly()->setValue(number_format($stats['treeEvaluations']));

        $row = $form->addRow();
            $row->addLabel('', __('Total Timetable Combinations'));
            $row->addTextField('')->readonly()->setValue(number_format($stats['nodeValidations']));

        $row = $form->addRow();
            $row->addLabel('', __('Valid Timetable Combinations'));
            $row->addTextField('')->readonly()->setValue(number_format($stats['nodeEvaluations']));

        $row = $form->addRow();
            $row->addLabel('', __('Average Combinations per Student'));
            $row->addTextField('')->readonly()->setValue(number_format($stats['treeEvaluations'] / intval($stats['totalResults']), 0));


        if ($conflictCount > 0 || $stats['incompleteResults'] > 0) {
            $alert = sprintf(__('There are %1$s timetables with conflics and %2$s failed timetables, these students will not recieve complete timetables when the timetabling goes live.'), $conflictCount, $stats['incompleteResults']);
            $alertStatus = 'warning';
        } else {
            $alert = sprintf(__('Congrats! All %1$s timetables have been created successfully. You\'re ready to take this timetable live.'), $stats['totalResults']);
            $alertStatus = 'success';
        }

        $alert .= '<br/><br/>'.__('Taking the timetable live will turn all results into student enrolments for the selected school year. After going live the new student enrolments can be managed as usual from the Timetable Admin module.');

        $row = $form->addRow();
            $row->addAlert($alert, $alertStatus);

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
            $row->addAlert(__('Resetting the engine will delete ALL timetabling results, which allows the engine to run again. Course requests will not be deleted.'), 'error');

        $row = $form->addRow();
            $thickboxClear = "onclick=\"tb_show('','".$_SESSION[$guid]['absoluteURL']."/fullscreen.php?q=/modules/Course%20Selection/tt_engine_clear.php&gibbonSchoolYearID=".$gibbonSchoolYearID."&width=650&height=200',false)\"";
            $row->addContent('<input type="button" value="'.__('Clear All Results').'" class="shortWidth" style="background: #B10D0D;color:#ffffff;" '.$thickboxClear.'>')->setClass('right');

        echo $form->getOutput();

    }
}
