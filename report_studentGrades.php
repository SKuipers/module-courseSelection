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

use CourseSelection\Domain\SelectionsGateway;
use CourseSelection\Domain\GradesGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/approval_byOffering.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Student Grades', 'Course Selection') . "</div>" ;
    echo "</div>" ;
    echo "<br/>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonPersonIDStudent = isset($_REQUEST['gibbonPersonIDStudent'])? $_REQUEST['gibbonPersonIDStudent'] : 0;

    $selectionsGateway = new SelectionsGateway($pdo);
    $gradesGateway = new GradesGateway($pdo);

    $studentResults = $selectionsGateway->selectStudentDetails($gibbonPersonIDStudent);
    $studentEnrolmentResults = $gradesGateway->selectStudentEnrolmentByStudent($gibbonPersonIDStudent);

    if ($studentResults->rowCount() == 0 || $studentEnrolmentResults->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {
        $student = $studentResults->fetch();

        echo '<br/>';
        echo '<div>';
        echo __('Viewing report grades for ');
        echo '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonIDStudent.'" target="_blank">';
        echo '<strong>'.formatName('', $student['preferredName'], $student['surname'], 'Student', true).'</strong>';
        echo '</a>';
        echo '</div>';

        while ($enrolment = $studentEnrolmentResults->fetch())
        {
            $gradesResults = $gradesGateway->selectStudentReportGradesBySchoolYear($enrolment['schoolYearID'], $gibbonPersonIDStudent);

            if (!$gradesResults || $gradesResults->rowCount() == 0) continue;

            echo '<h3>';
            echo $enrolment['schoolYearName'];
            echo '</h3>';

            echo '<table class="reportGradesTable colorOddEven" cellspacing="0">';
            echo '<tr class="break">';
                echo '<td>'.__('Course').'</td>';
                echo '<td>'.__('Name').'</td>';
                echo '<td>'.__('Sem1-Mid').'</td>';
                echo '<td>'.__('Sem1-End').'</td>';
                echo '<td>'.__('Sem2-Mid').'</td>';
                echo '<td>'.__('Sem2-End').'</td>';
                echo '<td style="border-left: 2px solid #bbb;">'.__('Exam').'</td>';
                echo '<td>'.__('Final').'</td>';
            echo '</tr>';

            $schoolYearGrades = $gradesGateway->processStudentGrades($enrolment, $gradesResults->fetchAll());

            foreach ($schoolYearGrades as $courseGrades) {

                $sem1Mid = (!empty($courseGrades['Sem1-Mid']))? $courseGrades['Sem1-Mid'].'%' : '';
                $sem1MidClass = (!empty($sem1Mid) && intval($sem1Mid) < 50)? 'gradesRow warning' : 'gradesRow';

                $sem1End = (!empty($courseGrades['Sem1-End']))? $courseGrades['Sem1-End'].'%' : '';
                $sem1EndClass = (!empty($sem1End) && intval($sem1End) < 50)? 'gradesRow warning' : 'gradesRow';

                $sem2Mid = (!empty($courseGrades['Sem2-Mid']))? $courseGrades['Sem2-Mid'].'%' : '';
                $sem2MidClass = (!empty($sem2Mid) && intval($sem2Mid) < 50)? 'gradesRow warning' : 'gradesRow';

                $sem2End = (!empty($courseGrades['Sem2-End']))? $courseGrades['Sem2-End'].'%' : '';
                $sem2EndClass = (!empty($sem2End) && intval($sem2End) < 50)? 'gradesRow warning' : 'gradesRow';

                $credits = (!empty($courseGrades['credits']))? $courseGrades['credits'].'%' : '';

                $exam = (!empty($courseGrades['Exam']))? $courseGrades['Exam'].'%' : '';
                $examClass = (!empty($exam) && intval($exam) < 50)? 'gradesRow warning' : 'gradesRow';

                $final = (!empty($courseGrades['Final']))? $courseGrades['Final'].'%' : '';
                $finalClass = (!empty($final) && intval($final) < 50)? 'gradesRow warning' : 'gradesRow';

                if (!empty($courseGrades['gibbonCourseClassID']) && $enrolment['schoolYearID'] == $_SESSION[$guid]['gibbonSchoolYearID']) {
                    $courseGrades['courseNameShort'] = sprintf('<a href="%2$s">%1$s</a>', $courseGrades['courseNameShort'], $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonIDStudent.'&search=&search=&allStudents=&subpage=Markbook#'.str_pad($courseGrades['gibbonCourseClassID'], 8, '0', STR_PAD_LEFT));
                }

                echo '<tr>';
                    echo '<td class="gradesRow" style="width:15%;text-align:left;">'.$courseGrades['courseNameShort'].'</td>';
                    echo '<td class="gradesRow" style="width:37%;text-align:left;">'.$courseGrades['courseName'].'</td>';
                    echo '<td class="'.$sem1MidClass.'" style="width:8%;">'.$sem1Mid.'</td>';
                    echo '<td class="'.$sem1EndClass.'" style="width:8%;">'.$sem1End.'</td>';
                    echo '<td class="'.$sem2MidClass.'" style="width:8%">'.$sem2Mid.'</td>';
                    echo '<td class="'.$sem2EndClass.'" style="width:8%">'.$sem2End.'</td>';
                    echo '<td class="'.$examClass.'" style="width:8%;border-left: 2px solid #bbb;">'.$exam.'</td>';
                    echo '<td class="'.$finalClass.'" style="width:8%;text-align:left;">'.$final.'</td>';
                echo '</tr>';
            }
            echo '</table>';


        }
    }

}
