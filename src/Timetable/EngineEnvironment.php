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

        foreach ($this->classData as $course) {
            // Class enrolments can be grouped for purposes of combining student numbers across courses (from course meta data)
            $period = strval($course['period']);
            $enrolmentGroup = (!empty($course['enrolmentGroup']))? $course['enrolmentGroup'] : $course['gibbonCourseClassID'];
            $this->setClassValue($course['gibbonCourseClassID'], 'enrolmentGroup', $enrolmentGroup);

            // Build the initial class enrolment counts
            //$this->enrolmentData[$enrolmentGroup][$period]['total'] = $course['students'] ?? 0;
            //$this->enrolmentData[$enrolmentGroup][$period]['M'] = $course['studentsMale'] ?? 0;
            //$this->enrolmentData[$enrolmentGroup][$period]['F'] = $course['studentsFemale'] ?? 0;

            $this->enrolmentData[$enrolmentGroup][$period]['total'] = 0;
            $this->enrolmentData[$enrolmentGroup][$period]['M'] = 0;
            $this->enrolmentData[$enrolmentGroup][$period]['F'] = 0;
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
        return (isset($this->classData[$classID][$key]))? $this->classData[$classID][$key] : 0;
    }

    public function setClassValue($classID, $key, $value)
    {
        $this->classData[$classID][$key] = $value;
    }

    public function getStudentValue($studentID, $key)
    {
        return (isset($this->studentData[$studentID][$key]))? $this->studentData[$studentID][$key] : 0;
    }

    public function setStudentValue($studentID, $key, $value)
    {
        $this->studentData[$studentID][$key] = $value;
    }

    public function getEnrolmentCount($classID, $group = 'total')
    {
        $enrolmentGroup = $this->getClassValue($classID, 'enrolmentGroup');
        $period = $this->getClassValue($classID, 'period');

        return $this->enrolmentData[$enrolmentGroup][$period][$group];
    }

    public function incrementEnrolmentCount($classID, $studentID, $increment = 1)
    {
        $enrolmentGroup = $this->getClassValue($classID, 'enrolmentGroup');
        $period = $this->getClassValue($classID, 'period');
        $gender = $this->getStudentValue($studentID, 'gender');

        $this->enrolmentData[$enrolmentGroup][$period]['total'] += $increment;
        $this->enrolmentData[$enrolmentGroup][$period][$gender] += $increment;
    }

    public function updateEnrolmentCountsFromResult(&$result)
    {
        if (empty($result)) return;

        foreach ($result->values as $value) {
            if (empty($value)) continue;
            if (!empty($value['flag'])) continue;

            $this->incrementEnrolmentCount($value['gibbonCourseClassID'], $value['gibbonPersonID']);
        }
    }
}
