<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable;

use CourseSelection\Timetable\Solvers\Solver;
use CourseSelection\Timetable\Validators\Validator;
use CourseSelection\Timetable\Evaluators\Evaluator;
use CourseSelection\Timetable\Heuristics\Heuristic;
use CourseSelection\Timetable\Validators\SimpleValidator;
use CourseSelection\Timetable\Validators\ConflictValidator;
use CourseSelection\Timetable\Evaluators\SimpleEvaluator;
use CourseSelection\Timetable\Evaluators\WeightedEvaluator;
use CourseSelection\Timetable\Heuristics\ClassSizeHeuristic;
use CourseSelection\Timetable\Heuristics\RandomizeHeuristic;

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
        switch ($settings->heuristic) {
            case 'Class Size':  $heuristic = new ClassSizeHeuristic($environment, $settings); break;

            default:
            case 'Randomize':   $heuristic = new RandomizeHeuristic($environment, $settings); break;
        }

        return $heuristic;
    }

    public function createValidator(EngineEnvironment $environment, EngineSettings $settings) : Validator
    {
        switch ($settings->validator) {
            case 'Conflict':    $validator = new ConflictValidator($environment, $settings); break;

            default:
            case 'Simple':      $validator = new SimpleValidator($environment, $settings); break;
        }

        return $validator;
    }

    public function createEvaluator(EngineEnvironment $environment, EngineSettings $settings) : Evaluator
    {
        switch ($settings->evaluator) {
            case 'Weighted':    $evaluator = new WeightedEvaluator($environment, $settings); break;

            default:
            case 'Simple':      $evaluator = new SimpleEvaluator($environment, $settings); break;
        }

        return $evaluator;
    }

    public function createSolver(Heuristic $heuristic, Validator $validator, Evaluator $evaluator) : Solver
    {
        $solver = new Solver($heuristic, $validator, $evaluator);

        return $solver;
    }
}
