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
use CourseSelection\SchoolYearNavigation;
use CourseSelection\Domain\ToolsGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tools_copyByCourse.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Copy Selections By Course', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $toolsGateway = new ToolsGateway($pdo);

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $_SESSION[$guid]['gibbonSchoolYearID'];
    $gibbonSchoolYearIDCopyTo = $_GET['gibbonSchoolYearIDCopyTo'] ?? null;
    $gibbonCourseID = $_GET['gibbonCourseID'] ?? '';
    $action = $_GET['action'] ?? '';

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    if ($action == 'Requests') {
        $gibbonSchoolYearIDCopyTo = $gibbonSchoolYearID;
    } else if (empty($gibbonSchoolYearIDCopyTo)) {
        $nextSchoolYear = $navigation->selectNextSchoolYearByID($gibbonSchoolYearID);
        $gibbonSchoolYearIDCopyTo = $nextSchoolYear['gibbonSchoolYearID'] ?? '';
    }

    // SELECT COURSE
    $form = Form::create('copySelectionsPicker', $_SESSION[$guid]['absoluteURL'].'/index.php', 'get');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('q', '/modules/Course Selection/tools_copyByCourse.php');
    $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);

    $courses = array();
    $courseResults = $toolsGateway->selectAllCoursesBySchoolYear($gibbonSchoolYearID);
    if ($courseResults && $courseResults->rowCount() > 0) {
        while ($row = $courseResults->fetch()) {
            $courses[$row['grouping']][$row['value']] = $row['name'];
        }
    }

    $form->addRow()->addContent(__("This tool lets you copy student enrolments or course requests and convert them into requests for a different course. After selecting the year and course below you'll have the option to select students and the destination course to copy to."))->wrap('<br/><p>', '</p>');

    $courseCopyFromResults = $toolsGateway->selectCoursesOfferedBySchoolYear($gibbonSchoolYearID);
    $courseCopyOptions = ($courseCopyFromResults->rowCount() > 0)? array('Enrolments', 'Requests') : array('Enrolments');

    $row = $form->addRow();
        $row->addLabel('action', __('Copy'));
        $row->addSelect('action')->fromArray($courseCopyOptions)->isRequired()->placeholder()->selected($action);

    $form->toggleVisibilityByClass('courseEnrolment')->onSelect('action')->when('Enrolments');
    $form->toggleVisibilityByClass('courseRequests')->onSelect('action')->when('Requests');

    $row = $form->addRow()->addClass('courseEnrolment');
        $row->addLabel('gibbonCourseID', __('Courses from ').$navigation->getSchoolYearName());
        $row->addSelect('gibbonCourseID')->fromArray($courses)->isRequired()->selected($gibbonCourseID);

    $row = $form->addRow()->addClass('courseEnrolment');
        $row->addLabel('gibbonSchoolYearIDCopyTo', __('Destination School Year'))->setClass('mediumWidth');
        $row->addSelectSchoolYear('gibbonSchoolYearIDCopyTo', 'Active')->isRequired()->selected($gibbonSchoolYearIDCopyTo);

    $row = $form->addRow()->addClass('courseRequests');
        $row->addLabel('gibbonCourseID', __('Course'));
        $row->addSelect('gibbonCourseID')->fromResults($courseCopyFromResults)->isRequired()->selected($gibbonCourseID);

    $row = $form->addRow();
        $row->addSubmit();

    echo $form->getOutput();

    // SELECT STUDENTS
    if (!empty($gibbonCourseID) && !empty($gibbonSchoolYearIDCopyTo)) {
        echo '<h2>';
        echo __($guid, (($action == 'Requests')? 'Current Requests' : 'Current Enrolments') );
        echo '</h2>';

        if ($action == 'Requests') {
            $studentsResults = $toolsGateway->selectStudentsByCourseSelection($gibbonCourseID);
        } else {
            $studentsResults = $toolsGateway->selectStudentsByCourse($gibbonCourseID);
        }

        if ($studentsResults->rowCount() == 0) {
            echo '<div class="error">';
            echo __("There are no records to display.") ;
            echo '</div>';
            return;
        }

        $form = Form::create('copySelectionsStudents', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/tools_copyByCourseProcess.php');

        $form->addHiddenValue('gibbonSchoolYearID', $gibbonSchoolYearID);
        $form->addHiddenValue('gibbonSchoolYearIDCopyTo', $gibbonSchoolYearIDCopyTo);
        $form->addHiddenValue('gibbonCourseID', $gibbonCourseID);
        $form->addHiddenValue('action', $action);

        $row = $form->addRow()->setClass('break');
            $row->addContent(__('Student Name'));
            $row->addContent(__('Grade'));
            $row->addContent(__('Current'));
            $row->addContent('<input type="checkbox" class="checkall" checked>')->setClass('right');

        while ($student = $studentsResults->fetch()) {
            $row = $form->addRow()->addClass('rowHighlight');
                $row->addLabel('studentList[]', formatName('', $student['preferredName'], $student['surname'], 'Student', true));
                $row->addContent($student['yearGroupName']);
                $row->addContent($student['courseClassName']);
                $row->addCheckbox('studentList[]')->addClass('studentList')->setValue($student['gibbonPersonID'])->checked($student['gibbonPersonID']);
        }

        $row = $form->addRow();
            $row->addContent('<span><input type="text" class="countall" readonly style="text-align: right;"></span>')->setClass('right');

        $form->addRow()->addHeading(__('Copy to Course'))->append(__('Course selections will be created for each of the students selected here in the following course:'));

        $schoolYearCopyToResults = $toolsGateway->selectSchoolYear($gibbonSchoolYearIDCopyTo);
        $schoolYearCopyToName = ($schoolYearCopyToResults->rowCount() > 0)? $schoolYearCopyToResults->fetchColumn(1) : '';

        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearCopyToName', __('Destination School Year'));
            $row->addTextField('gibbonSchoolYearCopyToName')->readonly()->setValue($schoolYearCopyToName);

        $courseCopyToResults = $toolsGateway->selectCoursesOfferedBySchoolYear($gibbonSchoolYearIDCopyTo);

        $row = $form->addRow();
            $row->addLabel('gibbonCourseIDCopyTo', __('Course Selection'));
            $row->addSelect('gibbonCourseIDCopyTo')->fromResults($courseCopyToResults)->isRequired();

        $row = $form->addRow();
            $row->addLabel('status', __('Selection Status'));
            $row->addSelect('status')->fromArray(array('Required', 'Approved', 'Requested', 'Selected', 'Recommended', 'Removed'))->isRequired();

        $row = $form->addRow();
            $row->addLabel('overwrite', __('Overwrite?'))->description(__('Replace the course selection status if one already exists for that student and course.'));
            $row->addYesNo('overwrite')->isRequired()->selected('Y');

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

                $(':checkbox').change(function () {
                    $('.countall').val( 'Selected: '+$(this).parents('#copySelectionsStudents').find('.studentList:checked').length );
                });

                $('.checkall:checkbox').change();
            });
        </script>
        <?php
    }
}
