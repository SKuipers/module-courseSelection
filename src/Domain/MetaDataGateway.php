<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Domain;

/**
 * Course Selection: Meta Data Gateway
 *
 * @version v14
 * @since   10th May 2017
 * @author  Sandra Kuipers
 *
 * @uses  courseSelectionMetaData
 */
class MetaDataGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function insert(array $data)
    {
        $sql = "INSERT INTO courseSelectionMetaData SET gibbonCourseID=:gibbonCourseID, enrolmentGroup=:enrolmentGroup, timetablePriority=:timetablePriority, tags=:tags ON DUPLICATE KEY UPDATE enrolmentGroup=:enrolmentGroup, timetablePriority=:timetablePriority, tags=:tags";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function update(array $data)
    {
        $sql = "UPDATE courseSelectionMetaData SET gibbonCourseID=:gibbonCourseID, enrolmentGroup=:enrolmentGroup, timetablePriority=:timetablePriority, tags=:tags WHERE courseSelectionMetaDataID=:courseSelectionMetaDataID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }

    public function delete($courseSelectionAccessID)
    {
        $data = array('courseSelectionMetaDataID' => $courseSelectionMetaDataID);
        $sql = "DELETE FROM courseSelectionMetaData WHERE courseSelectionMetaDataID=:courseSelectionMetaDataID";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
    }
}
