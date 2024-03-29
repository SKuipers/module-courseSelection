<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace Gibbon\Module\CourseSelection\Timetable\Solvers;

use Gibbon\Module\CourseSelection\DecisionTree\DecisionTree;
use Gibbon\Module\CourseSelection\DecisionTree\NodeHeuristic;
use Gibbon\Module\CourseSelection\DecisionTree\NodeValidator;
use Gibbon\Module\CourseSelection\DecisionTree\NodeEvaluator;

/**
 * Problem solver for the Timetabling Engine: impemented as a decision tree
 *
 * @version v14
 * @since   4th May 2017
 */
class Solver
{
    protected $decisionTree;

    public function __construct(NodeHeuristic $heuristic, NodeValidator $validator, NodeEvaluator $evaulator)
    {
        $this->decisionTree = new DecisionTree($heuristic, $validator, $evaulator);
    }

    public function makeDecisions(&$data) : array
    {
        return $this->decisionTree->buildTree($data);
    }
}
