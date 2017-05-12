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

        // Flag values in this node if there are any full classes
        foreach ($node->values as $key => &$value) {
            if ($this->environment->getEnrolmentCount($value['gibbonCourseClassID']) >= $this->settings->maximumStudents) {
                // FLAGGED: Incomplete
                $className = $this->environment->getClassValue($value['gibbonCourseClassID'], 'className');
                $this->createFlag($value, 'Full', 'Full class: '.$className);
            }
        }

        // Look for duplicates by counting the class period occurances
        $periodCounts = array_count_values(array_column($node->values, 'period'));

        // Put together a set of conflicting classes
        $node->conflicts = $confictCount = array_reduce($node->values, function($conflicts, $item) use ($periodCounts) {
            if (!empty($item['flag'])) return $conflicts; // Don't conflict with courses already ruled out

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
        $conflictIDs = array_column($node->conflicts, 'gibbonCourseClassID');

        if ($this->settings->autoResolveConflicts == false) {
            // Simply flag conflicts if we're not auto-resolving
            foreach ($node->values as &$value) {
                if (in_array($value['gibbonCourseClassID'], $conflictIDs)) {
                    // FLAGGED: Conflict
                    $className = $this->environment->getClassValue($value['gibbonCourseClassID'], 'className');
                    $this->createFlag($value, 'Conflict', 'Class : '.$className);
                }
            }
            return;
        }

        // Group conflicts by period to handle them in sets
        $groupedConflicts = array_reduce($node->conflicts, function($grouped, &$item) use ($environment) {
            $item['priority'] = $environment->getClassValue($item['gibbonCourseClassID'], 'priority');
            $grouped[$item['period']][] = $item;
            return $grouped;
        }, array());

        // Sort by priority and flag every conflict that isn't top priority
        foreach ($groupedConflicts as &$values) {
            usort($values, function($a, $b) {
                return $a['priority'] - $b['priority'];
            });

            $keep = $values[0];
            $keepClassName = $this->environment->getClassValue($keep['gibbonCourseClassID'], 'className');

            $remove = array_column(array_slice($values, 1), 'gibbonCourseClassID');

            foreach ($node->values as &$value) {
                if (in_array($value['gibbonCourseClassID'], $remove)) {
                    // FLAGGED: Conflict Resolved
                    $className = $this->environment->getClassValue($value['gibbonCourseClassID'], 'className');
                    $this->createFlag($value, 'Conflict', 'Resolved with '.$keepClassName.' instead of '.$className);
                }
            }
        }
    }

    protected function createFlag(&$value, $flag, $reason = '')
    {
        $value['flag'] = $flag;
        $value['reason'] = $reason;
    }
}
