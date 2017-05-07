<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable;

use CourseSelection\Domain\TimetableGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');

$mockData = array(
    0 => array( 'ENG.A-1', 'ENG.A-2', 'ENG.B-1', 'ENG.B-2' ),
    1 => array( 'SCI.A-2', 'SCI.A-3', 'SCI.B-3' ),
    2 => array( 'MAT.A-3', 'MAT.A-4', 'MAT.B-1', 'MAT.B-4' ),
    3 => array( 'SST.A-2', 'SST.B-4' ),
    4 => array( 'ART.A-1', 'ART.B-1' ),
    5 => array( 'PHY.A-1', 'PHY.B-2', 'PHY.B-3', 'PHY.B-4' ),
    6 => array( 'CTS.A-2', 'CTS.A-3', 'CTS.B-3' ),
    7 => array( 'BIO.A-1', 'BIO.A-2', 'BIO.B-2' ),
);

// GOAL:
// Setup the engine
// Provide environment values
// Feed the engine some student data
// Run the engine as a batch process
// Do something with the results
// Output some stats & logs


$timetableGateway = new TimetableGateway($pdo);

$engineSettings = new EngineSettings();

$engine = new Engine($engineSettings);

$environment = array();

$validator = new Validator($environment);
$evaluator = new Evaluator($environment);
$solver = new Solver($validator, $evaluator);

$engine->startEngine($validator, $evaluator, $solver);




