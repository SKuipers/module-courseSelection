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
                // FLAGGED: Incomplete
                $className = $this->environment->getClassValue($value['gibbonCourseClassID'], 'className');
                $this->createFlag($value, 'Incomplete', 'Full class: '.$className);
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

        $conflictIDs = array_column($node->conflicts, 'gibbonCourseClassID');

        if ($this->settings->autoResolveConflicts == false) {
            foreach ($node->values as &$value) {
                if (in_array($value['gibbonCourseClassID'], $conflictIDs)) {
                    // FLAGGED: Conflict
                    $className = $this->environment->getClassValue($value['gibbonCourseClassID'], 'className');
                    $this->createFlag($value, 'Conflict', 'Class : '.$className);
                }
            }
            //$node->conflicts = array();

            return;
        }

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
            //
            // $node->values = array_filter($node->values, function($item) use ($removeIDs) {
            //     return !in_array($item['gibbonCourseClassID'], $removeIDs);
            // });

            $removeIDs = array_column($remove, 'gibbonCourseClassID');

            foreach ($node->values as &$value) {
                if (in_array($value['gibbonCourseClassID'], $removeIDs)) {
                    // FLAGGED: Conflict Resolved
                    $className = $this->environment->getClassValue($value['gibbonCourseClassID'], 'className');
                    $this->createFlag($value, 'Conflict Resolved', 'Class removed: '.$className);
                }
            }

            //$node->conflicts = array();
        }
    }

    protected function createFlag(&$value, $flag, $reason = '')
    {
        $value['flag'] = $flag;
        $value['reason'] = $reason;
    }
}
