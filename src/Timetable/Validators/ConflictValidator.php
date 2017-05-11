<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable\Validators;

/**
 * Validator that looks for timetabling conflicts
 *
 * @version v14
 * @since   4th May 2017
 */
class ConflictValidator extends Validator
{
    /**
     * @param   object  &$node
     * @param   int     &$treeDepth
     * @return  bool
     */
    public function validateNode(&$node, $treeDepth) : bool
    {
        $this->performance['nodeValidations']++;

        // Invalidate this node if there are any full classes
        foreach ($node->values as &$option) {
            if ($this->environment->getEnrolmentCount($option['gibbonCourseClassID']) >= $this->settings->maximumStudents) {
                //unset($option);
                //$node->weight -= 5.0;
                //$node->invalid = true;
                return false;
            }
        }

        // Look for duplicates by counting the class period occurances
        $periods = array_column($node->values, 'period');
        $periodCounts = array_count_values($periods);

        $node->conflicts = $confictCount = array_reduce($periodCounts, function($total, $item) {
            $total += ($item > 1)? $item : 0;
            return $total;
        }, 0);

        return (count($periodCounts) >= max(0, $treeDepth - $this->settings->timetableConflictTollerance) );
    }
}
