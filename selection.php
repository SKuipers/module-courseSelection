<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use CourseSelection\Domain\AccessGateway;
use CourseSelection\Domain\OfferingsGateway;
use CourseSelection\Domain\SelectionsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/selection.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Course Selection', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $highestGroupedAction = getHighestGroupedAction($guid, '/modules/Course Selection/selection.php', $connection2);

    if ($highestGroupedAction == 'Course Selection_all') {
        $gibbonPersonIDStudent = isset($_REQUEST['gibbonPersonIDStudent'])? $_REQUEST['gibbonPersonIDStudent'] : 0;

        $form = Form::create('selectStudent', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/selection.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDStudent', __('Student'));
            $row->addSelectStudent('gibbonPersonIDStudent')->selected($gibbonPersonIDStudent);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    } else if ($highestGroupedAction == 'Course Selection_my') {
        $gibbonPersonIDStudent = $_SESSION[$guid]['gibbonPersonID'];
    }

    // Cancel out early if there's no valid student selected
    if (empty($gibbonPersonIDStudent)) return;

    $accessGateway = new AccessGateway($pdo);
    $offeringsGateway = new OfferingsGateway($pdo);
    $selectionsGateway = new SelectionsGateway($pdo);

    $accessRequest = $accessGateway->getAccessByPerson($_SESSION[$guid]['gibbonPersonID']);

    if (!$accessRequest || $accessRequest->rowCount() == 0) {
        echo "<div class='error'>" ;
            echo __('You do not have access to course selection at this time.');
        echo "</div>" ;
    } else {

        while ($access = $accessRequest->fetch()) {
            echo '<h3>';
                echo __('Course Selection').' '.$access['schoolYearName'];
            echo '</h3>';

            $accessTypes = explode(',', $access['accessTypes']);
            $readOnly = (in_array('Request', $accessTypes) || in_array('Select', $accessTypes)) == false && !($highestGroupedAction == 'Course Selection_all');

            $offeringsRequest = $offeringsGateway->selectOfferingsByStudentEnrolment($access['gibbonSchoolYearID'], $gibbonPersonIDStudent);

            if ($offeringsRequest && $offeringsRequest->rowCount() > 0) {

                $today = date('Y-m-d');

                if ((in_array('Request', $accessTypes) || in_array('Select', $accessTypes)) == false) {
                    $accessMessageClass = 'message';
                    $accessMessageText = sprintf(__('Course selection is currently %1$s.'), __('View Only'));
                } else if ($today >= $access['dateStart'] && $today <= $access['dateEnd']) {
                    $accessMessageClass = 'success';
                    $accessMessageText = sprintf(__('Course selection is currently %1$s.'), __('Open'));
                } else {
                    $accessMessageClass = 'warning';
                    $accessMessageText = sprintf(__('Course selection is currently %1$s.'), __('Closed'));
                }

                echo '<div class="'.$accessMessageClass.'">';
                    echo $accessMessageText.' '.sprintf(__('Access is available from %1$s to %2$s'),
                        date('M j', strtotime($access['dateStart'])),
                        date('M j, Y', strtotime($access['dateEnd']))
                    );
                echo '</div>';

                $infoText = getSettingByScope($connection2, 'Course Selection', 'infoTextOfferings');
                if (!empty($infoText)) {
                    echo '<p>'.$infoText.'</p>';
                }

                $offeringChoiceRequest = $selectionsGateway->selectChoiceOffering($access['gibbonSchoolYearID'], $gibbonPersonIDStudent);
                $offeringChoice = ($offeringChoiceRequest->rowCount() > 0)? $offeringChoiceRequest->fetchColumn(0) : 0;

                $form = Form::create('selection', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/selectionChoices.php&sidebar=false');

                $form->setClass('fullWidth');
                $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                $form->addHiddenValue('gibbonSchoolYearID', $access['gibbonSchoolYearID']);
                $form->addHiddenValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);

                $form->addRow()->addSubHeading(__('Course Offerings'));

                while ($offering = $offeringsRequest->fetch()) {
                    $row = $form->addRow();
                        $row->addLabel('courseSelectionOfferingID', $offering['name'])->description($offering['description']);
                        $row->addRadio('courseSelectionOfferingID')
                            ->isRequired()
                            ->setClass('')
                            ->fromArray(array($offering['courseSelectionOfferingID'] => ''))
                            ->checked($offeringChoice)
                            ->setDisabled($readOnly);

                        if ($readOnly) {
                            $form->addHiddenValue('courseSelectionOfferingID', $offeringChoice);
                        }
                }

                $row = $form->addRow();
                    $row->addSubmit( ($readOnly)? __('View') : __('Select') );

                echo $form->getOutput();
            } else {
                echo "<div class='error'>" ;
                    echo __('There are no course offerings available at this time.');
                echo "</div>" ;
            }

        }
    }
}
