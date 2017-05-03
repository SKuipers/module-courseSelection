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

use Gibbon\Modules\CourseSelection\Engine\DecisionTree;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');

$startTime = microtime(true);

$mockData = array(
    0 => array(
        array('nameShort' => 'ENG.A-1', 'period' => 'A1'),
        array('nameShort' => 'ENG.A-2', 'period' => 'A2'),
        array('nameShort' => 'ENG.B-1', 'period' => 'B1'),
        array('nameShort' => 'ENG.B-2', 'period' => 'B2'),
    ),

    1 => array(
        array('nameShort' => 'SCI.A-2', 'period' => 'A2'),
        array('nameShort' => 'SCI.A-3', 'period' => 'A3'),
        array('nameShort' => 'SCI.B-3', 'period' => 'B3'),
    ),

    2 => array(

        array('nameShort' => 'MAT.A-3', 'period' => 'A3'),
        array('nameShort' => 'MAT.A-4', 'period' => 'A4'),
        array('nameShort' => 'MAT.B-1', 'period' => 'B1'),
        array('nameShort' => 'MAT.B-4', 'period' => 'B4'),
    ),

    3 => array(
        array('nameShort' => 'SST.A-2', 'period' => 'A2'),
        array('nameShort' => 'SST.B-4', 'period' => 'B4'),
    ),

    4 => array(
        array('nameShort' => 'ART.A-1', 'period' => 'A1'),
        array('nameShort' => 'ART.B-1', 'period' => 'B1'),
    ),

    5 => array(
        array('nameShort' => 'PHY.A-1', 'period' => 'A1'),
        array('nameShort' => 'PHY.B-2', 'period' => 'B2'),
        array('nameShort' => 'PHY.B-3', 'period' => 'B3'),
        array('nameShort' => 'PHY.B-4', 'period' => 'B4'),
    ),

    6 => array(
        array('nameShort' => 'CTS.A-2', 'period' => 'A2'),
        array('nameShort' => 'CTS.A-3', 'period' => 'A3'),
        array('nameShort' => 'CTS.B-3', 'period' => 'B3'),
    ),

    7 => array(
        array('nameShort' => 'BIO.A-1', 'period' => 'A1'),
        array('nameShort' => 'BIO.A-2', 'period' => 'A2'),
        array('nameShort' => 'BIO.B-2', 'period' => 'B2'),
    ),

    8 => array(
        array('nameShort' => 'MUS.A-1', 'period' => 'A1'),
        array('nameShort' => 'MUS.B-4', 'period' => 'B4'),
    ),
);

$engine = new DecisionTree();

$results = $engine->buildTree($mockData);

$endTime = microtime(true);

echo '<pre>';
echo 'Duration: '.($endTime - $startTime).'ms'."\n";
echo 'Iterations: '.$engine->iterations."\n";
echo 'Braches Created: '.$engine->branchesCreated."\n";
echo 'Leaves Created: '.$engine->leavesCreated."\n";
echo 'Valid Results: '.count($results)."\n";
echo "\n\n";

print_r($results);
echo '</pre>';
