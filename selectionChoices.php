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
use Gibbon\Modules\CourseSelection\Domain\AccessGateway;
use Gibbon\Modules\CourseSelection\Domain\OfferingsGateway;
use Gibbon\Modules\CourseSelection\Domain\BlocksGateway;
use Gibbon\Modules\CourseSelection\Domain\SelectionsGateway;
use Gibbon\Modules\CourseSelection\Domain\ToolsGateway;
use Gibbon\Modules\CourseSelection\Form\CourseSelectionFormFactory;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/selectionChoices.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Course Selection Choices', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    $highestGroupedAction = getHighestGroupedAction($guid, '/modules/Course Selection/selectionChoices.php', $connection2);

    $gibbonPersonIDStudent = isset($_REQUEST['gibbonPersonIDStudent'])? $_REQUEST['gibbonPersonIDStudent'] : 0;
    $courseSelectionOfferingID = isset($_REQUEST['courseSelectionOfferingID'])? $_REQUEST['courseSelectionOfferingID'] : 0;

    // Cancel out early if there's no valid student or course offering selected
    if (empty($courseSelectionOfferingID) || empty($gibbonPersonIDStudent)) {
        echo '<div class="error">';
            echo __('You do not have access to this action.');
        echo '</div>';
        return;
    }

    // Cancel out if a student is accessing a different student record
    if ($highestGroupedAction != 'Course Selection_all' && $gibbonPersonIDStudent != $_SESSION[$guid]['gibbonPersonID']) {
        echo "<div class='error'>" ;
            echo __('You do not have access to this action.');
        echo "</div>" ;
        return;
    }

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $accessGateway = new AccessGateway($pdo);
    $offeringsGateway = new OfferingsGateway($pdo);
    $blocksGateway = new BlocksGateway($pdo);
    $selectionsGateway = new SelectionsGateway($pdo);

    if ($highestGroupedAction == 'Course Selection_all') {
        $accessRequest = $accessGateway->getAccessByPerson($_SESSION[$guid]['gibbonPersonID']);
    } else {
        $accessRequest = $accessGateway->getAccessByOfferingAndPerson($courseSelectionOfferingID, $_SESSION[$guid]['gibbonPersonID']);
    }

    $offeringRequest = $offeringsGateway->selectOne($courseSelectionOfferingID);

    if (!$accessRequest || $accessRequest->rowCount() == 0 || !$offeringRequest || $offeringRequest->rowCount() == 0) {
        echo "<div class='error'>" ;
            echo __('You do not have access to course selection at this time.');
        echo "</div>" ;
    } else {
        $access = $accessRequest->fetch();
        $offering = $offeringRequest->fetch();

        $accessTypes = explode(',', $access['accessTypes']);
        $readOnly = (in_array('Request', $accessTypes) || in_array('Select', $accessTypes)) == false && !($highestGroupedAction == 'Course Selection_all');

        echo '<h3>';
        echo __('Course Selection').' '.$access['schoolYearName'];
        echo '</h3>';

        if ($gibbonPersonIDStudent != $_SESSION[$guid]['gibbonPersonID']) {
            $studentRequest = $selectionsGateway->selectStudentDetails($gibbonPersonIDStudent);
            if ($studentRequest && $studentRequest->rowCount() > 0) {
                $student = $studentRequest->fetch();
                echo '<p>';
                echo __('Selecting courses for ');
                echo '<a href="'.$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$gibbonPersonIDStudent.'" target="_blank">';
                echo '<strong>'.formatName('', $student['preferredName'], $student['surname'], 'Student', false, true).'</strong>';
                echo '</a></p>';
            }
        }

        $infoTextBefore = getSettingByScope($connection2, 'Course Selection', 'infoTextSelectionBefore');
        if (!empty($infoTextBefore)) {
            echo '<p>'.$infoTextBefore.'</p>';
        }

        $offeringChoiceRequest = $selectionsGateway->selectChoiceOffering($offering['gibbonSchoolYearID'], $gibbonPersonIDStudent);
        $offeringChoice = ($offeringChoiceRequest->rowCount() > 0)? $offeringChoiceRequest->fetchColumn(0) : 0;

        if (!empty($offeringChoice) && $offeringChoice != $courseSelectionOfferingID) {
            echo '<div class="warning">';
                echo __('You have changed your previous course offering selection. Submitting your course choices here will replace any choices selected in a previous course offering.');
            echo '</div>';
        }

        $form = Form::create('selectionChoices', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/selectionChoicesProcess.php');
        $form->setFactory(CourseSelectionFormFactory::create($selectionsGateway));

        $form->setClass('fullWidth');
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);
        $form->addHiddenValue('courseSelectionOfferingID', $courseSelectionOfferingID);
        $form->addHiddenValue('gibbonSchoolYearID', $offering['gibbonSchoolYearID']);
        $form->addHiddenValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);

        $form->addRow()->addHeading($offering['name'])->append($offering['description']);

        $row = $form->addRow()->setClass('break');
            $row->addContent(__('Department'));
            $row->addContent(__('Course Marks'));
            $row->addContent(__('Choices'));

            if ($readOnly == false) {
                $row->addContent(__('Progress'));
            }

        $alreadySelected = array();

        $blocksRequest = $offeringsGateway->selectAllBlocksByOffering($courseSelectionOfferingID);
        if ($blocksRequest && $blocksRequest->rowCount() > 0) {
            while ($block = $blocksRequest->fetch()) {
                if ($block['courseCount'] == 0) continue;

                $courseSelectionBlockID = $block['courseSelectionBlockID'];

                $fieldName = 'courseSelection['.$courseSelectionBlockID.'][]';

                $gradesRequest = $selectionsGateway->selectStudentReportGradesByDepartments($block['gibbonDepartmentIDList'], $gibbonPersonIDStudent);
                $coursesRequest = $selectionsGateway->selectCoursesByBlock($courseSelectionBlockID);

                $selectedChoicesRequest = $selectionsGateway->selectChoicesByBlockAndPerson($courseSelectionBlockID, $gibbonPersonIDStudent);
                $selectedChoices = ($selectedChoicesRequest->rowCount() > 0)? $selectedChoicesRequest->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();

                // Prevent pre-filled courses from selecting multiple times (gives priority to the first instance)
                foreach ($selectedChoices as $gibbonCourseID => $choice) {
                    if (!empty($alreadySelected[$gibbonCourseID]) && ($alreadySelected[$gibbonCourseID] == 'Required' || $alreadySelected[$gibbonCourseID] == 'Approved')) {
                        $selectedChoices[$gibbonCourseID]['status'] = 'Locked';
                    }

                    $alreadySelected[$gibbonCourseID] = $choice['status'];
                }

                $row = $form->addRow();
                $row->addLabel('courseSelection', $block['blockName'])->description($block['blockDescription']);
                $row->addCourseGrades()->fromResults($gradesRequest);
                $row->addCourseSelection($fieldName, $courseSelectionBlockID, $gibbonPersonIDStudent)
                    ->fromResults($coursesRequest)
                    ->selected($selectedChoices)
                    ->setReadOnly($readOnly)
                    ->setBlockID($courseSelectionBlockID)
                    ->canSelectStatus($highestGroupedAction == 'Course Selection_all');

                if ($readOnly == false) {
                    $row->addCourseProgressByBlock($block);
                }
            }
        }

        $unofferedChoicesRequest = $selectionsGateway->selectUnofferedChoicesByPerson($courseSelectionOfferingID, $gibbonPersonIDStudent);
        if ($unofferedChoicesRequest && $unofferedChoicesRequest->rowCount() > 0) {
            $unofferedChoices = $unofferedChoicesRequest->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE);

            $row = $form->addRow();
            $row->addLabel('courseSelection', __('Other Courses'));
            $row->addContent();
            $row->addCourseSelection($fieldName, $courseSelectionBlockID, $gibbonPersonIDStudent)
                ->fromArray($unofferedChoices)
                ->selected($unofferedChoices)
                ->setReadOnly($readOnly)
                ->setBlockID(null)
                ->canSelectStatus($highestGroupedAction == 'Course Selection_all');

            if ($readOnly == false) {
                $row->addContent();
            }

        }

        if ($readOnly == false) {
            $row = $form->addRow();
                $progress = $row->addCourseProgressByOffering($offering);
                $progress->setMessage('complete', getSettingByScope($connection2, 'Course Selection', 'selectionComplete'));
                $progress->setMessage('invalid', getSettingByScope($connection2, 'Course Selection', 'selectionInvalid'));
                $progress->setMessage('continue', getSettingByScope($connection2, 'Course Selection', 'selectionContinue'));

            $row = $form->addRow();
                $row->addSubmit();
        }

        echo $form->getOutput();

        $infoTextAfter = getSettingByScope($connection2, 'Course Selection', 'infoTextSelectionAfter');
        if (!empty($infoTextAfter)) {
            echo '<br/><p>'.$infoTextAfter.'</p>';
        }

        if ($highestGroupedAction == 'Course Selection_all') {
            $toolsGateway = new ToolsGateway($pdo);

            echo '<h3>';
            echo __('Add a Course Selection');
            echo '</h3>';

            // MANUALLY ADD COURSE
            $form = Form::create('selectByStudent', $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/selectionChoices_addProcess.php');
            $form->setFactory(DatabaseFormFactory::create($pdo));

            $form->addHiddenValue('address', $_SESSION[$guid]['address']);
            $form->addHiddenValue('gibbonSchoolYearID', $access['gibbonSchoolYearID']);
            $form->addHiddenValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);
            $form->addHiddenValue('courseSelectionOfferingID', $courseSelectionOfferingID);

            $courses = array();
            $courseResults = $toolsGateway->selectAllCoursesBySchoolYear($access['gibbonSchoolYearID']);
            if ($courseResults && $courseResults->rowCount() > 0) {
                while ($row = $courseResults->fetch()) {
                    $courses[$row['grouping']][$row['value']] = $row['name'];
                }
            }

            $row = $form->addRow();
                $row->addLabel('gibbonCourseID', __('Course'));
                $row->addSelect('gibbonCourseID')->fromArray($courses)->isRequired();

            $row = $form->addRow();
                    $row->addLabel('status', __('Selection Status'));
                    $row->addSelect('status')->fromArray(array('Required', 'Recommended', 'Selected', 'Approved', 'Requested'))->isRequired();

            $row = $form->addRow();
                $row->addSubmit('Add');

            echo $form->getOutput();
        }
    }


}
