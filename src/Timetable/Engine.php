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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace CourseSelection\Timetable;

use CourseSelection\DecisionTree\DecisionTree;

/**
 * Timetabling Engine
 *
 * Handles batch processing of student timetable generation via decision tree.
 * The engine is made up of several replaceable parts, allowing for a high
 * degree of control over the timetabling process.
 *
 * @version v14
 * @since   4th May 2017
 */
class Engine
{
    protected $settings;
    protected $environment;

    protected $validator;
    protected $evaulator;
    protected $solver;

    protected $startTime;
    protected $stopTime;

    protected $dataSet = array();
    protected $resultSet = array();

    protected $performance = array();

    public function __construct(EngineSettings $settings)
    {
        $this->settings = $settings;
    }

    public function addData($gibbonPersonIDStudent, $data)
    {
        if (empty($data) || !is_array($data) || empty(next($data)) || !is_array(next($data))) {
            throw new \Exception('Invalid data fed into engine: not a valid two-dimensional array.');
        }

        $this->dataSet[$gibbonPersonIDStudent] = $data;
    }

    public function buildEngine(EngineFactory $factory, $environmentData = array())
    {
        // Factory is responsible for creating and configuring the parts that go in the engine
        $this->environment = $factory->createEnvironment($environmentData);
        $this->heuristic = $factory->createHeuristic($this->environment);
        $this->validator = $factory->createValidator($this->environment);
        $this->evaluator = $factory->createEvaluator($this->environment);
        $this->solver = $factory->createSolver($this->heuristic, $this->validator, $this->evaluator);
    }

    public function runEngine()
    {
        if (empty($this->solver) || empty($this->validator) || empty($this->evaluator)) {
            throw new \Exception('Engine cannot run: missing some parts!');
        }

        if (empty($this->dataSet) || !is_array($this->dataSet)) {
            throw new \Exception('Engine cannot run: no decisions to make. Feed some data into the engine.');
        }

        $this->startEngine();

        foreach ($this->dataSet as $gibbonPersonIDStudent => $data) {
            $results = $this->solver->makeDecisions($data);
            $bestResult = $this->evaluator->getBestNodeInSet($results);

            if (empty($bestResult)) {
                $this->performance['incompleteResults'] = @$this->performance['incompleteResults'] + 1;
            }

            $this->resultSet[$gibbonPersonIDStudent] = $bestResult;
        }

        $this->stopEngine();

        return $this->resultSet;
    }

    protected function startEngine()
    {
        $this->performance['startTime'] = microtime(true);
        $this->performance['startMemory'] = memory_get_usage();
    }

    protected function stopEngine()
    {
        $this->performance['stopTime'] = microtime(true);
        $this->performance['stopMemory'] = memory_get_usage();

        $units = array('bytes','KB','MB','GB','TB','PB');
        $bytes = max( 0, ($this->performance['stopMemory'] - $this->performance['startMemory']));
        $memory = round($bytes/pow(1024,($i=floor(log($bytes,1024)))),2).' '.$units[$i];
        $time = number_format($this->performance['stopTime'] - $this->performance['startTime'], 6).' sec';

        $this->performance['time'] = $time;
        $this->performance['memory'] = $memory;

        $this->performance['nodeValidations'] = $this->validator->getNodeValidations();
        $this->performance['nodeEvaluations'] = $this->evaluator->getNodeEvaluations();
        $this->performance['treeEvaluations'] = $this->evaluator->getTreeEvaluations();
    }

    public function getPerformance()
    {
        return $this->performance;
    }
}
