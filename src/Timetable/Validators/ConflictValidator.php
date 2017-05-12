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
                $conflicts[] = $item;
            }
            return $conflicts;
        }, array());

        return (count($periodCounts) >= max(0, $treeDepth - $this->settings->timetableConflictTollerance) );
    }

    public function resolveConflicts(&$node)
    {
        if (empty($node->conflicts) || count($node->conflicts) == 0) return;

        $environment = &$this->environment;

        $remove = array();

        // Group conflicts by period to handle them in sets
        $groupedConflicts = array_reduce($node->conflicts, function($grouped, $item) use ($environment) {
            $item['priority'] = $environment->getClassValue($item['gibbonCourseClassID'], 'priority');
            $grouped[$item['period']][] = $item;
            return $grouped;
        }, array());

        // Sort by priority and remove every conflict that isn't top priority
        foreach ($groupedConflicts as &$values) {
            usort($values, function($a, $b) {
                return $a['priority'] - $b['priority'];
            });

            $remove += array_slice($values, 1);
        }

        // Filter the removed items out of the node values
        if (!empty($remove)) {
            $removeIDs = array_column($remove, 'gibbonCourseClassID');
            $node->values = array_filter($node->values, function($item) use ($removeIDs) {
                return !in_array($item['gibbonCourseClassID'], $removeIDs);
            });

            $node->conflicts = array();

            // TOTO: FLAG ME - CONFLICT RESOLVED / INCOMPLETE
        }
    }
}
