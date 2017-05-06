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

    public function __construct(EngineEnvironment $environment)
    {
        $this->environment = $environment;
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

        usort($options, function($a, $b) use ($environment, $periods) {

            if (in_array($a['period'], $periods)) return 1;
            if (in_array($b['period'], $periods)) return 1;

            $aCount = $environment->get($a['className'], 'students');
            $bCount = $environment->get($b['className'], 'students');

            return $bCount - $aCount;
        });

        return $options;
    }
}
