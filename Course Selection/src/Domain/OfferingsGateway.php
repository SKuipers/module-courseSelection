<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace Gibbon\Module\CourseSelection\Domain;

use Gibbon\Domain\Traits\TableAware;
use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Contracts\Database\Connection;

/**
 * Course Selection: courseSelectionOffering Table Gateway
 *
 * @version v14
 * @since   13th April 2017
 * @author  Sandra Kuipers
 *
 * @uses  courseSelectionOffering
 * @uses  courseSelectionOfferingRestriction
 * @uses  courseSelectionOfferingBlock
 * @uses  courseSelectionBlock
 * @uses  courseSelectionBlockCourse
 * @uses  gibbonStudentEnrolment
 * @uses  gibbonSchoolYear
 * @uses  gibbonYearGroup
 * @uses  gibbonDepartment
 */
class OfferingsGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'courseSelectionOffering';
    private static $primaryKey = 'courseSelectionOfferingID';
    private static $searchableColumns = [];

    public function queryAllBySchoolYear($criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->cols(['courseSelectionOffering.*', 'gibbonSchoolYear.name as schoolYearName', "GROUP_CONCAT(CONCAT(restrictYear.name, ' - ', gibbonYearGroup.nameShort) ORDER BY restrictYear.sequenceNumber, gibbonYearGroup.sequenceNumber SEPARATOR '<br/>') as yearGroupNames"])
            ->from($this->getTableName())
            ->innerJoin('gibbonSchoolYear', 'courseSelectionOffering.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->leftJoin('courseSelectionOfferingRestriction', 'courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID')
            ->leftJoin('gibbonSchoolYear AS restrictYear', 'restrictYear.gibbonSchoolYearID=courseSelectionOfferingRestriction.gibbonSchoolYearID')
            ->leftJoin('gibbonYearGroup', 'gibbonYearGroup.gibbonYearGroupID=courseSelectionOfferingRestriction.gibbonYearGroupID')
            ->groupBy(['courseSelectionOffering.courseSelectionOfferingID'])
            ->where('courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectAllBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT courseSelectionOffering.*, gibbonSchoolYear.name as schoolYearName, GROUP_CONCAT(CONCAT(restrictYear.name, ' - ', gibbonYearGroup.nameShort) ORDER BY restrictYear.sequenceNumber, gibbonYearGroup.sequenceNumber SEPARATOR '<br/>') as yearGroupNames
                FROM courseSelectionOffering
                JOIN gibbonSchoolYear ON (courseSelectionOffering.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                LEFT JOIN courseSelectionOfferingRestriction ON (courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID)
                LEFT JOIN gibbonSchoolYear AS restrictYear ON (restrictYear.gibbonSchoolYearID=courseSelectionOfferingRestriction.gibbonSchoolYearID)
                LEFT JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=courseSelectionOfferingRestriction.gibbonYearGroupID)
                WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY courseSelectionOfferingID
                ORDER BY sequenceNumber";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function selectOne($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT courseSelectionOffering.*, gibbonSchoolYear.name as schoolYearName FROM courseSelectionOffering JOIN gibbonSchoolYear ON (courseSelectionOffering.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE courseSelectionOfferingID=:courseSelectionOfferingID ";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function selectOfferingsByStudentEnrolment($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID);
        $sql = "SELECT *
                FROM courseSelectionOffering
                LEFT JOIN courseSelectionOfferingRestriction ON (courseSelectionOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID)
                LEFT JOIN gibbonStudentEnrolment ON (
                    gibbonStudentEnrolment.gibbonPersonID=:gibbonPersonID
                    AND gibbonStudentEnrolment.gibbonSchoolYearID=courseSelectionOfferingRestriction.gibbonSchoolYearID
                    AND gibbonStudentEnrolment.gibbonYearGroupID=courseSelectionOfferingRestriction.gibbonYearGroupID)
                WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY courseSelectionOffering.courseSelectionOfferingID
                HAVING (COUNT(courseSelectionOfferingRestrictionID) = 0 OR COUNT(gibbonStudentEnrolmentID) > 0)
                ORDER BY courseSelectionOffering.sequenceNumber";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function insert(array $data)
    {
        $sql = "INSERT INTO courseSelectionOffering SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupIDList=:gibbonYearGroupIDList, name=:name, description=:description, minSelect=:minSelect, maxSelect=:maxSelect, sequenceNumber=:sequenceNumber";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function update(array $data)
    {
        $sql = "UPDATE courseSelectionOffering SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupIDList=:gibbonYearGroupIDList, name=:name, description=:description, minSelect=:minSelect, maxSelect=:maxSelect, sequenceNumber=:sequenceNumber WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->db()->update($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function delete($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "DELETE FROM courseSelectionOffering WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function copyAllBySchoolYear($gibbonSchoolYearID, $gibbonSchoolYearIDNext)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearIDNext' => $gibbonSchoolYearIDNext );
        $sql = "INSERT INTO courseSelectionOffering (gibbonSchoolYearID, gibbonYearGroupIDList, name, description, minSelect, maxSelect, sequenceNumber)
                SELECT :gibbonSchoolYearIDNext, gibbonYearGroupIDList, name, description, minSelect, maxSelect, sequenceNumber
                FROM courseSelectionOffering WHERE courseSelectionOffering.gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $this->db()->insert($sql, $data);

        $partialSuccess = $this->db()->getQuerySuccess();
        if ($partialSuccess) {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearIDNext' => $gibbonSchoolYearIDNext );
            $sql = "INSERT INTO courseSelectionOfferingRestriction (courseSelectionOfferingID, gibbonSchoolYearID, gibbonYearGroupID)
                    SELECT
                        (SELECT courseSelectionOfferingID FROM courseSelectionOffering WHERE gibbonSchoolYearID=:gibbonSchoolYearIDNext AND gibbonYearGroupIDList=prevOffering.gibbonYearGroupIDList AND name=prevOffering.name) as courseSelectionOfferingID,
                        (SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE sequenceNumber > offeringYear.sequenceNumber ORDER BY sequenceNumber LIMIT 1) as gibbonSchoolYearID,
                        courseSelectionOfferingRestriction.gibbonYearGroupID
                    FROM courseSelectionOfferingRestriction
                    JOIN gibbonSchoolYear as offeringYear ON (offeringYear.gibbonSchoolYearID=courseSelectionOfferingRestriction.gibbonSchoolYearID)
                    JOIN courseSelectionOffering as prevOffering ON (prevOffering.courseSelectionOfferingID=courseSelectionOfferingRestriction.courseSelectionOfferingID)
                    WHERE prevOffering.gibbonSchoolYearID=:gibbonSchoolYearID";
            $result = $this->db()->insert($sql, $data);

            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearIDNext' => $gibbonSchoolYearIDNext );
            $sql = "INSERT INTO courseSelectionOfferingBlock (courseSelectionOfferingID, courseSelectionBlockID, minSelect, maxSelect, sequenceNumber)
                SELECT
                    (SELECT courseSelectionOfferingID FROM courseSelectionOffering WHERE gibbonSchoolYearID=:gibbonSchoolYearIDNext AND gibbonYearGroupIDList=prevOffering.gibbonYearGroupIDList AND name=prevOffering.name) as courseSelectionOfferingID,
                    (SELECT courseSelectionBlockID FROM courseSelectionBlock WHERE gibbonSchoolYearID=:gibbonSchoolYearIDNext AND gibbonDepartmentIDList=prevBlock.gibbonDepartmentIDList AND name=prevBlock.name AND description=prevBlock.description) as courseSelectionBlockID,
                    courseSelectionOfferingBlock.minSelect, courseSelectionOfferingBlock.maxSelect, courseSelectionOfferingBlock.sequenceNumber
                FROM courseSelectionOfferingBlock
                JOIN courseSelectionOffering as prevOffering ON (prevOffering.courseSelectionOfferingID=courseSelectionOfferingBlock.courseSelectionOfferingID)
                JOIN courseSelectionBlock as prevBlock ON (prevBlock.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID)
                WHERE prevOffering.gibbonSchoolYearID=:gibbonSchoolYearID
                AND prevBlock.gibbonSchoolYearID=:gibbonSchoolYearID";

            $result = $this->db()->insert($sql, $data);
        }

        return $partialSuccess;
    }

    public function getNextSequenceNumber()
    {
        $sql = "SELECT MAX(sequenceNumber) FROM courseSelectionOffering";
        $result = $this->db()->select($sql, array());

        return ($result && $result->rowCount() > 0)? $result->fetchColumn(0)+1 : 1;
    }

    // OFFERING RESTRICTIONS

    public function selectAllRestrictionsByOffering($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT courseSelectionOfferingRestriction.*, gibbonSchoolYear.name as schoolYearName, gibbonYearGroup.name as yearGroupName
                FROM courseSelectionOfferingRestriction
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=courseSelectionOfferingRestriction.gibbonSchoolYearID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=courseSelectionOfferingRestriction.gibbonYearGroupID)
                WHERE courseSelectionOfferingID=:courseSelectionOfferingID ";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function insertRestriction(array $data)
    {
        $sql = "INSERT INTO courseSelectionOfferingRestriction SET courseSelectionOfferingID=:courseSelectionOfferingID, gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupID=:gibbonYearGroupID";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function deleteRestriction($courseSelectionOfferingRestrictionID)
    {
        $data = array('courseSelectionOfferingRestrictionID' => $courseSelectionOfferingRestrictionID);
        $sql = "DELETE FROM courseSelectionOfferingRestriction WHERE courseSelectionOfferingRestrictionID=:courseSelectionOfferingRestrictionID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function deleteAllRestrictionsByOffering($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "DELETE FROM courseSelectionOfferingRestriction WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    // OFFERING BLOCKS

    public function selectAllBlocksByOffering($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT courseSelectionBlock.gibbonDepartmentIDList, courseSelectionOfferingBlock.*, courseSelectionBlock.name as blockName, courseSelectionBlock.description as blockDescription, courseSelectionOfferingBlock.minSelect, courseSelectionOfferingBlock.maxSelect, COUNT(gibbonCourseID) as courseCount
                FROM courseSelectionOfferingBlock
                JOIN courseSelectionBlock ON (courseSelectionBlock.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID)
                LEFT JOIN courseSelectionBlockCourse ON (courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID)
                WHERE courseSelectionOfferingID=:courseSelectionOfferingID
                GROUP BY courseSelectionBlock.courseSelectionBlockID
                ORDER BY courseSelectionOfferingBlock.sequenceNumber";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function insertBlock(array $data)
    {
        $sql = "INSERT INTO courseSelectionOfferingBlock SET courseSelectionOfferingID=:courseSelectionOfferingID, courseSelectionBlockID=:courseSelectionBlockID, minSelect=:minSelect, maxSelect=:maxSelect";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function updateBlockOrder($courseSelectionOfferingID, $courseSelectionBlockID, $sequenceNumber)
    {
        $data = ['courseSelectionOfferingID' => $courseSelectionOfferingID, 'courseSelectionBlockID' => $courseSelectionBlockID, 'sequenceNumber' => $sequenceNumber];
        $sql = "UPDATE courseSelectionOfferingBlock SET sequenceNumber=:sequenceNumber WHERE courseSelectionOfferingID=:courseSelectionOfferingID AND courseSelectionBlockID=:courseSelectionBlockID";

        return $this->db()->select($sql, $data);
    }

    public function deleteBlock($courseSelectionOfferingID, $courseSelectionBlockID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'courseSelectionBlockID' => $courseSelectionBlockID);
        $sql = "DELETE FROM courseSelectionOfferingBlock WHERE courseSelectionOfferingID=:courseSelectionOfferingID AND courseSelectionBlockID=:courseSelectionBlockID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function deleteAllBlocksByOffering($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "DELETE FROM courseSelectionOfferingBlock WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    // FORM QUERIES

    public function selectAvailableBlocksBySchoolYear($courseSelectionOfferingID, $gibbonSchoolYearID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT courseSelectionBlock.courseSelectionBlockID as value, CONCAT(courseSelectionBlock.name, ' (', COUNT(courseSelectionBlockCourse.gibbonCourseID), ' courses)') as name
                FROM courseSelectionBlock
                JOIN gibbonSchoolYear ON (courseSelectionBlock.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                LEFT JOIN courseSelectionOfferingBlock ON (courseSelectionOfferingBlock.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID AND courseSelectionOfferingID=:courseSelectionOfferingID)
                LEFT JOIN courseSelectionBlockCourse ON (courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID)
                WHERE gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID
                AND courseSelectionOfferingBlock.courseSelectionBlockID IS NULL
                GROUP BY courseSelectionBlock.courseSelectionBlockID
                ORDER BY name";

        return $this->db()->select($sql, $data);
    }

    // MISC

    public function selectDepartmentByID($gibbonDepartmentID)
    {
        $data = array('gibbonDepartmentID' => $gibbonDepartmentID);
        $sql = "SELECT* FROM gibbonDepartment WHERE gibbonDepartmentID=:gibbonDepartmentID";
        $result = $this->db()->select($sql, $data);

        return $result;
    }
}
