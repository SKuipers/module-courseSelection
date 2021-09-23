<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use CourseSelection\SchoolYearNavigation;
use CourseSelection\Domain\AccessGateway;

// Module Bootstrap
require 'module.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/access_manage.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='" . $session->get('absoluteURL') . "'>" . __("Home") . "</a> > <a href='" . $session->get('absoluteURL') . "/index.php?q=/modules/" . getModuleName($_GET["q"]) . "/" . getModuleEntry($_GET["q"], $connection2, $guid) . "'>" . __(getModuleName($_GET["q"])) . "</a> > </div><div class='trailEnd'>" . __('Course Selection Access', 'Course Selection') . "</div>" ;
    echo "</div>" ;

    if (isset($_GET['return'])) {
        returnProcess($guid, $_GET['return'], null, null);
    }

    $gibbonSchoolYearID = $_REQUEST['gibbonSchoolYearID'] ?? getSettingByScope($connection2, 'Course Selection', 'activeSchoolYear');

    $navigation = new SchoolYearNavigation($pdo, $gibbon->session);
    echo $navigation->getYearPicker($gibbonSchoolYearID);

    echo "<div class='linkTop'>";
    echo "<a href='".$session->get('absoluteURL').'/index.php?q=/modules/'.$session->get('module')."/access_manage_addEdit.php&gibbonSchoolYearID=".$gibbonSchoolYearID."'>".__('Add')."<img style='margin-left: 5px' title='".__('Add')."' src='./themes/".$session->get('gibbonThemeName')."/img/page_new.png'/></a>";
    echo '</div>';

    $gateway = $container->get('CourseSelection\Domain\AccessGateway');
    $accessList = $gateway->selectAllBySchoolYear($gibbonSchoolYearID);

    if ($accessList->rowCount() == 0) {
        echo '<div class="error">';
        echo __("There are no records to display.") ;
        echo '</div>';
    } else {
        echo '<table class="fullWidth colorOddEven" cellspacing="0">';

        echo '<tr class="head">';
            echo '<th>';
                echo __('School Year');
            echo '</th>';
            echo '<th>';
                echo __('Dates');
            echo '</th>';
            echo '<th>';
                echo __('Roles');
            echo '</th>';
            echo '<th>';
                echo __('Type');
            echo '</th>';
            echo '<th style="width: 80px;">';
                echo __('Actions');
            echo '</th>';
        echo '</tr>';

        while ($access = $accessList->fetch()) {
            echo '<tr>';
                echo '<td>'.$access['gibbonSchoolYearName'].'</td>';
                echo '<td>';
                    echo date('M j', strtotime($access['dateStart'])).' - '.date('M j, Y', strtotime($access['dateEnd']));
                echo '</td>';
                echo '<td>'.$access['roleGroupNames'].'</td>';
                echo '<td>'.$access['accessType'].'</td>';
                echo '<td>';
                    echo "<a href='".$session->get('absoluteURL')."/index.php?q=/modules/".$session->get('module')."/access_manage_addEdit.php&courseSelectionAccessID=".$access['courseSelectionAccessID']."'><img title='".__('Edit')."' src='./themes/".$session->get('gibbonThemeName')."/img/config.png'/></a> &nbsp;";

                    echo "<a class='thickbox' href='".$session->get('absoluteURL')."/fullscreen.php?q=/modules/".$session->get('module')."/access_manage_delete.php&courseSelectionAccessID=".$access['courseSelectionAccessID']."&width=650&height=200'><img title='".__('Delete')."' src='./themes/".$session->get('gibbonThemeName')."/img/garbage.png'/></a>";
                echo '</td>';
            echo '</tr>';
        }

        echo '</table>';
    }
}
