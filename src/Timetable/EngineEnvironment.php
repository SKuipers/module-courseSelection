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
    protected $courseData = array();
    protected $studentData = array();

    public function getCourseData()
    {
        return $this->courseData;
    }

    public function setCourseData($courseData = array())
    {
        $this->courseData = $courseData;
    }

    public function getStudentData()
    {
        return $this->studentData;
    }

    public function setStudentData($studentData = array())
    {
        $this->studentData = $studentData;
    }

    public function getCourseValue($courseID, $key)
    {
        return (isset($this->courseData[$courseID][$key]))? $this->courseData[$courseID][$key] : null;
    }

    public function setCourseValue($courseID, $key, $value)
    {
        $this->courseData[$courseID][$key] = $value;
    }

    public function getStudentValue($studentID, $key)
    {
        return (isset($this->studentData[$studentID][$key]))? $this->studentData[$studentID][$key] : null;
    }

    public function setStudentValue($studentID, $key, $value)
    {
        $this->studentData[$studentID][$key] = $value;
    }

    public function updateStudentCounts(&$results)
    {
        if (empty($results)) return;

        foreach ($results as $result) {
            $classEnrolment = $this->getCourseValue($result['className'], 'students');
            $this->setCourseValue($result['className'], 'students', $classEnrolment+1);
        }
    }

    public function combineSmallClasses(&$resultSet, $minimum, $maximum)
    {
        $smallCourses = array_reduce($this->courseData, function($classes, $class) use ($minimum) {
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
}
