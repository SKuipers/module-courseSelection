<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable;

use CourseSelection\BackgroundProcess;
use CourseSelection\Domain\TimetableGateway;
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

// Build a set of course information for the school year
$environmentResults = $timetableGateway->selectTimetabledCoursesBySchoolYear($gibbonSchoolYearID);
$environmentData = ($environmentResults && $environmentResults->rowCount() > 0)? $environmentResults->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

// Get the course selections grouped by student
$studentResults = $timetableGateway->selectApprovedCourseSelectionsBySchoolYear($gibbonSchoolYearID);
$studentData = ($studentResults && $studentResults->rowCount() > 0)? $studentResults->fetchAll(\PDO::FETCH_GROUP) : array();

// Condense the result set down group by Student > Course > Classes
$courseSelectionData = collect($studentData)->transform(function($courses, $gibbonPersonIDStudent) {
    return collect($courses)->mapToGroups(function($item) {
         return [$item['gibbonCourseID'] => $item];
    })->toArray();
});


$factory = new EngineFactory();

$settings = $factory->createSettings();
$settings->timetableConflictTollerance = 1;
$settings->optimalWeight = 1.0;
$settings->maximumOptimalResults = 0;
$settings->minimumClassEnrolment = 8;
$settings->maximumClassEnrolment = 24;

// Build the engine
$engine = $factory->createEngine($settings);
$engine->buildEngine($environmentData);

// Add the student data
$courseSelectionData->each(function($courses, $gibbonPersonIDStudent) use ($engine) {
    $engine->addData($gibbonPersonIDStudent, array_values($courses) );
});

// Run
$results = $engine->runEngine();

$performance = $engine->getPerformance();

$data = array(
    'gibbonSchoolYearID' => $gibbonSchoolYearID,
);

foreach ($results as $gibbonPersonIDStudent => $result) {
    if (!empty($result->values) && is_array($result->values)) {
        $data['gibbonPersonIDStudent'] = $gibbonPersonIDStudent;

        foreach ($result->values as $class) {
            $data['gibbonCourseID'] = $class['gibbonCourseID'];
            $data['gibbonCourseClassID'] = $class['gibbonCourseClassID'];
            $data['weight'] = $result->weight;

            $timetableGateway->insertResult($data);
        }
    }
}


// End the process and output the result to terminal (output file)
$processor->stopProcess('engine');
die( __('Complete')."\n" );
