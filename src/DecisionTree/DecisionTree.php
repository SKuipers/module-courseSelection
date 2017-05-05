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
 * A generic implementation of a basic decision tree: not tied to a specific type of data.
 * The validator and evaluator interfaces provide hooks for making decisions.
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
        $tree = array();
        $leaves = array();

        // Create an empty node and push it to the root of the tree
        $tree[] = new Node(array());

        // Continue adding branches to the tree until the goal is met
        while (!$this->isGoalSatisfied($tree)) {
            $tree = $this->createBranches($tree, $leaves, $decisions);
        }

        return $leaves;
    }

    protected function isGoalSatisfied(&$tree)
    {
        return (count($tree) == 0) || $this->evaulator->evaluateTree($tree);
    }

    protected function createBranches(&$tree, &$leaves, &$decisions)
    {
        $node = array_pop($tree);

        $nodeDepth = $node->getDepth();
        $treeDepth = count($decisions);

        if ($nodeDepth == $treeDepth) {
            // Complete (and valid) nodes become leaves
            if ($this->validator->validateNode($node, $treeDepth)) {
                $weight = $this->evaulator->evaluateNode($node);
                $node->setWeight($weight);

                array_push($leaves, $node);
            }
        } else {
            // Decisions in the tree become branches
            $branches = $decisions[$nodeDepth];

            // Shake the tree a bit
            shuffle($branches);

            foreach ($branches as $branch) {
                // Combine the result of this decision with the previous decisions
                $values = array_slice($node->getValues(), 0);
                array_push($values, $branch);

                // Each branch leads to a new node on the tree
                array_push($tree, new Node($values));
            }
        }

        return $tree;
    }
}
