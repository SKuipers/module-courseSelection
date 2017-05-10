<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Domain;

/**
 * Course Selection: Timetable Gateway
 *
 * @version v14
 * @since   4th May 2017
 * @author  Sandra Kuipers
 *
 * @uses
 */
class TimetableGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function selectTimetabledClassesBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonCourseClass.gibbonCourseClassID as groupBy, COUNT(DISTINCT gibbonCourseClassPerson.gibbonPersonID) as students, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) as className, gibbonCourseClass.nameShort as period, gibbonCourseClass.gibbonCourseClassID, courseSelectionMetaData.enrolmentGroup, courseSelectionMetaData.timetablePriority as priority
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN courseSelectionOffering ON (courseSelectionOffering.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                JOIN courseSelectionOfferingRestriction ON (courseSelectionOfferingRestriction.courseSelectionOfferingID=courseSelectionOffering.courseSelectionOfferingID AND FIND_IN_SET(courseSelectionOfferingRestriction.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList))
                LEFT JOIN courseSelectionMetaData ON (courseSelectionMetaData.gibbonCourseID=gibbonCourse.gibbonCourseID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.role='Student')
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClass.gibbonCourseClassID";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectApprovedCourseSelectionsBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT courseSelectionChoice.gibbonPersonIDStudent, gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID, gibbonCourseClass.nameShort as period, (CASE WHEN gibbonCourseClassPerson.gibbonCourseClassID IS NOT NULL THEN 1 ELSE 0 END) as currentEnrolment
                FROM courseSelectionChoice
                JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=courseSelectionChoice.gibbonPersonIDStudent)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                LEFT JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.gibbonPersonID=courseSelectionChoice.gibbonPersonIDStudent)
                WHERE courseSelectionChoice.gibbonSchoolYearID=:gibbonSchoolYearID
                AND courseSelectionChoice.status <> 'Removed'
                AND courseSelectionChoice.status <> 'Recommended'
                AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')
                GROUP BY courseSelectionChoice.courseSelectionChoiceID, gibbonCourseClass.gibbonCourseClassID
                ORDER BY gibbonYearGroup.sequenceNumber DESC, RAND(), gibbonCourse.orderBy, gibbonCourse.nameShort";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectTimetabledStudentsBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.gender
                FROM gibbonPerson
                JOIN courseSelectionChoice ON (courseSelectionChoice.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID)
                JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                WHERE courseSelectionChoice.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonPerson.gibbonPersonID";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectCourseResultsBySchoolYear($gibbonSchoolYearID, $orderBy = 'nameShort')
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT GROUP_CONCAT(DISTINCT gibbonCourse.name ORDER BY gibbonCourse.name SEPARATOR '<br>') as courseName, GROUP_CONCAT(DISTINCT CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) ORDER BY gibbonCourse.nameShort, gibbonCourseClass.nameShort SEPARATOR '<br>') as className, COUNT(DISTINCT courseSelectionTTResult.gibbonPersonIDStudent) as students, SUM(CASE WHEN gibbonPerson.gender = 'M' THEN 1 ELSE 0 END) as studentsMale, SUM(CASE WHEN gibbonPerson.gender = 'F' THEN 1 ELSE 0 END) as studentsFemale, (CASE WHEN courseSelectionMetaData.enrolmentGroup IS NOT NULL THEN CONCAT(courseSelectionMetaData.enrolmentGroup,'.',gibbonCourseClass.nameShort) ELSE CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) END) as enrolmentGroupName
                FROM courseSelectionTTResult
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionTTResult.gibbonCourseID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=courseSelectionTTResult.gibbonCourseClassID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=courseSelectionTTResult.gibbonPersonIDStudent)
                LEFT JOIN courseSelectionMetaData ON (courseSelectionMetaData.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE courseSelectionTTResult.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY enrolmentGroupName";

        if ($orderBy == 'count') {
            $sql .= " ORDER BY students DESC, gibbonCourse.nameShort, gibbonCourse.name";
        } else if ($orderBy == 'order') {
            $sql .= " ORDER BY gibbonCourse.orderBy, gibbonCourse.nameShort, gibbonCourse.name";
        } else if ($orderBy == 'name') {
            $sql .= " ORDER BY gibbonCourse.name, gibbonCourse.nameShort";
        } else {
            $sql .= " ORDER BY gibbonCourse.nameShort, gibbonCourse.name";
        }

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectStudentResultsBySchoolYear($gibbonSchoolYearID, $orderBy = 'surname')
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT courseSelectionTTResult.gibbonPersonIDStudent, gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonRollGroup.nameShort as rollGroupName, courseSelectionTTResult.weight, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourseClass.nameShort as classNameShort, gibbonCourse.gibbonCourseID, gibbonCourseClass.gibbonCourseClassID
                FROM courseSelectionTTResult
                LEFT JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionTTResult.gibbonCourseID)
                LEFT JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=courseSelectionTTResult.gibbonCourseClassID)
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=courseSelectionTTResult.gibbonPersonIDStudent)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                WHERE courseSelectionTTResult.gibbonSchoolYearID=:gibbonSchoolYearID
                AND gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Current')
                AND (gibbonPerson.status = 'Full' OR gibbonPerson.status = 'Expected')
                GROUP BY gibbonPerson.gibbonPersonID, courseSelectionTTResult.courseSelectionTTResultID
        ";

        if ($orderBy == 'count') {
            $sql .= " ORDER BY approvalCount DESC, LENGTH(gibbonRollGroup.nameShort), gibbonRollGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else if ($orderBy == 'rollGroup') {
            $sql .= " ORDER BY LENGTH(gibbonRollGroup.nameShort), gibbonRollGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else if ($orderBy == 'weight') {
            $sql .= " ORDER BY courseSelectionTTResult.weight, gibbonPerson.surname, gibbonPerson.preferredName";
        } else {
            $sql .= " ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
        }

        return $this->pdo->executeQuery($data, $sql);
    }

    public function insertResult(array $data)
    {
        $sql = "INSERT INTO courseSelectionTTResult SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseID=:gibbonCourseID, gibbonCourseClassID=:gibbonCourseClassID, weight=:weight";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function insertFlag(array $data)
    {
        $sql = "INSERT INTO courseSelectionTTFlag SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseClassID=:gibbonCourseClassID, scope:scope, type=:type, reason=:reason";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function deleteAllResultsBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "DELETE FROM courseSelectionTTResult WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $this->pdo->executeQuery($data, $sql);

        $sql = "DELETE FROM courseSelectionTTFlag WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function transformResultsIntoClassEnrolments($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "INSERT IGNORE INTO gibbonCourseClassPerson (gibbonCourseClassID, gibbonPersonID, role) SELECT gibbonCourseClassID, gibbonPersonIDStudent, 'Student' FROM courseSelectionTTResult WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }
}
