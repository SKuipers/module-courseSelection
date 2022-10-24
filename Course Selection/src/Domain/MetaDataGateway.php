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
 * Course Selection: Meta Data Gateway
 *
 * @version v14
 * @since   10th May 2017
 * @author  Sandra Kuipers
 *
 * @uses  courseSelectionMetaData
 */
class MetaDataGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'courseSelectionMetaData';
    private static $primaryKey = 'courseSelectionMetaDataID';
    private static $searchableColumns = [];
    protected $pdo;

    public function queryAllBySchoolYear($criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->cols(['*'])
            ->from($this->getTableName())
            ->innerJoin('gibbonCourse', 'gibbonCourse.gibbonCourseID=courseSelectionMetaData.gibbonCourseID')
            ->where('gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function insert(array $data)
    {
        $sql = "INSERT INTO courseSelectionMetaData SET gibbonCourseID=:gibbonCourseID, enrolmentGroup=:enrolmentGroup, timetablePriority=:timetablePriority, tags=:tags ON DUPLICATE KEY UPDATE enrolmentGroup=:enrolmentGroup, timetablePriority=:timetablePriority, tags=:tags, excludeClasses=:excludeClasses";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function update(array $data)
    {
        $sql = "UPDATE courseSelectionMetaData SET gibbonCourseID=:gibbonCourseID, enrolmentGroup=:enrolmentGroup, timetablePriority=:timetablePriority, tags=:tags, excludeClasses=:excludeClasses WHERE courseSelectionMetaDataID=:courseSelectionMetaDataID";
        $result = $this->db()->update($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function delete($courseSelectionMetaDataID)
    {
        $data = array('courseSelectionMetaDataID' => $courseSelectionMetaDataID);
        $sql = "DELETE FROM courseSelectionMetaData WHERE courseSelectionMetaDataID=:courseSelectionMetaDataID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function copyAllBySchoolYear($gibbonSchoolYearID, $gibbonSchoolYearIDNext)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonSchoolYearIDNext' => $gibbonSchoolYearIDNext );
        $sql = "INSERT INTO courseSelectionMetaData (gibbonCourseID, enrolmentGroup, timetablePriority, tags, excludeClasses)
                SELECT nextCourse.gibbonCourseID, courseSelectionMetaData.enrolmentGroup, courseSelectionMetaData.timetablePriority, courseSelectionMetaData.tags, courseSelectionMetaData.excludeClasses
                FROM courseSelectionMetaData
                JOIN gibbonCourse as prevCourse ON (prevCourse.gibbonCourseID=courseSelectionMetaData.gibbonCourseID)
                JOIN gibbonCourse as nextCourse ON (nextCourse.nameShort=prevCourse.nameShort)
                WHERE prevCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                AND nextCourse.gibbonSchoolYearID=:gibbonSchoolYearIDNext";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function selectOne($courseSelectionMetaDataID)
    {
        $data = array('courseSelectionMetaDataID' => $courseSelectionMetaDataID);
        $sql = "SELECT * FROM courseSelectionMetaData WHERE courseSelectionMetaDataID=:courseSelectionMetaDataID ";
        $result = $this->db()->select($sql, $data);

        return $result;
    }
}
