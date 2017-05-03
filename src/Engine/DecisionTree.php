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

namespace Gibbon\Modules\CourseSelection\Engine;

/**
 * Decision Tree
 *
 * @version v14
 * @since   3rd May 2017
 */
class DecisionTree
{
    protected $leaves;
    protected $depth;

    public $iterations = 0;
    public $branchesCreated = 0;
    public $leavesCreated = 0;

    public function buildTree(array &$decisions)
    {
        $this->leaves = array();
        $this->depth = count($decisions)-1;

        // Create the base node and push it to the root of the tree
        $node = new Node(0, array());
        $nodes[] = $node;

        while (!$this->isGoalReached($nodes)) {
            $nodes = $this->createBranches($nodes, $decisions);

            $this->iterations++;
        }

        return $this->leaves;
    }

    protected function isGoalReached(&$nodes)
    {
        //if (count($this->leaves) > 5) return true;

        if (count($nodes) == 0) return true;

        return false;
    }

    protected function createBranches(&$nodes, &$decisions)
    {
        $node = array_pop($nodes);
        $nodeDepth = $node->getDepth();

        if ($nodeDepth >= $this->depth) {
            // Complete (and valid) nodes become leaves
            if ($this->isNodeValid($node)) {
                $this->evaluateWeight($node);

                $this->leaves[] = $node;
            }

            $this->leavesCreated++;
        } else {
            // Decisions in the tree become branches
            $branches = $decisions[$nodeDepth];

            // Shake the tree a bit
            shuffle($branches);

            foreach ($branches as $branch) {
                // Combine the decision with the previous decisions
                $values = $node->getValues();
                $values[] = $branch;

                // Each branch leads to a new node
                $newNode = new Node($nodeDepth+1, $values);
                $nodes[] = $newNode;

                $this->branchesCreated++;
            }
        }

        return $nodes;
    }

    protected function isNodeValid(&$node)
    {
        $periods = array_count_values(array_column($node->getValues(), 'period'));

        return (count($periods) >= $this->depth);
    }

    protected function evaluateWeight(&$node)
    {
        $weight = 0;

        $node->setWeight($weight);
    }
}
