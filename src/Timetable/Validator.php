<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
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

        // Invalidate this node if there are any full classes
        foreach ($node->values as $option) {
            if ($this->environment->getClassValue($option['className'], 'students') >= $this->settings->maximumStudents) {
                return false;
            }
        }

        // Look for duplicates by counting the class period occurances
        $periods = array_column($node->values, 'period');
        $periodCounts = array_count_values($periods);

        $node->weight = (count($periodCounts) >= $treeDepth)? 1.0 : -1.0;

        return (count($periodCounts) >= max(0, $treeDepth - $this->settings->timetableConflictTollerance) );
    }

    public function getPerformance()
    {
        return $this->performance;
    }
}
