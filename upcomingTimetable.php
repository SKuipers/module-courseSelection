<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use CourseSelection\SchoolYearNavigation;
use Gibbon\Tables\DataTable;
use Gibbon\Domain\DataSet;
use Gibbon\Services\Format;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/upcomingTimetable.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $_SESSION[$guid]["absoluteURL"] . "'>" . __($guid, "Home") . "</a> > <a href='" . $_SESSION[$guid]["absoluteURL"] . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Upcoming Timetable', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $highestGroupedAction = getHighestGroupedAction($guid, '/modules/Course Selection/upcomingTimetable.php', $connection2);

    if ($highestGroupedAction == 'Upcoming Timetable_all') {
        $gibbonPersonIDStudent = isset($_POST['gibbonPersonIDStudent'])? $_POST['gibbonPersonIDStudent'] : 0;

        $form = Form::create('selectStudent', $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/upcomingTimetable.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('gibbonPersonIDStudent', __('Student'));
            $row->addSelectStudent('gibbonPersonIDStudent', $_SESSION[$guid]['gibbonSchoolYearID'])->selected($gibbonPersonIDStudent);

        $row = $form->addRow();
            $row->addFooter();
            $row->addSubmit();

        echo $form->getOutput();
    } else if ($highestGroupedAction == 'Upcoming Timetable_my') {
        $gibbonPersonIDStudent = $_SESSION[$guid]['gibbonPersonID'];
    }

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    $nextSchoolYear = $navigation->selectNextSchoolYearByID($_SESSION[$guid]['gibbonSchoolYearID']);

    // Cancel out early if there's no valid student selected
    if (empty($nextSchoolYear) || empty($gibbonPersonIDStudent)) return;

    // TODO: dont hardcode this :(
    $gibbonTTID = '00000010';

    $timetableGateway = $container->get('CourseSelection\Domain\TimetableGateway');

    $timetableDays = $timetableGateway->selectTimetableDaysAndColumns($gibbonTTID)->fetchGrouped();
    $courses = $timetableGateway->selectEnroledCoursesBySchoolYearAndStudent($nextSchoolYear['gibbonSchoolYearID'], $gibbonPersonIDStudent)->fetchGrouped();


    $table = DataTable::create('timetable');

    $timetableData = new DataSet($timetableDays);

    $timetableData->transform(function (&$ttDay) use (&$courses) {
        foreach ($ttDay as $index => $values) {
            $ttDayRow = $values['gibbonTTDayID'].'-'.$values['gibbonTTColumnRowID'];
            if (isset($courses[$ttDayRow])) {
                $ttDay[$index]['courseClass'] = current($courses[$ttDayRow]);
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

    $canAccessTT = isActionAccessible($guid, $connection2, '/modules/Timetable Admin/courseEnrolment_manage_class_edit.php');
    foreach (current($timetableDays) as $index => $ttDay) {
        $table->addColumn($ttDay['nameShort'], str_replace('MF', '', $ttDay['nameShort']))
            ->width('23%')
            ->format(function ($values) use ($index, $canAccessTT) {
                $name = $url = $title = $class = '';

                $courseClass = $values[$index]['courseClass'] ?? [];
                if (!empty($courseClass)) {
                    $class = 'bg-blue-200';
                    $name = '<span class="font-bold text-sm">'.$courseClass['courseNameShort'].'.'.$courseClass['className'].'</span><br>';
                    $name .= '<span class="text-xs text-gray-700">'.$courseClass['courseName'].'</span>';
                    $url = $canAccessTT
                        ? './index.php?q=/modules/Timetable Admin/courseEnrolment_manage_class_edit.php&gibbonSchoolYearID='.$courseClass['gibbonSchoolYearID'].'&gibbonCourseID='.$courseClass['gibbonCourseID'].'&gibbonCourseClassID='.$courseClass['gibbonCourseClassID']
                        : "#";
                }

                $class .= ' block px-4 h-20 border border-gray-500 border-solid flex flex-col justify-center';
                
                return Format::link($url, $name, ['title' => $title, 'class' => $class, 'style' => 'text-decoration: none;']);
            })
            ->modifyCells(function ($values, $cell) use ($index) {
                $cell->addClass('p-1 text-center leading-tight');
                return $cell;
            });
    }

    echo $table->render(new DataSet(array_values($timetableData->toArray())));
}
