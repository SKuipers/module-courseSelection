<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

namespace Gibbon\Modules\CourseSelection\Timetable;

use Gibbon\Modules\CourseSelection\Domain\TimetableGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');

ini_set('memory_limit', '1024M');
ini_set('max_execution_time', 300);

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

$engine->addData($mockData);


$environment = array();

$validator = new Validator($environment);
$evaluator = new Evaluator($environment);
$solver = new Solver($validator, $evaluator);

$engine->startEngine($validator, $evaluator, $solver);
$results = $engine->run();


echo '<pre>';
echo 'Duration: '.$engine->getRunningTime().'ms'."\n";
// echo 'Iterations: '.$engine->iterations."\n";
// echo 'Braches Created: '.$engine->branchesCreated."\n";
// echo 'Leaves Created: '.$engine->leavesCreated."\n";
echo 'Valid Results: '.count($results, COUNT_RECURSIVE)."\n";
echo "\n\n";
//print_r($results);
echo '</pre>';
