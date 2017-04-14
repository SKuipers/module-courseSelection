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

namespace Modules\CourseSelection\Domain;

/**
 * Course Selection: courseSelectionOffering Table Gateway
 *
 * @version v14
 * @since   13th April 2017
 * @author  Sandra Kuipers
 */
class OfferingsGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function selectAll()
    {
        $data = array();
        $sql = "SELECT courseSelectionOffering.*, gibbonSchoolYear.name as gibbonSchoolYearName FROM courseSelectionOffering JOIN gibbonSchoolYear ON (courseSelectionOffering.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) ORDER BY sequenceNumber";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function selectOne($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "SELECT courseSelectionOffering.*, gibbonSchoolYear.name as gibbonSchoolYearName FROM courseSelectionOffering JOIN gibbonSchoolYear ON (courseSelectionOffering.gibbonSchoolYearID=gibbonSchoolYear.gibbonSchoolYearID) WHERE courseSelectionOfferingID=:courseSelectionOfferingID ";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function insert(array $data)
    {
        $sql = "INSERT INTO courseSelectionOffering SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupIDList=:gibbonYearGroupIDList, name=:name, description=:description, minSelect=:minSelect, maxSelect=:maxSelect, sequenceNumber=:sequenceNumber";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function update(array $data)
    {
        $sql = "UPDATE courseSelectionOffering SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonYearGroupIDList=:gibbonYearGroupIDList, name=:name, description=:description, minSelect=:minSelect, maxSelect=:maxSelect, sequenceNumber=:sequenceNumber WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function delete($courseSelectionOfferingID)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID);
        $sql = "DELETE FROM courseSelectionOffering WHERE courseSelectionOfferingID=:courseSelectionOfferingID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function getNextSequenceNumber()
    {
        $sql = "SELECT MAX(sequenceNumber) FROM courseSelectionOffering";
        $result = $this->pdo->executeQuery(array(), $sql);

        return ($result && $result->rowCount() > 0)? $result->fetchColumn(0)+1 : 1;
    }
}
