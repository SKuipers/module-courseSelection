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
 * Course Selection: Grades Gateway
 *
 * @version v14
 * @since   26th April 2017
 * @author  Sandra Kuipers
 *
 * @uses  gibbonSchoolYear
 * @uses  gibbonCourse
 * @uses  gibbonCourseClass
 * @uses  gibbonCourseClassPerson
 * @uses  arrCriteria
 * @uses  arrReportGrade
 * @uses  arrReport
 * @uses  arrLegacyGrade
 */
class GradesGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    // GRADES

    public function selectStudentReportGradesByDepartments($gibbonDepartmentIDList, $gibbonPersonIDStudent)
    {
        $data = array('gibbonDepartmentIDList' => $gibbonDepartmentIDList, 'gibbonPersonIDStudent' => $gibbonPersonIDStudent);
        $sql = "(SELECT gradeID as grade, gibbonSchoolYear.name as schoolYearName, gibbonSchoolYear.status as schoolYearStatus, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, arrReport.reportName, (CASE WHEN gibbonCourse.orderBy > 0 THEN gibbonCourse.orderBy ELSE 80 end) as courseOrder
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.role='Student')
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                LEFT JOIN arrCriteria ON (gibbonCourse.gibbonCourseID=arrCriteria.subjectID  )
                LEFT JOIN arrReportGrade ON (arrReportGrade.criteriaID=arrCriteria.criteriaID AND arrReportGrade.studentID = gibbonCourseClassPerson.gibbonPersonID )
                LEFT JOIN arrReport ON (arrReport.reportID=arrReportGrade.reportID AND arrReport.schoolYearID=gibbonCourse.gibbonSchoolYearID )
                WHERE FIND_IN_SET(gibbonCourse.gibbonDepartmentID, :gibbonDepartmentIDList)
                AND gibbonCourseClassPerson.gibbonPersonID=:gibbonPersonIDStudent
                AND gibbonCourseClass.reportable='Y'
                AND gibbonCourse.nameShort NOT LIKE '%ECA%'
                AND gibbonCourse.nameShort NOT LIKE '%HOMEROOM%'
                AND gibbonCourse.nameShort NOT LIKE '%Advisor%'
                AND arrReportGrade.reportGradeID IS NOT NULL
                AND arrCriteria.criteriaID = (SELECT arrCriteria.criteriaID FROM arrCriteria JOIN arrReportGrade ON (arrReportGrade.criteriaID=arrCriteria.criteriaID) WHERE subjectID=gibbonCourse.gibbonCourseID AND schoolYearID=gibbonCourse.gibbonSchoolYearID AND (criteriaType=2 || criteriaType=4) AND arrReportGrade.studentID=gibbonCourseClassPerson.gibbonPersonID ORDER BY arrCriteria.criteriaType DESC, arrCriteria.reportID DESC LIMIT 1)
                GROUP BY gibbonCourse.gibbonCourseID
                ORDER BY arrReport.schoolYearID DESC, arrReport.reportNum DESC, arrCriteria.criteriaType DESC
            ) UNION ALL (
            SELECT DISTINCT grade, gibbonSchoolYear.name as schoolYearName, gibbonSchoolYear.status as schoolYearStatus, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, 'Final', (CASE WHEN gibbonCourse.orderBy > 0 THEN gibbonCourse.orderBy ELSE 80 end) as courseOrder
                FROM arrLegacyGrade
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=arrLegacyGrade.gibbonCourseID)
                JOIN gibbonSchoolYear ON (gibbonSchoolYear.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                WHERE arrLegacyGrade.gibbonPersonID=:gibbonPersonIDStudent
                AND FIND_IN_SET(gibbonCourse.gibbonDepartmentID, :gibbonDepartmentIDList)
                AND arrLegacyGrade.reportTerm='Final'
                ) ORDER BY schoolYearName, courseOrder, courseNameShort";
        $result = $this->pdo->executeQuery($data, $sql);

        return $result;
    }
}
