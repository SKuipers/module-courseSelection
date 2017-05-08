<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable;

use CourseSelection\DecisionTree\NodeHeuristic;

/**
 * Implementation of the NodeHeuristic interface for the Timetabling Engine
 *
 * @version v14
 * @since   6th May 2017
 */
class Heuristic implements NodeHeuristic
{
    protected $environment;
    protected $settings;

    public function __construct(EngineEnvironment $environment, EngineSettings $settings)
    {
        $this->environment = $environment;
        $this->settings = $settings;
    }

    /**
     * @param   object &$node
     * @param   int    $depth
     * @return  bool
     */
    public function sortOptimalDecisions(&$options, &$node)
    {
        $environment = &$this->environment;

        $periods = array_column($node->values, 'period');

        // Sorts by timetable conflicts first, then number of students in the class
        usort($options, function($a, $b) use ($environment, $periods) {

            if (in_array($a['period'], $periods)) return -2;
            if (in_array($b['period'], $periods)) return 2;

            $aCount = $environment->get($a['className'], 'students');
            $bCount = $environment->get($b['className'], 'students');

            // Avoid filling empty classes first
            if ($aCount == 0) return -1;

            // De-prioritize those that are over the minimum
            if ($aCount >= $this->settings->minimumStudents) {
                return $bCount - $aCount;
            }
            if ($bCount >= $this->settings->minimumStudents) return 1;

            return $aCount - $bCount;
        });

        return $options;
    }
}
