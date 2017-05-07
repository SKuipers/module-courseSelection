<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Timetable;

/**
 * Timetabling Engine: Environment
 *
 * Holds the additional data about the timetable as a whole, nessesary to make individual decisions.
 *
 * @version v14
 * @since   4th May 2017
 */
class EngineEnvironment
{
    protected $data = array();

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public function get($course, $key)
    {
        return (isset($this->data[$course][$key]))? $this->data[$course][$key] : null;
    }

    public function set($course, $key, $value)
    {
        $this->data[$course][$key] = $value;
    }

    public function updateStudentCounts(&$results)
    {
        if (empty($results)) return;

        foreach ($results as $result) {
            $classEnrolment = $this->get($result['className'], 'students');
            $this->set($result['className'], 'students', $classEnrolment+1);
        }
    }

    public function combineSmallClasses(&$resultSet, $minimum, $maximum)
    {
        $smallCourses = array_reduce($this->data, function($classes, $class) use ($minimum) {
            if ($class['students'] < $minimum) {
                $classes[$class['courseNameShort']][] = $class;
            }
            return $classes;
        }, array());

        $combineClasses = array_reduce($smallCourses, function($classes, $courseClasses) use ($maximum) {

            if (count($courseClasses) <= 1) return $classes;

            $rootClass = current($courseClasses);

            foreach ($courseClasses as $class) {
                if ($rootClass['students'] + $class['students'] <= $maximum) {
                    $classes[$rootClass['className']][] = $class['className'];
                    $rootClass['students'] += $class['students'];
                }
            }

            return $classes;
        }, array());

    }

    public function getData()
    {
        return $this->data;
    }
}
