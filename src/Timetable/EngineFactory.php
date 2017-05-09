<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
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
    public function createEngine($settings = null) : Engine
    {
        if (empty($settings)) {
            $settings = $this->createSettings();
        }

        return new Engine($this, $settings);
    }

    public function createSettings(array $settingsData = array()) : EngineSettings
    {
        return new EngineSettings($settingsData);
    }

    public function createEnvironment() : EngineEnvironment
    {
        return new EngineEnvironment();
    }

    public function createHeuristic(EngineEnvironment $environment, EngineSettings $settings) : Heuristic
    {
        $heuristic = new Heuristic($environment, $settings);

        return $heuristic;
    }

    public function createValidator(EngineEnvironment $environment, EngineSettings $settings) : Validator
    {
        $validator = new Validator($environment, $settings);

        return $validator;
    }

    public function createEvaluator(EngineEnvironment $environment, EngineSettings $settings) : Evaluator
    {
        $evaluator = new Evaluator($environment, $settings);

        return $evaluator;
    }

    public function createSolver(Heuristic $heuristic, Validator $validator, Evaluator $evaluator) : Solver
    {
        $solver = new Solver($heuristic, $validator, $evaluator);

        return $solver;
    }
}
