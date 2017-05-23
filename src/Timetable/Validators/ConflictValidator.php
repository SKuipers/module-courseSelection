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

        $gibbonPersonID = current($node->values)['gibbonPersonID'];
        $enrolmentTTDays = $this->environment->getStudentValue($gibbonPersonID, 'ttDays');

        // Put together a set of conflicting classes
        $node->conflicts = $confictCount = array_reduce($node->values, function($conflicts, $item) use (&$node, &$enrolmentTTDays) {
            if (!empty($item['flag'])) return $conflicts; // Don't conflict with courses already ruled out

            // Look for conflicts with pre-enrolled classes
            if ($this->inArrayWithArray($item['ttDays'], $enrolmentTTDays)) {
                $conflicts[$item['gibbonCourseClassID']] = $item;
            }

            // Look for other classes that have conflicting TT days as this one
            if (is_array($item['ttDays'])) {
                foreach ($node->values as $other) {
                    if ($item['gibbonCourseClassID'] == $other['gibbonCourseClassID']) continue;

                    if ($this->inArrayWithArray($item['ttDays'], $other['ttDays'])) {
                        $conflicts[$item['gibbonCourseClassID']] = $item;
                    }
                }
            }

            return $conflicts;
        }, array());

        return (count($node->conflicts) <= $this->settings->timetableConflictTollerance);
    }

    public function resolveConflicts(&$node)
    {
        if (empty($node->conflicts) || count($node->conflicts) == 0) return;

        $environment = &$this->environment;
        $conflictIDs = array_column($node->conflicts, 'gibbonCourseClassID');

        if ($this->settings->autoResolveConflicts == 'N') {
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
            $grouped[$item['className']][] = $item;
            return $grouped;
        }, array());

        $gibbonPersonID = current($node->values)['gibbonPersonID'];
        $enrolmentTTDays = $this->environment->getStudentValue($gibbonPersonID, 'ttDays');

        // Sort by priority and flag every conflict that isn't top priority
        foreach ($groupedConflicts as &$values) {
            usort($values, function($a, $b) {
                return $a['priority'] - $b['priority'];
            });

            $keep = $values[0];
            $keepClassName = $this->environment->getClassValue($keep['gibbonCourseClassID'], 'className');

            // Look for conflicts with pre-enrolled classes
            if ($this->inArrayWithArray($keep['ttDays'], $enrolmentTTDays)) {
                // Simply flag all conflicts
                foreach ($node->values as &$value) {
                    if (in_array($value['gibbonCourseClassID'], $conflictIDs)) {
                        // FLAGGED: Conflict
                        $className = $this->environment->getClassValue($value['gibbonCourseClassID'], 'className');
                        $this->createFlag($value, 'Conflict', 'Conflicts with existing enrolment');
                    }
                }
            } else {
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
    }

    protected function createFlag(&$value, $flag, $reason = '')
    {
        $value['flag'] = $flag;
        $value['reason'] = $reason;
    }

    protected function inArrayWithArray(array $needle, array $haystack) {
        if (empty($needle) || empty($haystack)) return false;

        foreach ($needle as $value) {
            if (in_array($value, $haystack, true)) return true;
        }

        return false;
    }
}
