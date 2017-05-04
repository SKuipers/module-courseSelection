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

namespace Gibbon\Modules\CourseSelection\DecisionTree;

/**
 * Decision Tree
 *
 * @version v14
 * @since   3rd May 2017
 */
class DecisionTree
{
    protected $validator;
    protected $evaulator;

    public function __construct(NodeValidator $validator, NodeEvaluator $evaulator)
    {
        $this->validator = $validator;
        $this->evaulator = $evaulator;
    }

    public function buildTree(array &$decisions)
    {
        $nodes = array();
        $leaves = array();

        // Create an empty node and push it to the root of the tree
        $nodes[] = new Node(array());

        // Continue adding branches to the tree until the goal is met
        while (!$this->isGoalReached($nodes)) {
            $nodes = $this->createBranches($nodes, $leaves, $decisions);
        }

        return $leaves;
    }

    protected function isGoalReached(&$nodes)
    {
        return (count($nodes) == 0) || $this->evaulator->evaluateTree($nodes);
    }

    protected function createBranches(&$nodes, &$leaves, &$decisions)
    {
        $node = array_pop($nodes);

        $nodeDepth = $node->getDepth();
        $treeDepth = (count($decisions) - 1);

        if ($nodeDepth >= $treeDepth) {
            // Complete and valid nodes become leaves
            if ($this->validator->validateNode($node, $treeDepth)) {
                $node->setWeight($this->evaulator->evaluateNode($node));

                array_push($leaves, $node);
            }
        } else {
            // Decisions in the tree become branches
            $branches = $decisions[$nodeDepth];

            // Shake the tree a bit
            shuffle($branches);

            foreach ($branches as $branch) {
                // Combine the result of this decision with the previous decisions
                $values = $node->getValues();
                array_push($values, $branch);

                // Each branch leads to a new node
                array_push($nodes, new Node($values));
            }
        }

        return $nodes;
    }
}
