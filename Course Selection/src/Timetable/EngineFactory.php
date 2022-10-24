<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace Gibbon\Module\CourseSelection\Timetable;

use Gibbon\Module\CourseSelection\Timetable\Solvers\Solver;
use Gibbon\Module\CourseSelection\Timetable\Validators\Validator;
use Gibbon\Module\CourseSelection\Timetable\Evaluators\Evaluator;
use Gibbon\Module\CourseSelection\Timetable\Heuristics\Heuristic;
use Gibbon\Module\CourseSelection\Timetable\Validators\SimpleValidator;
use Gibbon\Module\CourseSelection\Timetable\Validators\ConflictValidator;
use Gibbon\Module\CourseSelection\Timetable\Evaluators\SimpleEvaluator;
use Gibbon\Module\CourseSelection\Timetable\Evaluators\WeightedEvaluator;
use Gibbon\Module\CourseSelection\Timetable\Heuristics\SimpleHeuristic;
use Gibbon\Module\CourseSelection\Timetable\Heuristics\ClassSizeHeuristic;
use Gibbon\Module\CourseSelection\Timetable\Heuristics\RandomizeHeuristic;

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

            case 'Randomize':   $heuristic = new RandomizeHeuristic(); break;

            default:
            case 'Simple':  $heuristic = new SimpleHeuristic(); break;
        }

        return $heuristic;
    }

    public function createValidator(EngineEnvironment $environment, EngineSettings $settings) : Validator
    {
        switch ($settings->validator) {
            case 'Conflict':    $validator = new ConflictValidator($environment, $settings); break;

            default:
            case 'Simple':      $validator = new SimpleValidator(); break;
        }

        return $validator;
    }

    public function createEvaluator(EngineEnvironment $environment, EngineSettings $settings) : Evaluator
    {
        switch ($settings->evaluator) {
            case 'Weighted':    $evaluator = new WeightedEvaluator($environment, $settings); break;

            default:
            case 'Simple':      $evaluator = new SimpleEvaluator(); break;
        }

        return $evaluator;
    }

    public function createSolver(Heuristic $heuristic, Validator $validator, Evaluator $evaluator) : Solver
    {
        $solver = new Solver($heuristic, $validator, $evaluator);

        return $solver;
    }
}
