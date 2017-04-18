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
 */
class SelectionsGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    // CHOICES

    public function selectChoicesByOfferingBlockAndPerson($courseSelectionOfferingID, $gibbonPersonIDStudent)
    {
        $data = array('courseSelectionOfferingID' => $courseSelectionOfferingID, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "SELECT courseSelectionOfferingBlock.courseSelectionBlockID, courseSelectionChoice.*
                FROM courseSelectionOfferingBlock
                JOIN courseSelectionBlockCourse ON (courseSelectionBlockCourse.courseSelectionBlockID=courseSelectionOfferingBlock.courseSelectionBlockID)
                JOIN courseSelectionChoice ON (courseSelectionBlockCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                WHERE courseSelectionOfferingBlock.courseSelectionOfferingID=:courseSelectionOfferingID
                AND courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
                AND courseSelectionChoice.status <> 'Removed'";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }

    public function insertChoice(array $data)
    {
        $sql = "INSERT INTO courseSelectionChoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseID=:gibbonCourseID, status=:status, gibbonPersonIDSelected=:gibbonPersonIDSelected, timestampSelected=:timestampSelected, gibbonPersonIDStatusChange=:gibbonPersonIDStatusChange, timestampStatusChange=:timestampStatusChange, notes=:notes ON DUPLICATE KEY UPDATE status=:status, gibbonPersonIDSelected=:gibbonPersonIDSelected, timestampSelected=:timestampSelected, gibbonPersonIDStatusChange=:gibbonPersonIDStatusChange, timestampStatusChange=:timestampStatusChange, notes=:notes";
        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getConnection()->lastInsertID();
    }

    public function updateChoice(array $data)
    {
        $sql = "UPDATE courseSelectionChoice SET gibbonSchoolYearID=:gibbonSchoolYearID, gibbonPersonIDStudent=:gibbonPersonIDStudent, gibbonCourseID=:gibbonCourseID, status=:status, gibbonPersonIDSelected=:gibbonPersonIDSelected, timestampSelected=:timestampSelected, gibbonPersonIDStatusChange=:gibbonPersonIDStatusChange, timestampStatusChange=:timestampStatusChange, notes=:notes";
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
                    AND gibbonCourseID NOT IN ({$courseIDList})";
        } else {
            $sql = "UPDATE courseSelectionChoice SET status='Removed'
                    WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                    AND gibbonPersonIDStudent=:gibbonPersonIDStudent";
        }

        $result = $this->pdo->executeQuery($data, $sql);

        return $this->pdo->getQuerySuccess();
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
}
