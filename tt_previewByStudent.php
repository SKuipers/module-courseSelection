<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/tt_resultsByStudent.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Preview Timetable', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');
    $gibbonPersonIDStudent = $_POST['gibbonPersonIDStudent'] ?? $_GET['gibbonPersonID'] ?? 0;

    $form = Form::create('selectStudent', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/tt_previewByStudent.php');
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('gibbonPersonIDStudent', __('Student'));
        $row->addSelectStudent('gibbonPersonIDStudent', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonPersonIDStudent);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    // Cancel out early if there's no valid student selected
    if (empty($gibbonPersonIDStudent)) return;

    // TODO: dont hardcode this :(
    $gibbonTTID = '00000010';

    $timetableGateway = $container->get('CourseSelection\Domain\TimetableGateway');

    $timetableDays = $timetableGateway->selectTimetableDaysAndColumns($gibbonTTID)->fetchGrouped();
    $timetablePreview = $timetableGateway->selectTimetablePreviewByStudent($gibbonSchoolYearID, $gibbonPersonIDStudent)->fetchGrouped();

    // echo '<pre>';
    // print_r($timetableDays);
    // echo '</pre>';

    // echo '<pre>';
    // print_r($timetablePreview);
    // echo '</pre>';

    $table = DataTable::create('timetable');

    $timetableData = new DataSet($timetableDays);

    $timetableData->transform(function (&$ttDay) use (&$timetablePreview) {
        foreach ($ttDay as $index => $values) {
            $ttDayRow = $values['gibbonTTDayID'].'-'.$values['gibbonTTColumnRowID'];
            if (isset($timetablePreview[$ttDayRow])) {
                $preview = array_reduce($timetablePreview[$ttDayRow], function ($group, $item) {
                    $group[$item['status']][] = $item;
                    return $group;
                }, []);

                $ttDay[$index]['preview'] = $preview;
            }
        }
    });

    foreach (array_keys($timetableDays) as $index => $ttRow) {
        $table->addColumn('period', '')
            ->width('8%')
            ->format(function ($values) use ($index) {
                return $values[$index]['rowName'];
            });
    }

    foreach (current($timetableDays) as $index => $ttDay) {
        $table->addColumn($ttDay['nameShort'], str_replace('MF', '', $ttDay['nameShort']))
            ->width('23%')
            ->format(function ($values) use ($index) {
                $name = $url = $title = $class = '';

                $preview = $values[$index]['preview'] ?? [];
                if (!empty($preview['Complete'])) {
                    $class = 'bg-blue-200';
                    $complete = current($preview['Complete']);
                    $name = '<span class="font-bold text-sm">'.$complete['courseNameShort'].'.'.$complete['className'].'</span><br>';
                    $name .= '<span class="text-xs text-gray-700">'.$complete['courseName'].'</span>';
                    $url = 'index.php?q=/modules/Course Selection/tt_resultsByStudent.php&gibbonSchoolYearID='.$complete['gibbonSchoolYearID'].'&gibbonCourseClassID='.$complete['gibbonCourseClassID'];

                    // $name = $complete['courseSelectionTTResultID'];
                }

                if (!empty($preview['Flagged'])) {
                    // $class = 'bg-transparent';
                    
                    $title = 'Conflicts with '.implode(', ', array_reduce($preview['Flagged'], function ($group, $item) {
                        $group[] = $item['courseNameShort'].'.'.$item['className'];
                        return $group;
                    }, []));

                    // $flagged = current($preview['Flagged']);
                    // $name = $flagged['courseSelectionTTResultID'];
                }

                $class .= ' block px-4 h-20 border border-gray-500 border-solid flex flex-col justify-center';
                
                return Format::link($url, $name, ['title' => $title, 'class' => $class, 'style' => 'text-decoration: none;']);
            })
            ->modifyCells(function ($values, $cell) use ($index) {
                $cell->addClass('p-1 text-center leading-tight');

                $preview = $values[$index]['preview'] ?? [];
                if (!empty($preview['Flagged'])) {
                    $cell->addClass('warning');

                    $title = 'Couldn\'t Schedule '.implode(', ', array_reduce($preview['Flagged'], function ($group, $item) {
                        $group[] = $item['courseNameShort'].'.'.$item['className'];
                        return $group;
                    }, []));
                    $cell->setTitle($title);
                }
                return $cell;
            });
    }
    // $table->addColumn('name', __('Name'));
    // $table->addColumn('tutors', __('Form Tutors'))->format($formatTutorsList);
    // $table->addColumn('space', __('Room'));
    // $table->addColumn('students', __('Students'));
    // $table->addColumn('website', __('Website'))->format(Format::using('link', 'website'));

    // $actions = $table->addActionColumn()->addParam('gibbonRollGroupID');
    // $actions->addAction('view', __('View'))
    //         ->setURL('/modules/Roll Groups/rollGroups_details.php');

    echo $table->render($timetableData);
    
    // echo '<pre>';
    // print_r($studentResults);
    // echo '</pre>';

    // if (!$courses || $courses->rowCount() == 0) {
    //     echo '<div class="error">';
    //     echo __('There are no records to display.');
    //     echo '</div>';
    // } else {
    //     echo '<h3>';
    //     echo sprintf(__('Timetable Courses for %1$s'), $nextSchoolYear['name']);
    //     echo '</h3>';

    //     echo '<table class="fullWidth colorOddEven" cellspacing="0">';

    //     echo '<tr class="head">';
    //         echo '<th style="width: 14%;">';
    //             echo __('Day');
    //         echo '</th>';
    //         echo '<th style="width: 12%;">';
    //             echo __('Period');
    //         echo '</th>';
    //         echo '<th>';
    //             echo __('Course Name');
    //         echo '</th>';
    //         echo '<th>';
    //             echo __('Course Code');
    //         echo '</th>';
    //     echo '</tr>';

    //     while ($course = $courses->fetch()) {
    //         $dayShort = substr($course['className'], 0, 1);

    //         switch ($dayShort) {
    //             case 'A':   $dayName = 'Day 1'; break;
    //             case 'B':   $dayName = 'Day 2'; break;
    //             case '1':   $dayName = 'Semester 1'; break;
    //             case '2':   $dayName = 'Semester 2'; break;
    //         }

    //         echo '<tr>';
    //             echo '<td>'.$dayName.'</td>';
    //             echo '<td>'.$course['period'].'</td>';
    //             echo '<td>'.$course['courseName'].'</td>';
    //             echo '<td>'.$course['courseNameShort'].'.'.$course['className'].'</td>';
    //         echo '</tr>';
    //     }

    //     echo '</table>';
    // }
}
