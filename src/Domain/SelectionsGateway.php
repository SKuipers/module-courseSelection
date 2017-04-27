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

namespace Gibbon\Modules\CourseSelection\Domain;

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
class SelectionsGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    // CHOICES

    public function selectChoicesByBlockAndPerson($courseSelectionBlockID, $gibbonPersonIDStudent)
    {
        $data = array('courseSelectionBlockID' => $courseSelectionBlockID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT courseSelectionChoice.gibbonCourseID, courseSelectionChoice.*
                FROM courseSelectionOfferingBlock
                JOIN courseSelectionBlockCourse ON (courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID)
                JOIN courseSelectionChoice ON (courseSelectionBlockCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                WHERE courseSelectionOfferingBlock.courseSelectionBlockID=:courseSelectionBlockID
                AND courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
                AND courseSelectionChoice.status <> 'Removed'
                AND (courseSelectionChoice.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID OR courseSelectionChoice.courseSelectionBlockID IS NULL)
                GROUP BY courseSelectionChoice.gibbonCourseID
                ORDER BY courseSelectionChoice.status";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectChoicesByCourse($gibbonCourseID, $excludeStatusList = array())
    {
        $data = array('gibbonCourseID' => $gibbonCourseID, 'exclude' => implode(',', $excludeStatusList));
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, courseSelectionChoice.status, courseSelectionChoice.gibbonPersonIDSelected, courseSelectionChoice.timestampSelected, selectedPerson.gibbonPersonID as selectedPersonID, selectedPerson.surname as selectedSurname, selectedPerson.preferredName as selectedPreferredName, courseSelectionChoiceOffering.courseSelectionOfferingID
                FROM courseSelectionChoice
                JOIN gibbonPerson ON (gibbonPerson.gibbonPersonID=courseSelectionChoice.gibbonPersonIDStudent)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                LEFT JOIN courseSelectionChoiceOffering ON (
                    courseSelectionChoiceOffering.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID
                    AND courseSelectionChoiceOffering.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                )
                JOIN gibbonPerson AS selectedPerson ON (selectedPerson.gibbonPersonID=courseSelectionChoice.gibbonPersonIDSelected)
                WHERE courseSelectionChoice.gibbonCourseID=:gibbonCourseID
                AND courseSelectionChoice.status NOT IN (:exclude)
                GROUP BY courseSelectionChoice.gibbonPersonIDStudent
                ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectChoicesByOfferingAndPerson($courseSelectionOfferingID, $gibbonPersonIDStudent)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, courseSelectionChoice.status, courseSelectionChoice.gibbonPersonIDSelected, courseSelectionChoice.timestampSelected, courseSelectionChoiceOffering.courseSelectionOfferingID, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, gibbonCourse.credits, courseSelectionChoice.courseSelectionChoiceID, (CASE WHEN courseSelectionApproval.courseSelectionChoiceID IS NOT NULL THEN 'Approved' ELSE '' END) as approval
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
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectChoiceByCourseAndPerson($gibbonCourseID, $gibbonPersonIDStudent)
    {
        $data = array('gibbonCourseID' => $gibbonCourseID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT * FROM courseSelectionChoice WHERE gibbonCourseID=:gibbonCourseID AND gibbonPersonIDStudent=:gibbonPersonIDStudent";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectUnofferedChoicesByPerson($courseSelectionOfferingID, $gibbonPersonIDStudent)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT courseSelectionChoice.gibbonCourseID, courseSelectionChoice.*, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, (SELECT COUNT(*) as count FROM courseSelectionBlockCourse JOIN courseSelectionOfferingBlock ON (courseSelectionOfferingBlock.courseSelectionBlockID=courseSelectionBlockCourse.courseSelectionBlockID) WHERE courseSelectionBlockCourse.gibbonCourseID=gibbonCourse.gibbonCourseID AND courseSelectionOfferingBlock.courseSelectionOfferingID=:courseSelectionOfferingID) AS offeringBlockCount
                FROM courseSelectionChoice
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                WHERE courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
                AND courseSelectionChoice.status <> 'Removed'
                GROUP BY courseSelectionChoice.gibbonCourseID
                HAVING (offeringBlockCount = 0)
                ORDER BY gibbonCourse.nameShort, gibbonCourse.name";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function insertChoice(array $data)
    {
        $sql = "INSERT INTO courseSelectionChoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseID=:gibbonCourseID, courseSelectionBlockID=:courseSelectionBlockID, status=:status, gibbonPersonIDSelected=:gibbonPersonIDSelected, timestampSelected=:timestampSelected, notes=:notes";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function updateChoice(array $data)
    {
        $sql = "UPDATE courseSelectionChoice SET gibbonSchoolYearID=:gibbonSchoolYearID, status=:status, gibbonPersonIDSelected=:gibbonPersonIDSelected, timestampSelected=:timestampSelected, courseSelectionBlockID=:courseSelectionBlockID, notes=:notes WHERE gibbonPersonIDStudent=:gibbonPersonIDStudent AND gibbonCourseID=:gibbonCourseID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function deleteChoice($courseSelectionChoiceID)
    {
        $data = array('courseSelectionChoiceID' => $courseSelectionChoiceID);
        $sql = "DELETE FROM courseSelectionChoice WHERE courseSelectionChoiceID=:courseSelectionChoiceID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function updateUnselectedChoicesBySchoolYearAndPerson($gibbonSchoolYearID, $gibbonPersonIDStudent, $courseIDList)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);

        if (!empty($courseIDList)) {
            $sql = "UPDATE courseSelectionChoice SET status='Removed'
                    WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonPersonIDStudent=:gibbonPersonIDStudent
                    AND gibbonCourseID NOT IN ({$courseIDList})
                    AND (status='Requested' OR status='Selected' OR status='Approved' OR status='')";
        } else {
            $sql = "UPDATE courseSelectionChoice SET status='Removed'
                    WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonPersonIDStudent=:gibbonPersonIDStudent
                    AND (status='Requested' OR status='Selected' OR status='Approved' OR status='')";
        }

        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    // APPROVAL

    public function insertApproval(array $data)
    {
        $sql = "INSERT INTO courseSelectionApproval SET courseSelectionChoiceID=:courseSelectionChoiceID, gibbonPersonIDApproved=:gibbonPersonIDApproved, timestampApproved=:timestampApproved ON DUPLICATE KEY UPDATE gibbonPersonIDApproved=:gibbonPersonIDApproved, timestampApproved=:timestampApproved";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function deleteApproval($courseSelectionChoiceID)
    {
        $data = array('courseSelectionChoiceID' => $courseSelectionChoiceID);
        $sql = "DELETE FROM courseSelectionApproval WHERE courseSelectionChoiceID=:courseSelectionChoiceID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    // LOG

    public function selectAllLogsBySchoolYear($gibbonSchoolYearID, $page = 1, $limit = 50)
    {
        $offset = ($page > 1)? ( ($page-1) * $limit) : 0;

        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT courseSelectionLog.*, gibbonSchoolYear.name as schoolYearName, courseSelectionOffering.name as offeringName, gibbonPersonStudent.surname AS studentSurname, gibbonPersonStudent.preferredName AS studentPreferredName, gibbonPersonChanged.surname AS changedSurname, gibbonPersonChanged.preferredName AS changedPreferredName
                FROM courseSelectionLog
                JOIN courseSelectionOffering ON (courseSelectionOffering.courseSelectionOfferingID=courseSelectionLog.courseSelectionOfferingID)
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=courseSelectionLog.gibbonSchoolYearID)
                JOIN gibbonPerson AS gibbonPersonStudent ON (gibbonPersonStudent.gibbonPersonID=courseSelectionLog.gibbonPersonIDStudent)
                JOIN gibbonPerson AS gibbonPersonChanged ON (gibbonPersonChanged.gibbonPersonID=courseSelectionLog.gibbonPersonIDChanged)
                WHERE courseSelectionLog.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY courseSelectionLog.courseSelectionLogID
                ORDER BY courseSelectionLog.timestampChanged DESC
                LIMIT {$limit} OFFSET {$offset}";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function insertLog(array $data)
    {
        $sql = "INSERT INTO courseSelectionLog SET gibbonSchoolYearID=:gibbonSchoolYearID, courseSelectionOfferingID=:courseSelectionOfferingID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonPersonIDChanged=:gibbonPersonIDChanged, timestampChanged=:timestampChanged, action=:action";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    // OFFERINGS

    public function selectChoiceOffering($gibbonSchoolYearID, $gibbonPersonIDStudent)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT courseSelectionOfferingID, gibbonSchoolYearID, gibbonPersonIDStudent FROM courseSelectionChoiceOffering WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonIDStudent";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function insertChoiceOffering(array $data)
    {
        $sql = "INSERT INTO courseSelectionChoiceOffering SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonIDStudent=:gibbonPersonIDStudent, courseSelectionOfferingID=:courseSelectionOfferingID ON DUPLICATE KEY UPDATE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function deleteChoiceOffering($gibbonSchoolYearID, $gibbonPersonIDStudent)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "DELETE FROM courseSelectionChoiceOffering WHERE gibbonSchoolYearID=:gibbonSchoolYearID AND gibbonPersonIDStudent=:gibbonPersonIDStudent";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    // MISC

    public function selectStudentsWithIncompleteSelections($gibbonSchoolYearID, $orderBy = 'surname')
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, courseSelectionOffering.courseSelectionOfferingID, gibbonRollGroup.nameShort as rollGroupName, COUNT(DISTINCT courseSelectionChoice.gibbonCourseID) as choiceCount, selectedOffering.minSelect, selectedOffering.maxSelect, selectedOffering.courseSelectionOfferingID as selectedOfferingID, selectedOffering.name as selectedOfferingName
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
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
                WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                AND (gibbonPerson.status = 'Full' OR gibbonPerson.status = 'Expected')
                GROUP BY gibbonPerson.gibbonPersonID

        ";

        if ($orderBy == 'rollGroup') {
            $sql .= " ORDER BY LENGTH(gibbonRollGroup.nameShort), gibbonRollGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else {
            $sql .= " ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
        }

        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectStudentsWithChoicesNotApproved($gibbonSchoolYearID, $orderBy = 'surname')
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, courseSelectionOffering.courseSelectionOfferingID, gibbonRollGroup.nameShort as rollGroupName, COUNT(DISTINCT courseSelectionChoice.gibbonCourseID) as choiceCount, COUNT(DISTINCT courseSelectionApproval.courseSelectionChoiceID) as approvalCount
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
                JOIN courseSelectionOfferingRestriction ON (
                    courseSelectionOfferingRestriction.gibbonSchoolYearID=gibbonStudentEnrolment.gibbonSchoolYearID
                    AND courseSelectionOfferingRestriction.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                JOIN courseSelectionOffering ON (courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID)
                LEFT JOIN courseSelectionChoice ON (courseSelectionChoice.gibbonPersonIDStudent=gibbonPerson.gibbonPersonID
                    AND courseSelectionChoice.gibbonSchoolYearID=courseSelectionOffering.gibbonSchoolYearID
                    AND courseSelectionChoice.status <> 'Removed' AND courseSelectionChoice.status <> 'Recommended')
                LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                AND (gibbonPerson.status = 'Full' OR gibbonPerson.status = 'Expected')
                GROUP BY gibbonPerson.gibbonPersonID

        ";

        if ($orderBy == 'rollGroup') {
            $sql .= " ORDER BY LENGTH(gibbonRollGroup.nameShort), gibbonRollGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else {
            $sql .= " ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
        }

        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectStudentsByOffering($courseSelectionOfferingID, $orderBy = 'surname')
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonPerson.image_240, courseSelectionOffering.courseSelectionOfferingID, gibbonRollGroup.nameShort as rollGroupName, courseSelectionChoiceOffering.courseSelectionOfferingID as selectedOfferingID
                FROM gibbonPerson
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonRollGroup ON (gibbonStudentEnrolment.gibbonRollGroupID=gibbonRollGroup.gibbonRollGroupID)
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

        if ($orderBy == 'rollGroup') {
            $sql .= " ORDER BY LENGTH(gibbonRollGroup.nameShort), gibbonRollGroup.nameShort, gibbonPerson.surname, gibbonPerson.preferredName";
        } else {
            $sql .= " ORDER BY gibbonPerson.surname, gibbonPerson.preferredName";
        }

        $result = $this->pdo->executeQuery($data, $sql);

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
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }
}
