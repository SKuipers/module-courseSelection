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

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/report_requestsByCourse.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $page->breadcrumbs
    	->add(__m('Total Requests by Course'));

	$settingGateway = $container->get(SettingGateway::class);
    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? $settingGateway->getSettingByScope('Course Selection', 'activeSchoolYear');
    $sort = $_GET['sort'] ?? 'surname';
    $min = $_GET['min'] ?? $settingGateway->getSettingByScope('Course Selection', 'classEnrolmentMinimum');
    $max = $_GET['max'] ?? $settingGateway->getSettingByScope('Course Selection', 'classEnrolmentMaximum');

    $page->navigator->addSchoolYearNavigation($gibbonSchoolYearID);

    echo '<h2>';
    echo __('Filter');
    echo '</h2>';

    $form = Form::create('action', $session->get('absoluteURL').'/index.php', 'get');

    $form->setClass('noIntBorder fullWidth');
    $form->addHiddenValue('q', '/modules/Course Selection/report_requestsByCourse.php');

    $row = $form->addRow();
        $row->addLabel('sort', __('Sort By'));
        $row->addSelect('sort')->fromArray(array('nameShort' => __('Course Code'), 'name' => __('Course Name'), 'order' => __('Report Order'), 'count' => __('Requests')))->selected($sort);

    $row = $form->addRow();
        $row->addLabel('highlight', __('Target Range'))->description(__('Highlight courses outside this range.'));
        $column = $row->addColumn()->addClass('inline right');
            $column->addTextField('min')->setClass('shortWidth')->placeholder(__('Minimum'))->setValue($min);
            $column->addTextField('max')->setClass('shortWidth')->placeholder(__('Maximum'))->setValue($max);

    $row = $form->addRow();
        $row->addSubmit('Go');

    echo $form->getOutput();

    $selectionsGateway = $container->get(SelectionsGateway::class);

    // QUERY
    $criteria = $selectionsGateway->newQueryCriteria(true)
        ->pageSize(50)
        ->fromPOST();
    if ($sort == 'count') {
        $criteria->sortBy(['count'], 'DESC')
            ->sortBy(['gibbonCourse.nameShort', 'gibbonCourse.name']);
    } else if ($sort == 'order') {
        $criteria->sortBy(['gibbonCourse.orderBy', 'gibbonCourse.nameShort', 'gibbonCourse.name']);
    } else if ($sort == 'name') {
        $criteria->sortBy(['gibbonCourse.name', 'gibbonCourse.nameShort']);
    } else {
        $criteria->sortBy(['gibbonCourse.nameShort', 'gibbonCourse.name']);
    }

    $courses = $selectionsGateway->queryChoiceCountsBySchoolYear($criteria, $gibbonSchoolYearID);

    // TABLE
    $table = DataTable::createPaginated('courses', $criteria);
    $table->setTitle(__('Course Requests'));

    $table->modifyRows(function ($course, $row) use ($min, $max) {
        return (!empty($min) && ($course['count'] < $min OR $course['count'] > $max)) ? $row->addClass('warning') : $row;
    });

    $table->addColumn('courseNameShort', __('Course'));
    $table->addColumn('courseName', __('Name'));
    $table->addColumn('count', __('Requests'));

    $actions = $table->addActionColumn()
        ->addParam('gibbonCourseID')
        ->addParam('gibbonSchoolYearID', $gibbonSchoolYearID)
        ->addParam('sidebar', 'false')
        ->format(function ($resource, $actions) {
            $actions->addAction('view', __('View'))
                ->setURL('/modules/Course Selection/approval_byCourse.php');
        });

    echo $table->render($courses);
}
