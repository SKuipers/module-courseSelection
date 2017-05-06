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

/**
 * Timetabling Engine: Factory
 *
 * @version v14
 * @since   5th May 2017
 */
class EngineFactory
{
    protected $settings;

    public function __construct($settingsData = array())
    {
        $this->settings = $this->createSettings($settingsData);
    }

    public function createEngine() : Engine
    {
        return new Engine($this->settings);
    }

    public function createSettings(array $settingsData) : EngineSettings
    {
        return new EngineSettings($settingsData);
    }

    public function createEnvironment(array $environmentData) : EngineEnvironment
    {
        return new EngineEnvironment($environmentData);
    }

    public function createHeuristic(EngineEnvironment $environment) : Heuristic
    {
        $heuristic = new Heuristic($environment);

        return $heuristic;
    }

    public function createValidator(EngineEnvironment $environment) : Validator
    {
        $validator = new Validator($environment);

        return $validator;
    }

    public function createEvaluator(EngineEnvironment $environment) : Evaluator
    {
        $evaluator = new Evaluator($environment);

        return $evaluator;
    }

    public function createSolver(Heuristic $heuristic, Validator $validator, Evaluator $evaluator) : Solver
    {
        $solver = new Solver($heuristic, $validator, $evaluator);

        return $solver;
    }
}
