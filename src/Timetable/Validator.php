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

use CourseSelection\DecisionTree\NodeValidator;

/**
 * Implementation of the NodeValidator interface for the Timetabling Engine
 *
 * @version v14
 * @since   4th May 2017
 */
class Validator implements NodeValidator
{
    protected $environment;
    protected $settings;

    /**
     * Performance Metrics
     */
    protected $performance = array(
        'nodeValidations'   => 0,
    );

    public function __construct(EngineEnvironment $environment, EngineSettings $settings)
    {
        $this->environment = $environment;
        $this->settings = $settings;
    }

    public function reset()
    {

    }

    /**
     * @param   object  &$node
     * @param   int     &$treeDepth
     * @return  bool
     */
    public function validateNode(&$node, $treeDepth) : bool
    {
        $this->performance['nodeValidations']++;

        foreach ($node->values as $option) {
            if ($this->environment->get($option['className'], 'students') >= $this->settings->maximumClassEnrolment) {
                return false;
            }
        }

        // Look for duplicates by counting the class period occurances
        $periods = array_column($node->values, 'period');
        $periodCounts = array_count_values($periods);

        $node->weight = (count($periodCounts) >= $treeDepth)? 1.0 : 0.0;

        return (count($periodCounts) >= max(0, $treeDepth - $this->settings->timetableConflictTollerance) );
    }

    public function getPerformance()
    {
        return $this->performance;
    }
}
