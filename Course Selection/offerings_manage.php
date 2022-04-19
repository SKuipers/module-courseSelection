<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use CourseSelection\SchoolYearNavigation;
use CourseSelection\Domain\OfferingsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $page->breadcrumbs
    	->add(__m('Manage Course Offerings'));

	$settingGateway = $container->get(SettingGateway::class);
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $settingGateway->getSettingByScope('Course Selection', 'activeSchoolYear');

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    echo "<p class='text-right mb-2 text-xs'>";
        $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
        $nextYear = $navigation->getNextYear($gibbonSchoolYearID);
        if (!empty($nextYear)) {
            echo "<a href='" . $session->get('absoluteURL') . '/modules/'.$session->get('module')."/offerings_manage_copyProcess.php?gibbonSchoolYearID=$gibbonSchoolYearID&gibbonSchoolYearIDNext=".$nextYear['gibbonSchoolYearID']."' onclick='return confirm(\"Are you sure you want to do this? All course offerings, but not their requests, will be copied.\")'>" . __('Copy All To Next Year') . "<img style='margin-left: 5px' title='" . __('Copy All To Next Year') . "' src='./themes/" . $session->get('gibbonThemeName') . "/img/copy.png'/></a>";
        }
    echo '</p>';

    $gateway = $container->get('CourseSelection\Domain\OfferingsGateway');

    // QUERY
    $criteria = $gateway->newQueryCriteria(true)
        ->sortBy(['sequenceNumber'])
        ->pageSize(50)
        ->fromPOST();

    $blocks = $gateway->queryAllBySchoolYear($criteria, $gibbonSchoolYearID);

    // TABLE
    $table = DataTable::createPaginated('blocks', $criteria);
    $table->setTitle(__('View'));

    $table->addHeaderAction('add', __('Add'))
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->setURL('/modules/Course Selection/offerings_manage_addEdit.php')
        ->displayLabel();

    $table->addColumn('schoolYearName', __('School Year'));

    $table->addColumn('name', __('Name'));

    $table->addColumn('yearGroupNames', __('Year Groups'));

    $actions = $table->addActionColumn()
        ->addParam('courseSelectionOfferingID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->format(function ($resource, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Course Selection/offerings_manage_addEdit.php');
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Course Selection/offerings_manage_delete.php');
        });

    echo $table->render($blocks);
}
