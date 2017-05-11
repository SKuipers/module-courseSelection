<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable\Evaluators;

/**
 * Evaluator that weights based on gender balance, class size, etc.
 *
 * @version v14
 * @since   4th May 2017
 */
class WeightedEvaluator extends Evaluator
{
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
}
