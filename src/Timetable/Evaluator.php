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
    public function evaluateNodeWeight(&$node) : float
    {
        $this->performance['nodeEvaluations']++;

        // Order the results (for interest sake)
        // TODO: Remove later for performace boost
        usort($node->values, $this->sortByNodeValue('period') );

        $weight = 0.0;
        // $weights = array();

        // foreach ($node->values as $option) {
        //     $classEnrolmentCount = $this->environment->get($option['className'], 'students');
        //     $weights[] = 1.0 - ($classEnrolmentCount / $this->settings->maximumClassEnrolment);
        // }

        // $weight = array_sum($weights) / count($weights);

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
        $bestWeight = 0.0;

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
