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

// Autoloader & Module includes
$loader->addNameSpace('Gibbon\Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_delete.php') == false) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __('You do not have access to this action.');
	echo "</div>" ;
} else {
    $courseSelectionBlockID = $_GET['courseSelectionBlockID'] ?? '';

    if ($courseSelectionBlockID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $form = Form::create('accessRecord', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/blocks_manage_deleteProcess.php');

        $form->addHiddenValue('courseSelectionBlockID', $courseSelectionBlockID);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow()->addHeading(__('Are you sure you want to delete this record?'));

        $row = $form->addRow();
            $row->addContent(__('This operation cannot be undone, and may lead to loss of vital data in your system. PROCEED WITH CAUTION!'))->wrap('<span style="color: #cc0000"><i>', '</i></span>');

        $row = $form->addRow();
            $row->addLabel('confirm', sprintf(__('Type %1$s to confirm'), __('DELETE')) );
            $row->addTextField('confirm')
                ->addValidation('Validate.Presence')
                ->addValidation('Validate.Inclusion',
                    'within: [\''.__('DELETE').'\'], failureMessage: "'.__(' Please enter the text exactly as it is displayed to confirm this action.').'"')
                ->addValidationOption('onlyOnSubmit: true');

        $form->addRow()->addSubmit();

        echo $form->getOutput();
    }
}
