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
    protected $classData = array();
    protected $studentData = array();

    protected $enrolmentData = array();

    public function getClassData()
    {
        return $this->classData;
    }

    public function setClassData($classData = array())
    {
        $this->classData = $classData;

        foreach ($this->classData as &$course) {
            // Class enrolments can be grouped for purposes of combining student numbers across courses (from course meta data)
            $course['enrolmentGroup'] = (!empty($course['enrolmentGroup']))? $course['enrolmentGroup'] : $course['className'];

            // Build the initial class enrolment counts
            $this->enrolmentData[$course['enrolmentGroup']] = $course['students'] ?? 0;
        }
    }

    public function getStudentData()
    {
        return $this->studentData;
    }

    public function setStudentData($studentData = array())
    {
        $this->studentData = $studentData;
    }

    public function getClassValue($classID, $key)
    {
        return (isset($this->classData[$classID][$key]))? $this->classData[$classID][$key] : null;
    }

    public function setClassValue($classID, $key, $value)
    {
        $this->classData[$classID][$key] = $value;
    }

    public function getStudentValue($studentID, $key)
    {
        return (isset($this->studentData[$studentID][$key]))? $this->studentData[$studentID][$key] : null;
    }

    public function setStudentValue($studentID, $key, $value)
    {
        $this->studentData[$studentID][$key] = $value;
    }

    public function getEnrolmentCount($classID)
    {
        $enrolmentGroup = $this->getClassValue($classID, 'enrolmentGroup');
        return $this->enrolmentData[$enrolmentGroup];
    }

    public function incrementEnrolmentCount($classID, $increment = 1)
    {
        $enrolmentGroup = $this->getClassValue($classID, 'enrolmentGroup');
        $this->enrolmentData[$enrolmentGroup] += $increment;
    }

    public function updateEnrolmentCountsFromResults(&$results)
    {
        if (empty($results)) return;

        foreach ($results as $result) {
            $this->incrementEnrolmentCount($result['className']);
        }
    }
}
