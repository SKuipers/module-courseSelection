<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable;

use CourseSelection\DecisionTree\NodeEvaluator;

/**
 * Implementation of the NodeEvaluator interface for the Timetabling Engine
 *
 * @version v14
 * @since   4th May 2017
 */
class Evaluator implements NodeEvaluator
{
    protected $environment;
    protected $settings;

    /**
     * Internal Counters
     */
    protected $optimalNodesEvaluated;

    /**
     * Performance Metrics
     */
    protected $performance = array(
        'nodeEvaluations'   => 0,
        'treeEvaluations'   => 0,
        'incompleteResults' => 0,
    );

    public function __construct(EngineEnvironment $environment, EngineSettings $settings)
    {
        $this->environment = $environment;
        $this->settings = $settings;
    }

    public function reset()
    {
        $this->optimalNodesEvaluated = 0;
    }

    /**
     * @param   object  &$node
     * @return  float
     */
    public function evaluateNodeWeight(&$node, $treeDepth) : float
    {
        $this->performance['nodeEvaluations']++;

        $weight = $node->weight ?? 0.0;

        // Sub-weighting: Gender Balance
        $gender = $this->environment->getStudentValue($node->key, 'gender');

        // Sub-weighting: Class Enrolment


        // Sub-weighting: Timetable Conflicts
        // $periods = array_column($node->values, 'period');
        // $periodCounts = array_count_values($periods);

        // $confictCount = array_reduce($periodCounts, function($total, $item) {
        //     $total += ($item > 1)? $item : 0;
        //     return $total;
        // }, 0);

        if ($node->conflicts > 1) {
            $weight += $node->conflicts * -1;
        } else {
            $weight += 1.0;
        }

        if (count($node->values) < $treeDepth) {
            $weight += count($node->values) - $treeDepth;
        }

        if ($weight >= $this->settings->optimalWeight) {
            $this->optimalNodesEvaluated++;
        }

        return $weight;
    }

    /**
     * @param   array  &$nodes
     * @return  bool
     */
    public function evaluateTreeCompletion(&$tree, &$leaves) : bool
    {
        $this->performance['treeEvaluations']++;

        // The tree is potentially complete if we've already seen a number of optimal results
        if (!empty($this->settings->maximumOptimalResults)) {
            if ($this->optimalNodesEvaluated >= $this->settings->maximumOptimalResults) {
                return true;
            }
        }

        return false;
    }

    public function getBestNodeInSet(&$nodes)
    {
        $bestResult = current($nodes);
        $bestWeight = -INF;

        foreach ($nodes as $node) {
            if ($node->weight > $bestWeight) {
                $bestResult = $node;
                $bestWeight = $node->weight;
            }
        }

        if (empty($bestResult)) $this->performance['incompleteResults']++;

        return $bestResult;
    }

    public function getPerformance()
    {
        return $this->performance;
    }

    protected function sortByNodeValue($key) {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }
}
