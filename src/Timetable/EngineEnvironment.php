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
