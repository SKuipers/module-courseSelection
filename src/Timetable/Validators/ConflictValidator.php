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

        $node->number = $this->performance['nodeValidations'];

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
        $nodeIndex = -1;
        $node->conflicts = array_reduce($node->values, function($conflicts, $item) use (&$node, &$enrolmentTTDays, &$nodeIndex) {
            $nodeIndex++;

            if (!empty($item['flag'])) return $conflicts; // Don't conflict with courses already ruled out

            $item['nodeIndex'] = $nodeIndex;

            // Look for conflicts with pre-enrolled classes
            if ($this->inArrayWithArray($item['ttDays'], $enrolmentTTDays)) {
                $item['blocked'] = true;
                $conflicts[$item['gibbonCourseClassID']] = $item;
            }

            // Look for other classes that have conflicting TT days as this one
            if (is_array($item['ttDays'])) {
                foreach ($node->values as $otherIndex => $other) {
                    if ($item['gibbonCourseClassID'] == $other['gibbonCourseClassID']) continue;

                    if ($this->inArrayWithArray($item['ttDays'], $other['ttDays'])) {
                        @$node->values[$nodeIndex]['conflicts']++;
                        @$item['conflicts'][] = $otherIndex;
                        $conflicts[$item['gibbonCourseClassID']] = $item;
                    }
                }
            }

            return $conflicts;
        }, array());

        // Go through each conflict, are there conflicts that do not conflict with each other?
        foreach ($node->conflicts as $gibbonCourseClassID => $conflict) {
            if (!empty($conflict['blocked'])) continue;
            if (count($conflict['conflicts']) <= 1) continue;

            foreach ($conflict['conflicts'] as $nodeIndex) {
                $item = $node->values[$nodeIndex];
                $conflictCount = 0;

                // Look at each other node for conflicts
                foreach ($node->values as $other) {
                    if ($conflict['gibbonCourseClassID'] == $other['gibbonCourseClassID']) continue; // Ignore self
                    if ($item['gibbonCourseClassID'] == $other['gibbonCourseClassID']) continue; // Ignore item

                    if ($this->inArrayWithArray($item['ttDays'], $other['ttDays'])) {
                        $conflictCount++;
                    }
                }

                // If this item has no conflicts with other nodes, remove it as a conflict with this node
                if ($conflictCount == 0 && isset($node->conflicts[$item['gibbonCourseClassID']])) {
                    $node->conflicts[$item['gibbonCourseClassID']]['conflicts'] = array_diff($node->conflicts[$item['gibbonCourseClassID']]['conflicts'], [$conflict['nodeIndex']]);
                }
            }
        }

        // Trim out conflicts have have been internally resolved
        $node->conflicts = array_filter($node->conflicts, function($item) {
            return !empty($item['blocked']) || count($item['conflicts']) > 0;
        });

        return (count($node->conflicts) <= $this->settings->timetableConflictTollerance);
    }

    public function resolveConflicts(&$node)
    {
        // print_r($node);

        if (empty($node->conflicts) || count($node->conflicts) == 0) return;

        $environment = &$this->environment;
        $conflictIDs = array_column($node->conflicts, 'gibbonCourseClassID');

        // Group conflicts by period to handle them in sets
        $groupedConflicts = array_reduce($node->conflicts, function($grouped, &$item) use ($environment) {
            $item['priority'] = $environment->getClassValue($item['gibbonCourseClassID'], 'priority');
            $classPeriod = implode('-', $item['ttDays']);
            // $classPeriod = preg_replace('/[^0-9-]/', '', strrchr($item['classNameShort'], '-'));
            $grouped[$classPeriod][] = $item;
            return $grouped;
        }, array());

        if ($this->settings->autoResolveConflicts == 'N') {
            // Simply flag conflicts if we're not auto-resolving
            foreach ($node->values as &$value) {
                if (in_array($value['gibbonCourseClassID'], $conflictIDs)) {
                    // FLAGGED: Conflict
                    $className = $this->environment->getClassValue($value['gibbonCourseClassID'], 'className');
                    // $classPeriod = preg_replace('/[^0-9-]/', '', strrchr($value['classNameShort'], '-'));
                    $classPeriod = implode('-', $value['ttDays']);

                    $conflictNames = array_reduce($groupedConflicts[$classPeriod] ?? [], function ($group, $item) use ($className) {
                        $conflictName = $item['courseNameShort'].'.'.$item['classNameShort'];
                        if ($conflictName != $className) {
                            $group[] = $conflictName;
                        }
                        return $group;
                    }, []);

                    $this->createFlag($value, 'Conflict', 'Conflicts with '.implode(', ', $conflictNames));
                }
            }
            return;
        }

        $gibbonPersonID = current($node->values)['gibbonPersonID'];
        $enrolmentTTDays = $this->environment->getStudentValue($gibbonPersonID, 'ttDays');

        // Sort by priority and flag every conflict that isn't top priority
        foreach ($groupedConflicts as &$values) {
            if (count($values) == 1) {
                $nodeIndex = current($values)['nodeIndex'];
                $value = &$node->values[$nodeIndex];
                $this->createFlag($value, 'Conflict', 'Unresolvable');
                continue;
            }

            usort($values, function($a, $b) {
                return $a['priority'] - $b['priority'];
            });

            $keep = $values[0];
            $keepClassName = $this->environment->getClassValue($keep['gibbonCourseClassID'], 'className');

            $groupedConflictIDs = array_column($values, 'gibbonCourseClassID');

            // Look for conflicts with pre-enrolled classes
            if ($this->inArrayWithArray($keep['ttDays'], $enrolmentTTDays)) {
                // Simply flag all conflicts
                foreach ($node->values as &$value) {
                    if (in_array($value['gibbonCourseClassID'], $groupedConflictIDs)) {
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

    protected function removeFlag(&$value)
    {
        $value['flag'] = null;
        $value['reason'] = null;
    }

    protected function inArrayWithArray(array $needle, array $haystack) {
        if (empty($needle) || empty($haystack)) return false;

        foreach ($needle as $value) {
            if (in_array($value, $haystack, true)) return true;
        }

        return false;
    }
}
