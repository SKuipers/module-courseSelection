<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\CourseSelection\Domain\SelectionsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/report_courseSelectionLog.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
	$page->breadcrumbs
    	->add(__m('Activity Log'));

	$settingGateway = $container->get(SettingGateway::class);
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $settingGateway->getSettingByScope('Course Selection', 'activeSchoolYear');

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $selectionsGateway = $container->get(SelectionsGateway::class);

    // QUERY
    $criteria = $selectionsGateway->newQueryCriteria(true)
        ->sortBy(['courseSelectionLog.timestampChanged'], 'DESC')
        ->pageSize(50)
        ->fromPOST();

    $logs = $selectionsGateway->queryAllLogsBySchoolYear($criteria, $gibbonSchoolYearID);

    // TABLE
    $table = DataTable::createPaginated('blocks', $criteria);

    $table->addColumn('offeringName', __('Course Offering'));

    $table->addColumn('name', __('Name'))
       ->format(Format::using('nameLinked', ['gibbonPersonIDStudent', '', 'studentPreferredName', 'studentSurname', 'Student']));

    $table->addColumn('action', __('Action'));

    $table->addColumn('dates', __('Date'))
        ->format(function ($values) {
            return Format::dateTime($values['timestampChanged']);
        })->notSortable();

    $actions = $table->addActionColumn()
        ->addParam('gibbonPersonIDStudent')
        ->addParam('courseSelectionOfferingID')
        ->addParam('sidebar', 'false')
        ->format(function ($resource, $actions) {
            $actions->addAction('view', __('View'))
                ->setURL('/modules/Course Selection/selectionChoices.php');
        });

    echo $table->render($logs);
}
