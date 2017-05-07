<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace CourseSelection\Domain;

/**
 * Course Selection: Timetable Gateway
 *
 * @version v14
 * @since   4th May 2017
 * @author  Sandra Kuipers
 *
 * @uses
 */
class TimetableGateway
{
    protected $pdo;

    public function __construct(\Gibbon\sqlConnection $pdo)
    {
        $this->pdo = $pdo;
    }

    public function selectTimetabledCoursesBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) as `0`, COUNT(DISTINCT gibbonCourseClassPerson.gibbonPersonID) as students, gibbonCourse.name as courseName, gibbonCourse.nameShort as courseNameShort, CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) as className, gibbonCourseClass.nameShort as period
                FROM gibbonCourse
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                JOIN courseSelectionOffering ON (courseSelectionOffering.gibbonSchoolYearID=gibbonCourse.gibbonSchoolYearID)
                JOIN courseSelectionOfferingRestriction ON (courseSelectionOfferingRestriction.courseSelectionOfferingID=courseSelectionOffering.courseSelectionOfferingID
                                                            AND FIND_IN_SET(courseSelectionOfferingRestriction.gibbonYearGroupID, gibbonCourse.gibbonYearGroupIDList))
                LEFT JOIN gibbonCourseClassPerson ON (gibbonCourseClassPerson.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID AND gibbonCourseClassPerson.role='Student')
                WHERE gibbonCourse.gibbonSchoolYearID=:gibbonSchoolYearID
                GROUP BY gibbonCourseClass.gibbonCourseClassID";

        return $this->pdo->executeQuery($data, $sql);
    }

    public function selectApprovedCourseSelectionsBySchoolYear($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT courseSelectionChoice.gibbonPersonIDStudent, gibbonCourse.gibbonCourseID, CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) as className, gibbonCourseClass.nameShort as period
                FROM courseSelectionChoice
                JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
                JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
                JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
                WHERE courseSelectionChoice.gibbonSchoolYearID=:gibbonSchoolYearID
                AND courseSelectionChoice.status <> 'Removed'
                AND courseSelectionChoice.status <> 'Recommended'
                GROUP BY courseSelectionChoice.courseSelectionChoiceID, gibbonCourseClass.gibbonCourseClassID
                ORDER BY gibbonCourse.orderBy, gibbonCourse.nameShort";

        return $this->pdo->executeQuery($data, $sql);
    }
}
