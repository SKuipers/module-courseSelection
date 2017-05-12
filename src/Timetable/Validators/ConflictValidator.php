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
        foreach ($node->values as $key => &$value) {

            if ($this->environment->getEnrolmentCount($value['gibbonCourseClassID']) >= $this->settings->maximumStudents) {
                unset($node->values[$key]);
                //$node->invalid = true;
                //return false;
                // TOTO: FLAG ME - INCOMPELTE
            }
        }

        // Look for duplicates by counting the class period occurances
        $periods = array_column($node->values, 'period');
        $periodCounts = array_count_values($periods);

        $node->conflicts = $confictCount = array_reduce($node->values, function($conflicts, $item) use ($periodCounts) {
            if (isset($item['period']) && $periodCounts[$item['period']] > 1) {
                $conflicts[] = $item['gibbonCourseClassID'];
            }
            return $conflicts;
        }, array());

        return (count($periodCounts) >= max(0, $treeDepth - $this->settings->timetableConflictTollerance) );
    }
}
