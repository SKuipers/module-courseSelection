<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Domain;

use Gibbon\Domain\QueryCriteria;
use Gibbon\Domain\QueryableGateway;
use Gibbon\Domain\Traits\TableAware;
use Gibbon\Contracts\Database\Connection;

/**
 * Course Selection: courseSelectionAccess Table Gateway
 *
 * @version v14
 * @since   13th April 2017
 * @author  Sandra Kuipers
 *
 * @uses  courseSelectionAccess
 * @uses  courseSelectionOffering
 * @uses  gibbonSchoolYear
 * @uses  gibbonRole
 * @uses  gibbonPerson
 * @uses  gibbonAction
 * @uses  gibbonModule
 * @uses  gibbonPermission
 */
class AccessGateway extends QueryableGateway
{
    use TableAware;

    private static $tableName = 'courseSelectionAccess';
    private static $primaryKey = 'courseSelectionAccessID';
    private static $searchableColumns = [];

    public function queryAllBySchoolYear($criteria, $gibbonSchoolYearID)
    {
        $query = $this
            ->newQuery()
            ->cols(['courseSelectionAccess.*', 'gibbonSchoolYear.name as schoolYearName', "GROUP_CONCAT(DISTINCT gibbonRole.name SEPARATOR ', ') as roleGroupNames"])
            ->from($this->getTableName())
            ->innerJoin('gibbonSchoolYear', 'courseSelectionAccess.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID')
            ->leftJoin('gibbonRole', 'FIND_IN_SET(gibbonRole.gibbonRoleID, courseSelectionAccess.gibbonRoleIDList)')
            ->groupBy(['courseSelectionAccess.courseSelectionAccessID'])
            ->where('courseSelectionAccess.gibbonSchoolYearID=:gibbonSchoolYearID')
            ->bindValue('gibbonSchoolYearID', $gibbonSchoolYearID);

        return $this->runQuery($query, $criteria);
    }

    public function selectOne($courseSelectionAccessID)
    {
        $data = array('courseSelectionAccessID' => $courseSelectionAccessID);
        $sql = "SELECT courseSelectionAccess.*, gibbonSchoolYear.name as gibbonSchoolYearName FROM courseSelectionAccess JOIN gibbonSchoolYear ON (courseSelectionAccess.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE courseSelectionAccessID=:courseSelectionAccessID";
        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function insert(array $data)
    {
        $sql = "INSERT INTO courseSelectionAccess SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonRoleIDList=:gibbonRoleIDList, dateStart=:dateStart, dateEnd=:dateEnd, accessType=:accessType";
        $result = $this->db()->insert($sql, $data);

        return $this->db()->getConnection()->lastInsertID();
    }

    public function update(array $data)
    {
        $sql = "UPDATE courseSelectionAccess SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonRoleIDList=:gibbonRoleIDList, dateStart=:dateStart, dateEnd=:dateEnd, accessType=:accessType WHERE courseSelectionAccessID=:courseSelectionAccessID";
        $result = $this->db()->update($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function delete($courseSelectionAccessID)
    {
        $data = array('courseSelectionAccessID' => $courseSelectionAccessID);
        $sql = "DELETE FROM courseSelectionAccess WHERE courseSelectionAccessID=:courseSelectionAccessID";
        $result = $this->db()->delete($sql, $data);

        return $this->db()->getQuerySuccess();
    }

    public function getAccessRolesWithSelectionPermission()
    {
        $sql = "SELECT gibbonRole.gibbonRoleID as value, gibbonRole.name as name
                FROM gibbonRole
                JOIN (
                    SELECT gibbonAction.gibbonActionID
                    FROM gibbonAction
                    JOIN gibbonModule ON (gibbonModule.gibbonModuleID=gibbonAction.gibbonModuleID)
                    WHERE LEFT(gibbonAction.name, 17)='Course Selection_'
                    AND gibbonModule.name='Course Selection') AS actions
                LEFT JOIN gibbonPermission ON (gibbonPermission.gibbonRoleID=gibbonRole.gibbonRoleID AND gibbonPermission.gibbonActionID=actions.gibbonActionID)
                GROUP BY gibbonRole.gibbonRoleID
                HAVING COUNT(DISTINCT gibbonPermission.permissionID) > 0";
        $result = $this->db()->select($sql, []);

        return $result;
    }

    public function getAccessByPerson($gibbonSchoolYearID, $gibbonPersonID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID, 'gibbonPersonID' => $gibbonPersonID, 'date' => date('Y-m-d'));
        $sql = "SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name as schoolYearName, courseSelectionAccess.*
                FROM courseSelectionAccess
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=courseSelectionAccess.gibbonSchoolYearID)
                JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, courseSelectionAccess.gibbonRoleIDList))
                JOIN gibbonPerson ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary OR FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonRoleIDAll))
                WHERE gibbonPerson.gibbonPersonID=:gibbonPersonID
                AND gibbonSchoolYear.gibbonSchoolYearID=:gibbonSchoolYearID
                AND courseSelectionAccess.dateEnd >= :date
                ORDER BY courseSelectionAccess.dateEnd, (CASE WHEN accessType='Select' THEN 2 WHEN accessType='Request' THEN 1 ELSE 0 END) DESC";

        $result = $this->db()->select($sql, $data);

        return $result;
    }

    public function getAccessByOfferingAndPerson($courseSelectionOfferingID, $gibbonPersonID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'gibbonPersonID' => $gibbonPersonID, 'date' => date('Y-m-d'));
        $sql = "SELECT gibbonSchoolYear.gibbonSchoolYearID, gibbonSchoolYear.name as schoolYearName, courseSelectionAccess.*
                FROM courseSelectionOffering
                JOIN courseSelectionAccess  ON (courseSelectionAccess.gibbonSchoolYearID=courseSelectionOffering.gibbonSchoolYearID)
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=courseSelectionAccess.gibbonSchoolYearID)
                JOIN gibbonRole ON (FIND_IN_SET(gibbonRole.gibbonRoleID, courseSelectionAccess.gibbonRoleIDList))
                JOIN gibbonPerson ON (gibbonRole.gibbonRoleID=gibbonPerson.gibbonRoleIDPrimary OR FIND_IN_SET(gibbonRole.gibbonRoleID, gibbonPerson.gibbonRoleIDAll))
                WHERE courseSelectionOffering.courseSelectionOfferingID=:courseSelectionOfferingID
                AND gibbonPerson.gibbonPersonID=:gibbonPersonID
                AND courseSelectionAccess.dateEnd >= :date
                ORDER BY courseSelectionAccess.dateEnd, (CASE WHEN accessType='Select' THEN 2 WHEN accessType='Request' THEN 1 ELSE 0 END) DESC";
        $result = $this->db()->select($sql, $data);

        return $result;
    }
}
