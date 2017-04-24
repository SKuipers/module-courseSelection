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
 * Course Selection: Tools Gateway
 *
 * @version v14
 * @since   17th April 2017
 * @author  Sandra Kuipers
 */
class ToolsGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function selectSchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectCoursesBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonYearGroup.name as grouping, gibbonCourse.gibbonCourseID as value, CONCAT(gibbonCourse.nameShort, ' - ', gibbonCourse.name) as name
                FROM gibbonCourse
                JOIN gibbonYearGroup ON (FIND_IN_SET(gibbonYearGroup.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList))
                WHERE gibbonSchoolYearID=:gibbonSchoolYearID
                ORDER BY gibbonYearGroup.sequenceNumber DESC, gibbonCourse.nameShort, gibbonCourse.name";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectStudentsByCourse($gibbonCourseID)
    {
        $data = array('gibbonCourseID' => $gibbonCourseID);
        $sql = "SELECT gibbonPerson.gibbonPersonID, gibbonPerson.surname, gibbonPerson.preferredName, gibbonYearGroup.name as yearGroupName, CONCAT(gibbonCourse.nameShort, '.',gibbonCourseClass.nameShort) as courseClassName
                FROM gibbonPerson
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonPersonID=gibbonPerson.gibbonPersonID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseClassID=gibbonCourseClassPerson.gibbonCourseClassID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=gibbonCourseClass.gibbonCourseID)
                JOIN gibbonStudentEnrolment ON (gibbonStudentEnrolment.gibbonPersonID=gibbonPerson.gibbonPersonID AND gibbonStudentEnrolment.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                JOIN gibbonYearGroup ON (gibbonYearGroup.gibbonYearGroupID=gibbonStudentEnrolment.gibbonYearGroupID)
                WHERE (gibbonPerson.gibbonPersonID=gibbonCourseClassPerson.gibbonPersonID)
                AND gibbonCourse.gibbonCourseID=:gibbonCourseID
                AND (gibbonPerson.status='Full' OR gibbonPerson.status='Expected')
                AND gibbonCourseClassPerson.role='Student'
                GROUP BY gibbonPerson.gibbonPersonID
                ORDER BY gibbonCourseClassPerson.role DESC, gibbonPerson.surname, gibbonPerson.preferredName";

        return $this->pdo->executeQuery($data, $sql);
    }
}
