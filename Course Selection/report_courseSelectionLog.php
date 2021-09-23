<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use CourseSelection\SchoolYearNavigation;
use CourseSelection\Domain\SelectionsGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/report_courseSelectionLog.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $session->get('absoluteURL') . "'>" . __($guid, "Home") . "</a> > <a href='" . $session->get('absoluteURL') . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __($guid, getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __($guid, 'Activity Log', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    $selectionsGateway = $container->get('CourseSelection\Domain\SelectionsGateway');

    $logs = $selectionsGateway->selectAllLogsBySchoolYear($gibbonSchoolYearID, 1, 100);

    if ($logs->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {
        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
            echo '<th>';
                echo __('Course Offering');
            echo '</th>';
            echo '<th>';
                echo __('Student');
            echo '</th>';
            echo '<th>';
                echo __('Type');
            echo '</th>';
            echo '<th>';
                echo __('Date');
            echo '</th>';
            echo '<th style="width: 80px;">';
                echo __('Actions');
            echo '</th>';
        echo '</tr>';

        while ($log = $logs->fetch()) {
            echo '<tr>';
                echo '<td>'.$log['offeringName'].'</td>';
                echo '<td>';
                    echo '<a href="'.$session->get('absoluteURL').'/index.php?q=/modules/Students/student_view_details.php&gibbonPersonID='.$log['gibbonPersonIDStudent'].'&allStudents=on" target="_blank">';
                    echo formatName('', $log['studentPreferredName'], $log['studentSurname'], 'Student', true);
                    echo '</a>';
                echo '</td>';
                echo '<td>'.$log['action'].'</td>';
                echo '<td>';
                    echo date('M j, Y \a\t g:i a', strtotime($log['timestampChanged']));
                echo '</td>';
                echo '<td>';
                    echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/".$session->get('module')."/selectionChoices.php&sidebar=false&gibbonPersonIDStudent=".$log['gibbonPersonIDStudent']."&courseSelectionOfferingID=".$log['courseSelectionOfferingID']."'><img title='".__('View')."' src='./themes/".$session->get('gibbonThemeName')."/img/plus.png'/></a>";

                echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}
