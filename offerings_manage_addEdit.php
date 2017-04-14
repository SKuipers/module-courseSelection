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
use Modules\CourseSelection\Domain\OfferingsGateway;

// Autoloader & Module includes
$loader->addNameSpace('Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_addEdit.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo "You do not have access to this action." ;
    echo "</div>" ;
} else {
    $gateway = new OfferingsGateway($pdo);

    $values = array(
        'courseSelectionOfferingID' => '',
        'gibbonSchoolYearID'        => '',
        'gibbonYearGroupIDList'     => '',
        'name'                      => '',
        'description'               => '',
        'minSelect'                 => '0',
        'maxSelect'                 => '1',
        'sequenceNumber'            => $gateway->getNextSequenceNumber()
    );

    if (isset($_GET['courseSelectionOfferingID'])) {
        $result = $gateway->selectOne($_GET['courseSelectionOfferingID']);
        if ($result && $result->rowCount() == 1) {
            $values = $result->fetch();
        }

        $actionName = __('Edit Course Offering');
        $actionURL = $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/offerings_manage_editProcess.php';
    } else {
        $actionName = __('Add Course Offering');
        $actionURL = $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/offerings_manage_addProcess.php';
    }

    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/offerings_manage.php'>".__('Manage Course Offerings', 'Course Selection')."</a> > </div><div class='trailEnd'>".$actionName.'</div>';
    echo "</div>" ;

    if (isset($_GET['return'])) {
        $editLink = (isset($_GET['editID']))? $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/offerings_manage_addEdit.php&courseSelectionOfferingID='.$_GET['editID'] : '';
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('offeringsAddEdit', $actionURL);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('courseSelectionOfferingID', $values['courseSelectionOfferingID']);
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearID', __('School Year'));
        $row->addSelectSchoolYear('gibbonSchoolYearID', 'Active')->isRequired()->selected($values['gibbonSchoolYearID']);

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->isRequired()->maxLength(90)->setValue($values['name']);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->maxLength(255)->setValue($values['description']);

    $row = $form->addRow();
        $row->addLabel('minSelect', __('Min Selections'));
        $row->addNumber('minSelect')->isRequired()->minimum(0)->maximum(100)->setValue($values['minSelect']);

    $row = $form->addRow();
        $row->addLabel('maxSelect', __('Max Selections'));
        $row->addNumber('maxSelect')->isRequired()->minimum(0)->maximum(100)->setValue($values['maxSelect']);

    $row = $form->addRow();
        $row->addLabel('gibbonYearGroupIDList', __('Year Groups'));
        $row->addCheckboxYearGroup('gibbonYearGroupIDList')->checked(explode(',', $values['gibbonYearGroupIDList']));

    $row = $form->addRow();
        $row->addLabel('sequenceNumber', __('Sequence Number'));
        $row->addNumber('sequenceNumber')->isRequired()->minimum(0)->maximum(999)->setValue($values['sequenceNumber']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
