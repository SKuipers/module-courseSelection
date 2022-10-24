<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Services\Format;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\System\SettingGateway;
use Gibbon\Module\CourseSelection\Domain\AccessGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/access_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $page->breadcrumbs
         ->add(__m('Course Selection Access'));

	$settingGateway = $container->get(SettingGateway::class);
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $settingGateway->getSettingByScope('Course Selection', 'activeSchoolYear');

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    $gateway = $container->get(AccessGateway::class);

    // QUERY
    $criteria = $gateway->newQueryCriteria(true)
        ->sortBy(['dateStart', 'dateEnd'])
        ->pageSize(50)
        ->fromPOST();

    $blocks = $gateway->queryAllBySchoolYear($criteria, $gibbonSchoolYearID);

    // TABLE
    $table = DataTable::createPaginated('blocks', $criteria);
    $table->setTitle(__('View'));

    $table->addHeaderAction('add', __('Add'))
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->setURL('/modules/Course Selection/access_manage_addEdit.php')
        ->displayLabel();

    $table->addColumn('schoolYearName', __('School Year'));

    $table->addColumn('dates', __('Date'))
        ->format(function ($values) {
            return Format::date($values['dateStart'])." - ".Format::date($values['dateEnd']);
        })->notSortable();

    $table->addColumn('roleGroupNames', __('Roles'));
    $table->addColumn('accessType', __('Type'));

    $actions = $table->addActionColumn()
        ->addParam('courseSelectionAccessID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->format(function ($resource, $actions) {
            $actions->addAction('edit', __('Edit'))
                ->setURL('/modules/Course Selection/access_manage_addEdit.php');
            $actions->addAction('delete', __('Delete'))
                ->setURL('/modules/Course Selection/access_manage_delete.php');
        });

    echo $table->render($blocks);
}
