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

namespace Gibbon\Modules\CourseSelection\Timetable;

use Gibbon\Modules\CourseSelection\DecisionTree\DecisionTree;

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

    protected $validator;
    protected $evaulator;
    protected $solver;

    protected $startTime;
    protected $stopTime;

    protected $dataSet = array();
    protected $resultSet = array();

    public function __construct(EngineSettings $settings = null)
    {
        $this->settings = $settings ?? new EngineSettings();
    }

    public function addData($data)
    {
        if (empty($data) || !is_array($data) || empty($data[0]) || !is_array($data[0])) {
            throw new \Exception('Invalid data fed into engine: not a valid two-dimensional array.');
        }

        $this->dataSet[] = $data;
    }

    public function startEngine(Validator $validator, Evaluator $evaluator, Solver $solver)
    {
        if (empty($solver) || empty($validator) || empty($evaluator)) {
            throw new \Exception('Engine cannot run: missing some parts!');
        }

        if (empty($this->dataSet) || !is_array($this->dataSet)) {
            throw new \Exception('Engine cannot run: no decisions to make. Feed some data into the engine.');
        }

        $this->validator = $validator;
        $this->evaluator = $evaluator;
        $this->solver = $solver;

        $this->startTime = microtime(true);

        return count($this->dataSet);
    }

    public function stopEngine()
    {
        $this->stopTime = microtime(true);
    }

    public function run()
    {
        foreach ($this->dataSet as $data) {
            $this->resultSet[] = $this->solver->makeDecisions($data);
        }

        $this->stopEngine();

        return $this->resultSet;
    }

    public function getRunningTime()
    {
        return ($this->stopTime - $this->startTime);
    }


}
