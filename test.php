<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable;

use CourseSelection\Domain\TimetableGateway;
use Illuminate\Support\Collection;

// Autoloader & Module includes
require_once $_SESSION[$guid]['absolutePath'].'/modules/Course Selection/src/Illuminate/Support/helpers.php';

$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');
$loader->addNameSpace('Illuminate\\', 'modules/Course Selection/src/Illuminate/');


ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 45);


// GOAL:
// Setup the engine
// Provide environment values
// Feed the engine some student data
// Run the engine as a batch process
// Do something with the results
// Output some stats & logs

$timetableGateway = new TimetableGateway($pdo);

// Build a set of course information for the school year
$environmentResults = $timetableGateway->selectTimetabledCoursesBySchoolYear('012');
$environmentData = ($environmentResults && $environmentResults->rowCount() > 0)? $environmentResults->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();


// Get the course selections grouped by student
$studentResults = $timetableGateway->selectApprovedCourseSelectionsBySchoolYear('012');
$studentData = ($studentResults && $studentResults->rowCount() > 0)? $studentResults->fetchAll(\PDO::FETCH_GROUP) : array();

// Limit the results (for now)
//$studentData = array_slice($studentData, 0, 100);

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

$engine = $factory->createEngine($settings);
$engine->buildEngine($environmentData);

$courseSelectionData->each(function($courses, $gibbonPersonIDStudent) use ($engine) {
    $engine->addData($gibbonPersonIDStudent, array_values($courses) );
});

$results = $engine->runEngine();

$performance = $engine->getPerformance();

$classEnrolments = collect($engine->getEnvironment()->getData())->transform(function($class) {
    return $class['students'];
})->filter(function($class){
    return $class > 0;
});



echo '<pre>';
echo "\n\n";
echo 'Engine Duration: '.$performance['time']."\n";
echo 'Engine Memory: '.$performance['memory']."\n";
echo 'Total Memory: '.round(memory_get_usage()/1024, 2)."\n";
echo "\n\n";
echo 'Tree Iterations: '.number_format($performance['treeEvaluations'])."\n";
echo 'Branches Created: '.number_format($performance['nodeValidations'])."\n";
echo 'Leaves Created: '.number_format($performance['nodeEvaluations'])."\n";
echo "\n\n";
echo 'Students: '.$performance['totalResults']."\n";
echo 'Students without Results: '.$performance['incompleteResults']."\n";
echo "\n\n";
print_r($classEnrolments);
print_r( array_slice($results, 0, 3) );
echo '</pre>';
