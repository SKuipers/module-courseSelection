<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace Gibbon\Module\CourseSelection\Domain;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Contracts\Database\Connection;

/**
 * Course Selection: courseSelectionChoice Table Gateway
 *
 * @version v14
 * @since   16th April 2017
 * @author  Sandra Kuipers
 *
 * @uses  courseSelectionOffering
 * @uses  courseSelectionOfferingBlock
 * @uses  courseSelectionBlockCourse
 * @uses  courseSelectionChoice
 * @uses  courseSelectionChoiceOffering
 * @uses  courseSelectionLog
 * @uses  gibbonSchoolYear
 * @uses  gibbonPerson
 * @uses  gibbonStudentEnrolment
 *
 */
class SelectionsGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'courseSelectionChoice';
    private static $primaryKey = 'courseSelectionChoiceID';
    private static $searchableColumns = [];

    // CHOICES

    public function selectChoicesByBlockAndPerson($courseSelectionBlockID, $gibbonPersonIDStudent)
    {
        $data = array('courseSelectionBlockID' => $courseSelectionBlockID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT courseSelectionChoice.gibbonCourseID, courseSelectionChoice.*, (CASE WHEN courseSelectionApproval.courseSelectionChoiceID > 0 THEN 'Approved' ELSE '' END) as approved
                FROM courseSelectionOfferingBlock
                JOIN courseSelectionBlockCourse ON (courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID)
                JOIN courseSelectionChoice ON (courseSelectionBlockCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                WHERE courseSelectionOfferingBlock.courseSelectionBlockID=:courseSelectionBlockID
                AND courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
                AND courseSelectionChoice.status <> 'Removed'
                AND (courseSelectionChoice.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID OR courseSelectionChoice.courseSelectionBlockID IS NULL)
                GROUP BY courseSelectionChoice.gibbonCourseID
                ORDER BY courseSelectionChoice.status";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function queryChoicesByCourse($criteria, $gibbonCourseID, $excludeStatusList = [])
    {
        $excludeStatusList = implode(',', $excludeStatusList);

        $query = $this
            ->newQuery()
            ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'courseSelectionChoice.status', 'courseSelectionChoice.gibbonPersonIDSelected', 'courseSelectionChoice.timestampSelected', 'selectedPerson.gibbonPersonID as selectedPersonID', 'selectedPerson.surname as selectedSurname', 'selectedPerson.preferredName as selectedPreferredName', 'courseSelectionChoiceOffering.courseSelectionOfferingID', 'gibbonFormGroup.nameShort as formGroupName', 'courseSelectionBlock.courseSelectionBlockID', 'courseSelectionBlock.countable as blockIsCountable'])
            ->from($this->getTableName())
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=courseSelectionChoice.gibbonPersonIDStudent')
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID')
            ->leftJoin('courseSelectionChoiceOffering', 'courseSelectionChoiceOffering.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID AND courseSelectionChoiceOffering.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID')
            ->leftJoin('courseSelectionBlock', 'courseSelectionChoice.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID')
            ->innerJoin('gibbonPerson AS selectedPerson', 'selectedPerson.gibbonPersonID=courseSelectionChoice.gibbonPersonIDSelected')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonFormGroup.gibbonFormGroupID=gibbonStudentEnrolment.gibbonFormGroupID')
            ->groupBy(['courseSelectionChoice.gibbonPersonIDStudent'])
            ->where('courseSelectionChoice.gibbonCourseID=:gibbonCourseID')
            ->bindValue('gibbonCourseID', $gibbonCourseID)
            ->where('courseSelectionChoice.status NOT IN (:exclude)')
            ->where('gibbonStudentEnrolment.gibbonSchoolYearID=(SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status=\'Current\')')
            ->bindValue('exclude', $excludeStatusList);

        return $this->runQuery($query, $criteria);
    }

    public function selectChoicesByOfferingAndPerson($courseSelectionOfferingID, $gibbonPersonIDStudent)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, courseSelectionChoice.status, courseSelectionChoice.gibbonPersonIDSelected, courseSelectionChoice.timestampSelected, courseSelectionChoiceOffering.courseSelectionOfferingID, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, courseSelectionChoice.courseSelectionChoiceID, (CASE WHEN courseSelectionApproval.courseSelectionChoiceID IS NOT NULL THEN 'Approved' ELSE '' END) as approval
                FROM courseSelectionChoice
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=courseSelectionChoice.gibbonPersonIDStudent)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                JOIN courseSelectionChoiceOffering ON (
                    courseSelectionChoiceOffering.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID
                    AND courseSelectionChoiceOffering.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                )
                LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                LEFT JOIN courseSelectionOfferingBlock ON (courseSelectionOfferingBlock.courseSelectionBlockID=courseSelectionChoice.courseSelectionBlockID)
                WHERE courseSelectionChoiceOffering.courseSelectionOfferingID=:courseSelectionOfferingID
                AND gibbonPerson.gibbonPersonID=:gibbonPersonIDStudent
                AND courseSelectionChoice.status <> 'Removed'
                AND courseSelectionChoice.status <> 'Recommended'
                GROUP BY courseSelectionChoice.courseSelectionChoiceID
                ORDER BY courseSelectionOfferingBlock.sequenceNumber, gibbonCourse.name";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function selectChoiceByCourseAndPerson($gibbonCourseID, $gibbonPersonIDStudent)
    {
        $data = array('gibbonCourseID' => $gibbonCourseID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT courseSelectionChoice.*, (CASE WHEN courseSelectionApproval.courseSelectionChoiceID > 0 THEN 'Approved' ELSE '' END) as approved
                FROM courseSelectionChoice
                LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                WHERE gibbonCourseID=:gibbonCourseID
                AND gibbonPersonIDStudent=:gibbonPersonIDStudent";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function selectUnofferedChoicesByPerson($courseSelectionOfferingID, $gibbonPersonIDStudent)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT courseSelectionChoice.gibbonCourseID, courseSelectionChoice.*, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, (
                    SELECT COUNT(*) as count FROM courseSelectionBlockCourse
                    JOIN courseSelectionOfferingBlock ON (courseSelectionOfferingBlock.courseSelectionBlockID=courseSelectionBlockCourse.courseSelectionBlockID)
                    WHERE courseSelectionBlockCourse.gibbonCourseID=gibbonCourse.gibbonCourseID
                    AND courseSelectionOfferingBlock.courseSelectionOfferingID=:courseSelectionOfferingID
                ) AS offeringBlockCount
                FROM courseSelectionChoice
                JOIN courseSelectionOffering ON (courseSelectionOffering.gibbonSchoolYearID=courseSelectionChoice.gibbonSchoolYearID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                WHERE courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
                AND courseSelectionOffering.courseSelectionOfferingID=:courseSelectionOfferingID
                AND gibbonCourse.gibbonSchoolYearID=courseSelectionOffering.gibbonSchoolYearID
                AND courseSelectionChoice.status <> 'Removed'
                GROUP BY courseSelectionChoice.gibbonCourseID
                HAVING (offeringBlockCount = 0)
                ORDER BY gibbonCourse.nameShort, gibbonCourse.name";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function insertChoice(array $data)
    {
        $sql = "INSERT INTO courseSelectionChoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseID=:gibbonCourseID, courseSelectionBlockID=:courseSelectionBlockID, status=:status, gibbonPersonIDSelected=:gibbonPersonIDSelected, timestampSelected=:timestampSelected, notes=:notes";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function updateChoice(array $data)
    {
        $sql = "UPDATE courseSelectionChoice SET gibbonSchoolYearID=:gibbonSchoolYearID, status=:status, gibbonPersonIDSelected=:gibbonPersonIDSelected, timestampSelected=:timestampSelected, courseSelectionBlockID=:courseSelectionBlockID, notes=:notes WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonCourseID=:gibbonCourseID";
        $result = $this->db()->update($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function deleteChoice($courseSelectionChoiceID)
    {
        $data = array('courseSelectionChoiceID' => $courseSelectionChoiceID);
        $sql = "DELETE FROM courseSelectionChoice WHERE courseSelectionChoiceID=:courseSelectionChoiceID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function updateUnselectedChoicesBySchoolYearAndPerson($gibbonSchoolYearID, $gibbonPersonIDStudent, $courseIDList)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);

        if (!empty($courseIDList)) {
            $sql = "UPDATE courseSelectionChoice
                    LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                    SET courseSelectionChoice.status='Removed'
                    WHERE courseSelectionChoice.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
                    AND gibbonCourseID NOT IN ({$courseIDList})
                    AND (courseSelectionChoice.status='Requested' OR courseSelectionChoice.status='Selected' OR courseSelectionChoice.status='')
                    AND courseSelectionApproval.courseSelectionChoiceID IS NULL";
        } else {
            $sql = "UPDATE courseSelectionChoice
                    LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                    SET courseSelectionChoice.status='Removed'
                    WHERE courseSelectionChoice.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
                    AND (courseSelectionChoice.status='Requested' OR courseSelectionChoice.status='Selected' OR courseSelectionChoice.status='')
                    AND courseSelectionApproval.courseSelectionChoiceID IS NULL";
        }

        $result = $this->db()->update($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    // APPROVAL

    public function insertApproval(array $data)
    {
        $sql = "INSERT INTO courseSelectionApproval SET courseSelectionChoiceID=:courseSelectionChoiceID, gibbonPersonIDApproved=:gibbonPersonIDApproved, timestampApproved=:timestampApproved ON DUPLICATE KEY UPDATE gibbonPersonIDApproved=:gibbonPersonIDApproved, timestampApproved=:timestampApproved";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function deleteApproval($courseSelectionChoiceID)
    {
        $data = array('courseSelectionChoiceID' => $courseSelectionChoiceID);
        $sql = "DELETE FROM courseSelectionApproval WHERE courseSelectionChoiceID=:courseSelectionChoiceID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    // LOG

    public function queryAllLogsBySchoolYear($criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->cols(['courseSelectionLog.*', 'gibbonSchoolYear.name as schoolYearName', 'courseSelectionOffering.name as offeringName', 'gibbonPersonStudent.surname AS studentSurname', 'gibbonPersonStudent.preferredName AS studentPreferredName', 'gibbonPersonChanged.surname AS changedSurname', 'gibbonPersonChanged.preferredName AS changedPreferredName'])
            ->from('courseSelectionLog')
            ->innerJoin('courseSelectionOffering', 'courseSelectionOffering.courseSelectionOfferingID=courseSelectionLog.courseSelectionOfferingID')
            ->innerJoin('gibbonSchoolYear', 'gibbonSchoolYear.gibbonSchoolYearID=courseSelectionLog.gibbonSchoolYearID')
            ->innerJoin('gibbonPerson AS gibbonPersonStudent', 'gibbonPersonStudent.gibbonPersonID=courseSelectionLog.gibbonPersonIDStudent')
            ->leftJoin('gibbonPerson AS gibbonPersonChanged', 'gibbonPersonChanged.gibbonPersonID=courseSelectionLog.gibbonPersonIDChanged')
            ->where('courseSelectionLog.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function insertLog(array $data)
    {
        $sql = "INSERT INTO courseSelectionLog SET gibbonSchoolYearID=:gibbonSchoolYearID, courseSelectionOfferingID=:courseSelectionOfferingID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonPersonIDChanged=:gibbonPersonIDChanged, timestampChanged=:timestampChanged, action=:action";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    // OFFERINGS

    public function selectChoiceOffering($gibbonSchoolYearID, $gibbonPersonIDStudent)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT courseSelectionOfferingID, gibbonSchoolYearID, gibbonPersonIDStudent FROM courseSelectionChoiceOffering WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonIDStudent";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function insertChoiceOffering(array $data)
    {
        $sql = "INSERT INTO courseSelectionChoiceOffering SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonIDStudent=:gibbonPersonIDStudent, courseSelectionOfferingID=:courseSelectionOfferingID ON DUPLICATE KEY UPDATE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function deleteChoiceOffering($gibbonSchoolYearID, $gibbonPersonIDStudent)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "DELETE FROM courseSelectionChoiceOffering WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonIDStudent";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    // MISC

    public function queryStudentsWithIncompleteSelections($criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->cols(['gibbonPerson.gibbonPersonID', 'gibbonPerson.surname', 'gibbonPerson.preferredName', 'courseSelectionOffering.courseSelectionOfferingID', 'gibbonFormGroup.nameShort AS formGroupName', 'COUNT(DISTINCT courseSelectionChoice.gibbonCourseID) AS choiceCount', 'selectedOffering.minSelect', 'selectedOffering.maxSelect', 'selectedOffering.courseSelectionOfferingID AS selectedOfferingID', 'selectedOffering.name AS selectedOfferingName', 'COUNT(DISTINCT courseSelectionApproval.courseSelectionChoiceID) AS approvalCount'])
            ->from('gibbonPerson')
            ->innerJoin('gibbonStudentEnrolment', 'gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID')
            ->innerJoin('gibbonFormGroup', 'gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID')
            ->innerJoin('courseSelectionOfferingRestriction', '(courseSelectionOfferingRestriction.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID AND courseSelectionOfferingRestriction.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)')
            ->innerJoin('courseSelectionOffering', 'courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID')
            ->leftJoin('courseSelectionChoice', "courseSelectionChoice.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND courseSelectionChoice.gibbonSchoolYearID=courseSelectionOffering.gibbonSchoolYearID AND courseSelectionChoice.status <> 'Removed' AND courseSelectionChoice.status <> 'Recommended'")
            ->leftJoin('courseSelectionChoiceOffering', 'courseSelectionChoiceOffering.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID AND courseSelectionChoiceOffering.gibbonSchoolYearID=courseSelectionOffering.gibbonSchoolYearID')
            ->leftJoin('courseSelectionOffering as selectedOffering', 'selectedOffering.courseSelectionOfferingID=courseSelectionChoiceOffering.courseSelectionOfferingID')
            ->leftJoin('courseSelectionApproval', 'courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID')
            ->where('courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("(gibbonPerson.status = 'Full' OR gibbonPerson.status = 'Expected')")
            ->having('approvalCount<choiceCount AND choiceCount>0')
            ->groupBy(['gibbonPerson.gibbonPersonID']);

        return $this->runQuery($query, $criteria);
    }

    // It ought to be possible to remove this method once table refactoring is complete
    public function selectStudentsWithIncompleteSelections($gibbonSchoolYearID, $orderBy = 'surname')
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, courseSelectionOffering.courseSelectionOfferingID, gibbonFormGroup.nameShort as formGroupName, COUNT(DISTINCT courseSelectionChoice.gibbonCourseID) as choiceCount, selectedOffering.minSelect, selectedOffering.maxSelect, selectedOffering.courseSelectionOfferingID as selectedOfferingID, selectedOffering.name as selectedOfferingName, COUNT(DISTINCT courseSelectionApproval.courseSelectionChoiceID) as approvalCount
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                JOIN courseSelectionOfferingRestriction ON (
                    courseSelectionOfferingRestriction.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID
                    AND courseSelectionOfferingRestriction.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                JOIN courseSelectionOffering ON (courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID)
                LEFT JOIN courseSelectionChoice ON (courseSelectionChoice.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                    AND courseSelectionChoice.gibbonSchoolYearID=courseSelectionOffering.gibbonSchoolYearID
                    AND courseSelectionChoice.status <> 'Removed' AND courseSelectionChoice.status <> 'Recommended')
                LEFT JOIN courseSelectionChoiceOffering ON (courseSelectionChoiceOffering.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                    AND courseSelectionChoiceOffering.gibbonSchoolYearID=courseSelectionOffering.gibbonSchoolYearID)
                LEFT JOIN courseSelectionOffering as selectedOffering ON (selectedOffering.courseSelectionOfferingID=courseSelectionChoiceOffering.courseSelectionOfferingID)
                LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                AND (gibbonPerson.status = 'Full' OR gibbonPerson.status = 'Expected')
                GROUP BY gibbonPerson.gibbonPersonID
        ";

        if ($orderBy == 'choiceCount') {
            $sql .= " ORDER BY choiceCount DESC, LENGTH(gibbonFormGroup.nameShort), gibbonFormGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else if ($orderBy == 'approvalCount') {
            $sql .= " ORDER BY approvalCount DESC, LENGTH(gibbonFormGroup.nameShort), gibbonFormGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else if ($orderBy == 'formGroup') {
            $sql .= " ORDER BY LENGTH(gibbonFormGroup.nameShort), gibbonFormGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else {
            $sql .= " ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
        }

        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function selectStudentsByOffering($courseSelectionOfferingID, $orderBy = 'surname')
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240, courseSelectionOffering.courseSelectionOfferingID, gibbonFormGroup.nameShort as formGroupName, courseSelectionChoiceOffering.courseSelectionOfferingID as selectedOfferingID
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonFormGroup ON (gibbonStudentEnrolment.gibbonFormGroupID=gibbonFormGroup.gibbonFormGroupID)
                JOIN courseSelectionOfferingRestriction ON (
                    courseSelectionOfferingRestriction.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID
                    AND courseSelectionOfferingRestriction.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                JOIN courseSelectionOffering ON (courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID)
                JOIN courseSelectionChoiceOffering ON (courseSelectionChoiceOffering.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                    AND courseSelectionChoiceOffering.courseSelectionOfferingID=courseSelectionOffering.courseSelectionOfferingID)
                WHERE courseSelectionOffering.courseSelectionOfferingID=:courseSelectionOfferingID
                AND (gibbonPerson.status = 'Full' OR gibbonPerson.status = 'Expected')
                GROUP BY gibbonPerson.gibbonPersonID
        ";

        if ($orderBy == 'formGroup') {
            $sql .= " ORDER BY LENGTH(gibbonFormGroup.nameShort), gibbonFormGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else {
            $sql .= " ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
        }

        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function selectStudentDetails($gibbonPersonIDStudent)
    {
        $data = array('gibbonPersonID' => $gibbonPersonIDStudent);
        $sql = "SELECT gibbonPerson.surname, gibbonPerson.preferredName
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                ORDER BY gibbonStudentEnrolment.gibbonSchoolYearID DESC
                LIMIT 1";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function queryChoiceCountsBySchoolYear($criteria, $gibbonSchoolYearID, $countable = 'Y')
    {
        $query = $this
            ->newQuery()
            ->cols(['COUNT(DISTINCT CASE WHEN courseSelectionBlock.countable=:countable OR (courseSelectionChoice.courseSelectionBlockID IS NULL AND :countable=\'Y\') THEN courseSelectionChoice.gibbonPersonIDStudent END) as count', 'gibbonCourse.gibbonCourseID', 'gibbonCourse.name as courseName', 'gibbonCourse.nameShort as courseNameShort'])
            ->from($this->getTableName())
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID')
            ->innerJoin('gibbonPerson', 'gibbonPerson.gibbonPersonID=courseSelectionChoice.gibbonPersonIDStudent')
            ->leftJoin('courseSelectionBlock', 'courseSelectionChoice.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID')
            ->where('(courseSelectionBlock.countable=:countable OR (courseSelectionChoice.courseSelectionBlockID IS NULL AND :countable=\'Y\'))')
            ->bindValue('countable', $countable)
            ->where('courseSelectionChoice.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID)
            ->where("courseSelectionChoice.status <> 'Removed' AND courseSelectionChoice.status <> 'Recommended'")
            ->where("(gibbonPerson.status = 'Full' OR gibbonPerson.status = 'Expected')")
            ->groupBy(['gibbonCourse.gibbonCourseID']);

        return $this->runQuery($query, $criteria);
    }
}
