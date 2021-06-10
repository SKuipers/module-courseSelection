<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_delete.php') == false) {
	//Acess denied
	echo "<div class='error'>" ;
		echo __('You do not have access to this action.');
	echo "</div>" ;
} else {
    $courseSelectionOfferingID = $_GET['courseSelectionOfferingID'] ?? '';

    if ($courseSelectionOfferingID == '') {
        echo "<div class='error'>";
        echo __($guid, 'You have not specified one or more required parameters.');
        echo '</div>';
    } else {
        $form = Form::create('accessRecord', $session->get('absoluteURL').'/modules/'.$session->get('module').'/offerings_manage_deleteProcess.php');

        $form->addHiddenValue('courseSelectionOfferingID', $courseSelectionOfferingID);
        $form->addHiddenValue('address', $session->get('address'));

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
