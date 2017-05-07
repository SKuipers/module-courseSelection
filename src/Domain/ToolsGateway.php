<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Domain;

/**
 * Course Selection: Tools Gateway
 *
 * @version v14
 * @since   17th April 2017
 * @author  Sandra Kuipers
 *
 * @uses  courseSelectionOffering
 * @uses  courseSelectionOfferingBlock
 * @uses  courseSelectionBlockCourse
 * @uses  gibbonSchoolYear
 * @uses  gibbonYearGroup
 * @uses  gibbonStudentEnrolment
 * @uses  gibbonPerson
 * @uses  gibbonCourse
 * @uses  gibbonCourseClass
 * @uses  gibbonCourseClassPerson
 */
class ToolsGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function selectSchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectAllCoursesBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonYearGroup.name as grouping, gibbonCourse.gibbonCourseID as value, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) as name
                FROM gibbonCourse
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList))
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonYearGroup.sequenceNumber DESC, gibbonCourse.nameShort, gibbonCourse.name";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectCoursesOfferedBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonCourse.gibbonCourseID as value, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) as name
                FROM courseSelectionOffering
                JOIN courseSelectionOfferingBlock ON (courseSelectionOfferingBlock.courseSelectionOfferingID=courseSelectionOffering.courseSelectionOfferingID)
                JOIN courseSelectionBlockCourse ON (courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionBlockCourse.gibbonCourseID)
                WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourse.gibbonCourseID
                ORDER BY gibbonCourse.nameShort, gibbonCourse.name";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectStudentsByCourse($gibbonCourseID)
    {
        $data = array('gibbonCourseID' => $gibbonCourseID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonYearGroup.name as yearGroupName, CONCAT(gibbonCourse.nameShort, '.',gibbonCourseClass.nameShort) as courseClassName
                FROM gibbonPerson
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                AND gibbonCourse.gibbonCourseID=:gibbonCourseID
                AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                AND gibbonCourseClassPerson.role='Student'
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY gibbonCourseClassPerson.role DESC, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectStudentsByCourseSelection($gibbonCourseID)
    {
        $data = array('gibbonCourseID' => $gibbonCourseID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonYearGroup.name as yearGroupName, (CASE WHEN courseSelectionApproval.courseSelectionChoiceID > 0 THEN 'Approved' ELSE courseSelectionChoice.status END) as courseClassName
                FROM gibbonPerson
                JOIN courseSelectionChoice ON (courseSelectionChoice.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                WHERE courseSelectionChoice.gibbonCourseID=:gibbonCourseID
                AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')
                AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                AND courseSelectionChoice.status != 'Removed'
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->pdo->executeQuery($data, $sql);
    }
}
