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

        $weightTotal = 0.0;
        $weightCumulative = 0.0;

        // Sub-weighting: Gender Balance
        $genderBalancePriority = 10;
        $genderBalanceWeight = $this->getGenderBalanceWeight($node);

        // Add the weighted value to the total
        $weightTotal += $genderBalancePriority;
        $weightCumulative += ($genderBalancePriority * $genderBalanceWeight);



        // Sub-weighting: Class Enrolment
        $enrolmentPriority = 20;
        $enrolmentWeight = $this->getEnrolmentWeight($node);

        // Add the weighted value to the total
        $weightTotal += $enrolmentPriority;
        $weightCumulative += ($enrolmentPriority * $enrolmentWeight);


        // Sub-weighting: Timetable Conflicts
        $conflictPriority = 30;
        $conflictWeight = $this->getConflictWeight($node);

        // Add the weighted value to the total
        $weightTotal += $conflictPriority;
        $weightCumulative += ($conflictPriority * $conflictWeight);


        // MISSING VALUES?
        // if (count($node->values) < $treeDepth) {
        //     $weight += count($node->values) - $treeDepth;
        // }

        // Possibly use this to short-cut out of result sets that already have a number of optimal results?
        if ($weight >= $this->settings->optimalWeight) {
            $this->optimalNodesEvaluated++;
        }

        // Get the weighted weight :P
        $weight = ($weightTotal > 0)? ($weightCumulative / $weightTotal) : 0;

        return $weight;
    }

    protected function getGenderBalanceWeight(&$node)
    {
        $weight = 0.0;

        $gender = $this->environment->getStudentValue(current($node->values)['gibbonPersonID'], 'gender');

        foreach ($node->values as $values) {
            $students = $this->environment->getEnrolmentCount($values['gibbonCourseClassID']);

            if (empty($students)) continue;

            $studentsMale = $this->environment->getEnrolmentCount($values['gibbonCourseClassID'], 'M');
            $studentsFemale = $this->environment->getEnrolmentCount($values['gibbonCourseClassID'], 'F');

            if ($gender == 'F') {
                $balance = ($studentsMale / $students) - ($studentsFemale / $students);
            } else {
                $balance = ($studentsFemale / $students) - ($studentsMale / $students);
            }

            $weight += $balance;
        }

        return $weight;
    }

    protected function getEnrolmentWeight(&$node)
    {
        $weight = 0.0;

        foreach ($node->values as $values) {
            $students = $this->environment->getEnrolmentCount($values['gibbonCourseClassID']);

            if (empty($students)) continue;

            $percent = 1.0 - ($students / $this->settings->maximumStudents);

            $weight += $percent;
        }

        return $weight;
    }

    protected function getConflictWeight(&$node)
    {
        $weight = 0.0;

        if (!empty($node->conflicts)) {
            $weight += $node->conflicts * -1.0;
        }

        return $weight;

        // $periods = array_column($node->values, 'period');
        // $periodCounts = array_count_values($periods);

        // $confictCount = array_reduce($periodCounts, function($total, $item) {
        //     $total += ($item > 1)? $item : 0;
        //     return $total;
        // }, 0);
    }
}
