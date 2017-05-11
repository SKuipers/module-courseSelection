<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable;

use CourseSelection\BackgroundProcess;
use CourseSelection\Domain\TimetableGateway;
use CourseSelection\Domain\SettingsGateway;
use Illuminate\Support\Collection;

// Cancel out now if we're not running via CLI
if (php_sapi_name() != 'cli') {
    die( __('This script cannot be run from a browser, only via CLI.') );
}

include '../../functions.php';
include '../../config.php';

// Setup default settings
ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 45);
getSystemSettings($guid, $connection2);
setCurrentSchoolYear($guid, $connection2);

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');
$loader->addNameSpace('Illuminate\\', 'modules/Course Selection/src/Illuminate/');
require_once $_SESSION[$guid]['absolutePath'].'/modules/Course Selection/src/Illuminate/Support/helpers.php';

// Incoming variables from command line
$gibbonSchoolYearID = (isset($argv[1]))? $argv[1] : null ;

$processor = new BackgroundProcess($_SESSION[$guid]['absolutePath'].'/uploads/engine');
$timetableGateway = new TimetableGateway($pdo);
$settingsGateway = new SettingsGateway($pdo);

// Build a set of class information for the school year
$classResults = $timetableGateway->selectTimetabledClassesBySchoolYear($gibbonSchoolYearID);
$classData = ($classResults && $classResults->rowCount() > 0)? $classResults->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

// Build a set of students information
$studentResults = $timetableGateway->selectTimetabledStudentsBySchoolYear($gibbonSchoolYearID);
$studentData = ($studentResults && $studentResults->rowCount() > 0)? $studentResults->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

// Get the course selections grouped by student
$selectionsResults = $timetableGateway->selectApprovedCourseSelectionsBySchoolYear($gibbonSchoolYearID);
$selectionsData = ($selectionsResults && $selectionsResults->rowCount() > 0)? $selectionsResults->fetchAll(\PDO::FETCH_GROUP) : array();

// Condense the result set down group by Student > Course > Classes
$courseSelectionData = collect($selectionsData)->transform(function($courses, $gibbonPersonIDStudent) {
    return collect($courses)->filter(function($item) {
        return !empty($item['gibbonCourseClassID']);
    })->mapToGroups(function($item) {
         return [$item['gibbonCourseID'] => $item];
    })->toArray();
});


$factory = new EngineFactory();

// Engine Settings
$settings = $factory->createSettings();
$settings->timetableConflictTollerance = getSettingByScope($connection2, 'Course Selection', 'timetableConflictTollerance');
$settings->optimalWeight = 1.0;
$settings->maximumOptimalResults = 0;
$settings->minimumStudents = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentMinimum');
$settings->targetStudents = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentTarget');
$settings->maximumStudents = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentMaximum');

$settings->heuristic = 'Class Size';
$settings->validator = 'Conflict';
$settings->evaluator = 'Weighted';

// Engine Environment
$environment = $factory->createEnvironment();
$environment->setClassData($classData);
$environment->setStudentData($studentData);

// Build the engine
$engine = $factory->createEngine($settings);
$engine->buildEngine($environment);

// Add the student course selections data
$courseSelectionData->each(function($courses, $gibbonPersonIDStudent) use ($engine) {
    $engine->addData($gibbonPersonIDStudent, array_values($courses) );
});

// Run
$results = $engine->runEngine();

$data = array(
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
);

// Make this a method, somewhere?
foreach ($results as $gibbonPersonIDStudent => $result) {
    $data['gibbonPersonIDStudent'] = $gibbonPersonIDStudent;
    $data['weight'] = $result->weight ?? 0.0;

    if (!empty($result->values) && is_array($result->values)) {
        foreach ($result->values as $class) {
            $data['gibbonCourseID'] = $class['gibbonCourseID'];
            $data['gibbonCourseClassID'] = $class['gibbonCourseClassID'];

            $timetableGateway->insertResult($data);
        }
    } else {
        $data['gibbonCourseID'] = null;
        $data['gibbonCourseClassID'] = null;

        $timetableGateway->insertResult($data);
    }
}

// Save the performance stats, may be interested in the results later ...
$settingsGateway->update('Course Selection', 'timetablingResults', json_encode($engine->getPerformance()));

// End the process and output the result to terminal (output file)
$processor->stopProcess('engine');
die( __('Complete')."\n" );
