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
        $weightTotal += $this->settings->genderBalancePriority;
        $weightCumulative += ($this->settings->genderBalancePriority * $this->getGenderBalanceWeight($node));

        // Sub-weighting: Class Enrolment
        $weightTotal += $this->settings->targetEnrolmentPriority;
        $weightCumulative += ($this->settings->targetEnrolmentPriority * $this->getEnrolmentWeight($node));

        // Sub-weighting: Timetable Conflicts
        $weightTotal += $this->settings->avoidConflictPriority;
        $weightCumulative += ($this->settings->avoidConflictPriority * $this->getConflictWeight($node));


        // MISSING VALUES?
        // if (count($node->values) < $treeDepth) {
        //     $weight += count($node->values) - $treeDepth;
        // }


        // Get the weighted weight :P
        $weight = ($weightTotal > 0)? ($weightCumulative / $weightTotal) : 0;

        // Possibly use this to short-cut out of result sets that already have a number of optimal results?
        if ($weight >= $this->settings->optimalWeight) {
            $this->optimalNodesEvaluated++;
        }

        return $weight;
    }

    /**
     * Normalized from -1 to 1
     *
     * @param    object  &$node
     * @return   float
     */
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

        return $weight / count($node->values);
    }

    /**
     * Normalized from 0 to 1
     *
     * @param    object  &$node
     * @return   float
     */
    protected function getEnrolmentWeight(&$node)
    {
        $weight = 0.0;

        foreach ($node->values as $values) {
            $students = $this->environment->getEnrolmentCount($values['gibbonCourseClassID']);

            if (empty($students)) continue;

            $percent = 1.0 - ($students / $this->settings->maximumStudents);

            $weight += $percent;
        }

        return $weight / count($node->values);
    }

    /**
     * Not normalized? -n to 0
     *
     * @param    object  &$node
     * @return   float
     */
    protected function getConflictWeight(&$node)
    {
        $weight = 0.0;

        if (!empty($node->conflicts) && count($node->conflicts) > 0) {
            $weight += count($node->conflicts) * -1.0;
        }

        return $weight;
    }
}
