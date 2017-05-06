<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
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

        // Filter options for classes that are not full
        // $options = array_filter($options, function($option) use ($environment) {
        //     return $environment->get($option['className'], 'students') < $this->settings->maximumClassEnrolment;
        // });

        $periods = array_column($node->values, 'period');

        // Sorts by timetable conflicts first, then number of students in the class
        usort($options, function($a, $b) use ($environment, $periods) {

            if (in_array($a['period'], $periods)) return -1;
            if (in_array($b['period'], $periods)) return 1;

            $aCount = $environment->get($a['className'], 'students');
            $bCount = $environment->get($b['className'], 'students');

            //if ($aCount == 0) return -1;
            //if ($bCount == 0) return 1;

            if ($aCount >= $this->settings->minimumClassEnrolment) {
                return $bCount - $aCount;
            }
            //if ($aCount >= $this->settings->minimumClassEnrolment) return -1;
            if ($bCount >= $this->settings->minimumClassEnrolment) return 1;

            return $aCount - $bCount;
        });

        return $options;
    }
}
