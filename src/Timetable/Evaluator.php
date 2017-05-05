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

use Gibbon\Modules\CourseSelection\DecisionTree\NodeEvaluator;

/**
 * Implementation of the NodeEvaluator interface for the Timetabling Engine
 *
 * @version v14
 * @since   4th May 2017
 */
class Evaluator implements NodeEvaluator
{
    protected $environment;

    protected $nodeEvaluations = 0;
    protected $treeEvaluations = 0;

    public function __construct(EngineEnvironment $environment)
    {
        $this->environment = $environment;
    }

    /**
     * @param   object  &$node
     * @return  float
     */
    public function evaluateNode(&$node) : float
    {
        $this->nodeEvaluations++;

        // Order the results (for interest sake)
        // TODO: Remove later for performace boost
        usort($node->values, $this->nodeSorter('period') );

        return 0.0;
    }

    /**
     * @param   array  &$nodes
     * @return  bool
     */
    public function evaluateTree(&$tree) : bool
    {
        $this->treeEvaluations++;

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

        return $bestResult;
    }

    public function getNodeEvaluations()
    {
        return $this->nodeEvaluations;
    }

    public function getTreeEvaluations()
    {
        return $this->treeEvaluations;
    }

    protected function nodeSorter($key) {
        return function ($a, $b) use ($key) {
            return strnatcmp($a[$key], $b[$key]);
        };
    }
}
