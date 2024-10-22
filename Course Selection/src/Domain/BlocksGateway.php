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
 * Course Selection: courseSelectionBlock Table Gateway
 *
 * @version v14
 * @since   13th April 2017
 * @author  Sandra Kuipers
 *
 * @uses  courseSelectionBlock
 * @uses  courseSelectionBlockCourse
 * @uses  gibbonCourse
 * @uses  gibbonSchoolYear
 * @uses  gibbonDepartment
 */
class BlocksGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'courseSelectionBlock';
    private static $primaryKey = 'courseSelectionBlockID';
    private static $searchableColumns = [];

    public function queryAllBySchoolYear($criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->cols(['courseSelectionBlock.*', 'gibbonSchoolYear.name as schoolYearName', "GROUP_CONCAT(DISTINCT gibbonDepartment.name ORDER BY gibbonDepartment.name SEPARATOR '<br/>') as departmentName",'COUNT(DISTINCT gibbonCourseID) as courseCount'])
            ->from($this->getTableName())
            ->innerJoin('gibbonSchoolYear', 'courseSelectionBlock.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->leftJoin('gibbonDepartment', '(FIND_IN_SET(gibbonDepartment.gibbonDepartmentID, courseSelectionBlock.gibbonDepartmentIDList))')
            ->leftJoin('courseSelectionBlockCourse', 'courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionBlock.courseSelectionBlockID')
            ->groupBy(['courseSelectionBlock.courseSelectionBlockID'])
            ->where('courseSelectionBlock.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectOne($courseSelectionBlockID)
    {
        $data = array('courseSelectionBlockID' => $courseSelectionBlockID);
        $sql = "SELECT courseSelectionBlock.*, gibbonSchoolYear.name as schoolYearName, GROUP_CONCAT(gibbonDepartment.name SEPARATOR '<br/>') as departmentName
                FROM courseSelectionBlock
                JOIN gibbonSchoolYear ON (courseSelectionBlock.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID)
                LEFT JOIN gibbonDepartment ON (FIND_IN_SET(gibbonDepartment.gibbonDepartmentID, courseSelectionBlock.gibbonDepartmentIDList))
                WHERE courseSelectionBlockID=:courseSelectionBlockID
                GROUP BY courseSelectionBlock.courseSelectionBlockID";

        return $this->db()->select($sql, $data);
    }

    public function update(array $data)
    {
        $sql = "UPDATE courseSelectionBlock SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonDepartmentIDList=:gibbonDepartmentIDList, name=:name, description=:description, countable=:countable WHERE courseSelectionBlockID=:courseSelectionBlockID";
        $result = $this->db()->update($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function copyAllBySchoolYear($gibbonSchoolYearID, $gibbonSchoolYearIDNext)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearIDNext' => $gibbonSchoolYearIDNext );
        $sql = "INSERT INTO courseSelectionBlock (gibbonSchoolYearID, gibbonDepartmentIDList, name, description, countable)
                SELECT :gibbonSchoolYearIDNext, gibbonDepartmentIDList, name, description, countable
                FROM courseSelectionBlock WHERE courseSelectionBlock.gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $this->db()->insert($sql, $data);

        $partialSuccess = $this->db()->getQuerySuccess();
        if ($partialSuccess) {
            $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearIDNext' => $gibbonSchoolYearIDNext );
            $sql = "INSERT INTO courseSelectionBlockCourse (courseSelectionBlockID, gibbonCourseID)
                    SELECT
                        (SELECT courseSelectionBlockID FROM courseSelectionBlock WHERE gibbonSchoolYearID=:gibbonSchoolYearIDNext AND gibbonDepartmentIDList=prevBlock.gibbonDepartmentIDList AND name=prevBlock.name AND description=prevBlock.description) as courseSelectionBlockID,
                        nextCourse.gibbonCourseID
                    FROM courseSelectionBlockCourse
                    JOIN courseSelectionBlock as prevBlock ON (prevBlock.courseSelectionBlockID=courseSelectionBlockCourse.courseSelectionBlockID)
                    JOIN gibbonCourse as prevCourse ON (prevCourse.gibbonCourseID=courseSelectionBlockCourse.gibbonCourseID)
                    JOIN gibbonCourse as nextCourse ON (nextCourse.nameShort=prevCourse.nameShort)
                    WHERE prevBlock.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND prevCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                    AND nextCourse.gibbonSchoolYearID=:gibbonSchoolYearIDNext";
            $result = $this->db()->insert($sql, $data);
        }

        return $partialSuccess;
    }

    // BLOCK COURSES
    public function getNextSequenceNumber($courseSelectionBlockID)
    {
        $data = ['courseSelectionBlockID' => $courseSelectionBlockID];
        $sql = "SELECT MAX(sequenceNumber) FROM courseSelectionBlockCourse WHERE courseSelectionBlockID=:courseSelectionBlockID";
        $result = $this->db()->select($sql, $data);

        return ($result && $result->rowCount() > 0)? $result->fetchColumn(0)+1 : 1;
    }

    public function selectAllCoursesByBlock($courseSelectionBlockID)
    {
        $data = array('courseSelectionBlockID' => $courseSelectionBlockID);
        $sql = "SELECT gibbonCourse.gibbonCourseID, courseSelectionBlockCourse.*, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort
                FROM courseSelectionBlockCourse
                JOIN gibbonCourse ON (courseSelectionBlockCourse.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE courseSelectionBlockID=:courseSelectionBlockID
                ORDER BY courseSelectionBlockCourse.sequenceNumber, gibbonCourse.name, gibbonCourse.nameShort";

        return $this->db()->select($sql, $data);
    }
    
    public function updateBlockOrder(array $data)
    {
        $sql = "UPDATE courseSelectionBlockCourse SET sequenceNumber=:sequenceNumber WHERE courseSelectionBlockID=:courseSelectionBlockID AND gibbonCourseID=:gibbonCourseID";
        $result = $this->db()->update($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function insertCourse(array $data)
    {
        $sql = "INSERT INTO courseSelectionBlockCourse SET courseSelectionBlockID=:courseSelectionBlockID, gibbonCourseID=:gibbonCourseID, sequenceNumber=:sequenceNumber";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function deleteCourse($courseSelectionBlockID, $gibbonCourseID)
    {
        $data = array('courseSelectionBlockID' => $courseSelectionBlockID, 'gibbonCourseID' => $gibbonCourseID);
        $sql = "DELETE FROM courseSelectionBlockCourse WHERE courseSelectionBlockID=:courseSelectionBlockID AND gibbonCourseID=:gibbonCourseID";
        $result =$this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function deleteAllCoursesByBlock($courseSelectionBlockID)
    {
        $data = array('courseSelectionBlockID' => $courseSelectionBlockID);
        $sql = "DELETE FROM courseSelectionBlockCourse WHERE courseSelectionBlockID=:courseSelectionBlockID";
        $result =$this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    // FORM QUERIES

    public function selectAvailableCourses($courseSelectionBlockID)
    {
        $data = array('courseSelectionBlockID' => $courseSelectionBlockID);
        $sql = "SELECT gibbonCourse.gibbonCourseID AS value, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) as name
                FROM gibbonCourse
                JOIN courseSelectionBlock ON (gibbonCourse.gibbonSchoolYearID=courseSelectionBlock.gibbonSchoolYearID)
                LEFT JOIN courseSelectionBlockCourse ON (
                    courseSelectionBlockCourse.gibbonCourseID=gibbonCourse.gibbonCourseID
                    AND courseSelectionBlockCourse.courseSelectionBlockID=:courseSelectionBlockID)
                WHERE courseSelectionBlock.courseSelectionBlockID=:courseSelectionBlockID
                AND courseSelectionBlockCourse.gibbonCourseID IS NULL
                ORDER BY nameShort, name";

        return $this->db()->select($sql, $data);
    }

    public function selectAvailableCoursesByDepartments($courseSelectionBlockID, $gibbonDepartmentIDList)
    {
        $data = array('courseSelectionBlockID' => $courseSelectionBlockID, 'gibbonDepartmentIDList' => $gibbonDepartmentIDList);
        $sql = "SELECT gibbonCourse.gibbonCourseID AS value, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) as name
                FROM gibbonCourse
                JOIN courseSelectionBlock ON (gibbonCourse.gibbonSchoolYearID=courseSelectionBlock.gibbonSchoolYearID)
                LEFT JOIN courseSelectionBlockCourse ON (
                    courseSelectionBlockCourse.gibbonCourseID=gibbonCourse.gibbonCourseID
                    AND courseSelectionBlockCourse.courseSelectionBlockID=:courseSelectionBlockID)
                WHERE FIND_IN_SET(gibbonCourse.gibbonDepartmentID, :gibbonDepartmentIDList)
                AND courseSelectionBlock.courseSelectionBlockID=:courseSelectionBlockID
                AND courseSelectionBlockCourse.gibbonCourseID IS NULL
                ORDER BY nameShort, name";

        return $this->db()->select($sql, $data);
    }
}
