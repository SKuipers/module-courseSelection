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

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Modules\CourseSelection\Domain\ToolsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tools_copy.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Copy Course Selections', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $toolsGateway = new ToolsGateway($pdo);

    $gibbonSchoolYearID = $_GET['gibbonSchoolYearID'] ?? '';
    $gibbonSchoolYearIDCopyTo = $_GET['gibbonSchoolYearIDCopyTo'] ?? '';
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';

    if ($gibbonSchoolYearID == '' or $gibbonSchoolYearID == $_SESSION[$guid]['gibbonSchoolYearID']) {
        $gibbonSchoolYearID = $_SESSION[$guid]['gibbonSchoolYearID'];
        $gibbonSchoolYearName = $_SESSION[$guid]['gibbonSchoolYearName'];
    } else {
        $schoolYearResults = $toolsGateway->selectSchoolYear($gibbonSchoolYearID);

        if ($schoolYearResults->rowCount() != 1) {
            echo "<div class='error'>";
            echo __($guid, 'The specified record does not exist.');
            echo '</div>';
        } else {
            $row = $schoolYearResults->fetch();
            $gibbonSchoolYearID = $row['gibbonSchoolYearID'];
            $gibbonSchoolYearName = $row['name'];
        }
    }

    if (empty($gibbonSchoolYearID)) {
        echo "<div class='error'>";
        echo __($guid, 'The specified record does not exist.');
        echo '</div>';
        return;
    }

    echo '<h2>';
    echo $gibbonSchoolYearName;
    echo '</h2>';

    echo "<div class='linkTop'>";
        //Print year picker
        $previousYear = getPreviousSchoolYearID($gibbonSchoolYearID, $connection2);
        $nextYear = getNextSchoolYearID($gibbonSchoolYearID, $connection2);
        if (!empty($previousYear)) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tools_copy.php&gibbonSchoolYearID='.getPreviousSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Previous Year').'</a> ';
        } else {
            echo __($guid, 'Previous Year').' ';
        }
        echo ' | ';
        if (!empty($nextYear)) {
            echo "<a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.$_SESSION[$guid]['module'].'/tools_copy.php&gibbonSchoolYearID='.getNextSchoolYearID($gibbonSchoolYearID, $connection2)."'>".__($guid, 'Next Year').'</a> ';
        } else {
            echo __($guid, 'Next Year').' ';
        }
    echo '</div>';

    // SELECT COURSE
    $form = Form::create('copySelectionsPicker', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Course Selection/tools_copy.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $courses = array();
    $courseResults = $toolsGateway->selectCoursesBySchoolYear($gibbonSchoolYearID);
    if ($courseResults && $courseResults->rowCount() > 0) {
        while ($row = $courseResults->fetch()) {
            $courses[$row['grouping']][$row['value']] = $row['name'];
        }
    }

    $form->addRow()->addContent(__("This tool lets you copy student enrolments for a course and convert them into course selections for a different course. After selecting the year and course below you'll have the option to select students and the destination course to copy to."))->wrap('<br/><p>', '</p>');

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearName', __('Copy from School Year'));
        $row->addTextField('gibbonSchoolYearName')->readonly()->setValue($gibbonSchoolYearName);

    $row = $form->addRow();
        $row->addLabel('gibbonCourseID', __('Copy from Course'));
        $row->addSelect('gibbonCourseID')->fromArray($courses)->isRequired()->selected($gibbonCourseID);

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearIDCopyTo', __('Destination School Year'));
        $row->addSelectSchoolYear('gibbonSchoolYearIDCopyTo', 'Active')->isRequired()->selected( (!empty($gibbonSchoolYearIDCopyTo)? $gibbonSchoolYearIDCopyTo : $nextYear));

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    // SELECT STUDENTS
    if (!empty($gibbonCourseID) && !empty($gibbonSchoolYearIDCopyTo)) {
        echo '<h2>';
        echo __($guid, 'Current Participants');
        echo '</h2>';

        $studentsResults = $toolsGateway->selectStudentsByCourse($gibbonCourseID);

        $form = Form::create('copySelectionsStudents', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tools_copyProcess.php', 'post');

        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        $form->addHiddenValue('gibbonSchoolYearIDCopyTo', $gibbonSchoolYearIDCopyTo);
        $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
        $form->addHiddenValue('gibbonPersonIDSelected', $_SESSION[$guid]['gibbonPersonID']);

        $row = $form->addRow()->setClass('break');
            $row->addContent(__('Student Name'));
            $row->addContent('<input type="checkbox" class="checkall" checked>')->setClass('right');

        while ($student = $studentsResults->fetch()) {
            $row = $form->addRow();
                $row->addLabel('studentList[]', formatName('', $student['preferredName'], $student['surname'], 'Student', true));
                $row->addCheckbox('studentList[]')->setValue($student['gibbonPersonID'])->checked($student['gibbonPersonID']);
        }

        $form->addRow()->addHeading(__('Copy to Course'))->append(__('Course selections will be created for each of the students selected here in the following course:'));

        $schoolYearCopyToResults = $toolsGateway->selectSchoolYear($gibbonSchoolYearIDCopyTo);
        $schoolYearCopyToName = ($schoolYearCopyToResults->rowCount() > 0)? $schoolYearCopyToResults->fetchColumn(1) : '';

        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearCopyToName', __('Destination School Year'));
            $row->addTextField('gibbonSchoolYearCopyToName')->readonly()->setValue($schoolYearCopyToName);

        $coursesCopyTo = array();
        $courseCopyToResults = $toolsGateway->selectCoursesBySchoolYear($gibbonSchoolYearIDCopyTo);
        if ($courseCopyToResults && $courseCopyToResults->rowCount() > 0) {
            while ($row = $courseCopyToResults->fetch()) {
                $coursesCopyTo[$row['grouping']][$row['value']] = $row['name'];
            }
        }

        $row = $form->addRow();
            $row->addLabel('gibbonCourseIDCopyTo', __('Course Selection'));
            $row->addSelect('gibbonCourseIDCopyTo', 'Active')->fromArray($coursesCopyTo)->isRequired();

        $row = $form->addRow();
            $row->addLabel('status', __('Selection Status'));
            $row->addSelect('status')->fromArray(array('Required',  'Recommended', 'Selected', 'Approved', 'Requested'))->isRequired();

        $row = $form->addRow();
            $row->addLabel('overwrite', __('Overwrite?'))->description(__('Replace the course selection status if one already exists for that student and course.'));
            $row->addYesNo('overwrite')->isRequired()->selected('Yes');

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
        ?>

        <script type="text/javascript">
            $(function () {
                $('.checkall').click(function () {
                    $(this).parents('#copySelectionsStudents').find(':checkbox').attr('checked', this.checked);
                });
            });
        </script>
        <?
    }
}
