<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/settings.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $session->get('absoluteURL') . "'>" . __($guid, "Home") . "</a> > <a href='" . $session->get('absoluteURL') . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Course Selection Settings', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $form = Form::create('settings', $session->get('absoluteURL').'/modules/Course Selection/settingsProcess.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $session->get('address'));

    $setting = getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addSelectSchoolYear($setting['name'], 'Active')->selected($setting['value'])->required();

    $setting = getSettingByScope($connection2, 'Course Selection', 'requireApproval', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value'])->required();

    $form->addRow()->addHeading(__('Information'));

    $setting = getSettingByScope($connection2, 'Course Selection', 'infoTextOfferings', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(4)->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Course Selection', 'infoTextSelectionBefore', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(4)->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Course Selection', 'infoTextSelectionAfter', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(4)->setValue($setting['value']);

    $form->addRow()->addHeading(__('Course Selection Messages'));

    $setting = getSettingByScope($connection2, 'Course Selection', 'selectionComplete', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(4)->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Course Selection', 'selectionInvalid', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(4)->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Course Selection', 'selectionContinue', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addTextArea($setting['name'])->setRows(4)->setValue($setting['value']);

    $form->addRow()->addHeading(__('Timetabling'));

    $setting = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentMinimum', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentTarget', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->setValue($setting['value']);

    $setting = getSettingByScope($connection2, 'Course Selection', 'classEnrolmentMaximum', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addNumber($setting['name'])->setValue($setting['value']);

    $form->addRow()->addHeading(__('Reporting Integration'));

    $setting = getSettingByScope($connection2, 'Course Selection', 'enableCourseGrades', true);
    $row = $form->addRow();
        $row->addLabel($setting['name'], __($setting['nameDisplay']))->description(__($setting['description']));
        $row->addYesNo($setting['name'])->selected($setting['value']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
