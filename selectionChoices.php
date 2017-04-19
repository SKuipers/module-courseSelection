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
use Gibbon\Modules\CourseSelection\Form\CourseSelectionFormFactory;

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/selection.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Course Selection Choices', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $highestGroupedAction = getHighestGroupedAction($guid, '/modules/Course Selection/selection.php', $connection2);

    $gibbonPersonIDStudent = isset($_REQUEST['gibbonPersonIDStudent'])? $_REQUEST['gibbonPersonIDStudent'] : 0;
    $courseSelectionOfferingID = isset($_REQUEST['courseSelectionOfferingID'])? $_REQUEST['courseSelectionOfferingID'] : 0;

    // Cancel out early if there's no valid student or course offering selected
    if (empty($courseSelectionOfferingID) || empty($gibbonPersonIDStudent)) {
        echo '<div class="error">';
            echo __('You do not have access to this action.');
        echo '</div>';
        return;
    }

    $accessGateway = new AccessGateway($pdo);
    $offeringsGateway = new OfferingsGateway($pdo);
    $blocksGateway = new BlocksGateway($pdo);
    $selectionsGateway = new SelectionsGateway($pdo);

    $accessRequest = $accessGateway->getAccessByOfferingAndPerson($courseSelectionOfferingID, $gibbonPersonIDStudent);
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
        $form->addHiddenValue('gibbonPersonIDSelected', $_SESSION[$guid]['gibbonPersonID']);

        $form->addRow()->addHeading($offering['name'])->append($offering['description']);

        $row = $form->addRow()->setClass('break');
            $row->addContent(__('Department'));
            $row->addContent(__('Course Marks'));
            $row->addContent(__('Choices'));
            $row->addContent(__('Progress'));

        $blocksRequest = $offeringsGateway->selectAllBlocksByOffering($courseSelectionOfferingID);
        if ($blocksRequest && $blocksRequest->rowCount() > 0) {
            $blocksByDepartment = $blocksRequest->fetchAll(\PDO::FETCH_GROUP);

            foreach ($blocksByDepartment as $gibbonDepartmentID => $blocks) {
                $departmentRequest = $offeringsGateway->selectDepartmentByID($gibbonDepartmentID);
                $department = $departmentRequest->fetch();

                foreach ($blocks as $block) {
                    if ($block['courseCount'] == 0) continue;

                    $row = $form->addRow();
                    $row->addLabel('courseSelection', $block['blockName'])->description($block['blockDescription'])->setTitle($department['name']);
                    $row->addCourseGrades($gibbonDepartmentID, $gibbonPersonIDStudent);
                    $row->addCourseSelection('courseSelection', $block['courseSelectionBlockID'], $gibbonPersonIDStudent)->setReadOnly($readOnly);
                    $row->addCourseProgressByBlock($block);
                }
            }
        }

        $row = $form->addRow();
            $row->addCourseProgressByOffering($offering);

        if ($readOnly == false) {
            $row = $form->addRow();
                $row->addSubmit();
        }

        echo $form->getOutput();

        $infoTextBefore = getSettingByScope($connection2, 'Course Selection', 'infoTextSelectionAfter');
        if (!empty($infoTextBefore)) {
            echo '<br/><p>'.$infoTextBefore.'</p>';
        }
    }
}
