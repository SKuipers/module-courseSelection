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
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Course Selection', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $highestGroupedAction = getHighestGroupedAction($guid, '/modules/Course Selection/selection.php', $connection2);

    if ($highestGroupedAction == 'Course Selection_all') {
        $gibbonPersonIDStudent = isset($_POST['gibbonPersonIDStudent'])? $_POST['gibbonPersonIDStudent'] : 0;

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

    $accessRequest = $accessGateway->getAccessByPerson($gibbonPersonIDStudent);

    if (!$accessRequest || $accessRequest->rowCount() == 0) {
        echo "<div class='error'>" ;
            echo __('You do not have access to course selection at this time.');
        echo "</div>" ;
    } else {

        while ($access = $accessRequest->fetch()) {
            echo '<h3>';
                echo __('Course Selection').' '.$access['schoolYearName'];
            echo '</h3>';

            $today = date('Y-m-d');

            if ($today >= $access['dateStart'] && $today <= $access['dateEnd']) {
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

            $accessTypes = explode(',', $access['accessTypes']);
            if ($highestGroupedAction == 'Course Selection_all' || in_array('Select', $accessTypes) || in_array('Request', $accessTypes) ) {
                $offeringsRequest = $offeringsGateway->selectOfferingsByStudentEnrolment($access['gibbonSchoolYearID'], $gibbonPersonIDStudent);

                if ($offeringsRequest && $offeringsRequest->rowCount() > 0) {

                    $form = Form::create('selection', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/selectionChoices.php&sidebar=false');

                    $form->setClass('fullWidth');
                    $form->addHiddenValue('address', $_SESSION[$guid]['address']);
                    $form->addHiddenValue('gibbonPersonIDStudent', $gibbonPersonIDStudent);

                    $form->addRow()->addSubHeading(__('Course Offerings'));

                    while ($offering = $offeringsRequest->fetch()) {
                        $row = $form->addRow();
                            $row->addLabel('courseSelectionOfferingID', $offering['name'])->description($offering['description']);
                            $row->addRadio('courseSelectionOfferingID')
                                ->setClass('')
                                ->fromArray(array($offering['courseSelectionOfferingID'] => ''));
                    }

                    $row = $form->addRow();
                        $row->addSubmit();

                    echo $form->getOutput();
                } else {
                    echo "<div class='error'>" ;
                        echo __('There are no course offerings available at this time.');
                    echo "</div>" ;
                }
            } else {
                echo "<div class='error'>" ;
                    echo __('The course selection interval is view-only at this time.');
                echo "</div>" ;
            }
        }
    }
}
