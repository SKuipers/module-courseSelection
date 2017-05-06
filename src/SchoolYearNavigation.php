<?php
/*
Gibbon, Flexible & Open School System
Copyright (C) 2010, Ross Parker

This program is free software: you can redistribute it and/or modify
it under the terms of the GNU General Public License as published by
the Free Software Foundation, either version 3 of the License, or
(at your option) any later version.

This program is distributed in the hope that it will be useful,
but WITHOUT ANY WARRANTY; without even the implied warranty of
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

namespace CourseSelection;

/**
 * School Year Navigation
 *
 * @version v14
 * @since   26th April 2017
 */
class SchoolYearNavigation
{
    protected $pdo;
    protected $session;

    protected $schoolYear;

    public function __construct(\Gibbon\sqlConnection $pdo, \Gibbon\session $session)
    {
        $this->pdo = $pdo;
        $this->session = $session;
    }

    public function getYearPicker($gibbonSchoolYearID)
    {
        $this->schoolYear = $this->selectSchoolYearByID($gibbonSchoolYearID);
        if (empty($this->schoolYear)) {
            return '';
        }

        $output = '';

        $output .= '<h2>';
        $output .= $this->schoolYear['name'];
        $output .= '</h2>';

        $output .= '<div class="linkTop">';
            //Print year picker
            $previousYear = $this->selectPreviousSchoolYearByID($gibbonSchoolYearID);
            $nextYear = $this->selectNextSchoolYearByID($gibbonSchoolYearID);
            if (!empty($previousYear)) {
                $output .= '<a href="'.$this->session->get('absoluteURL').'/index.php?q='.$this->session->get('address').'&gibbonSchoolYearID='.$previousYear['gibbonSchoolYearID'].'">'.__('Previous Year').'</a> ';
            } else {
                $output .= __('Previous Year').' ';
            }
            $output .=  ' | ';
            if (!empty($nextYear)) {
                $output .=  '<a href="'.$this->session->get('absoluteURL').'/index.php?q='.$this->session->get('address').'&gibbonSchoolYearID='.$nextYear['gibbonSchoolYearID'].'">'.__('Next Year').'</a> ';
            } else {
                $output .=  __('Next Year').' ';
            }
        $output .=  '</div>';

        return $output;
    }

    public function getSchoolYearName()
    {
        return (isset($this->schoolYear['name']))? $this->schoolYear['name'] : '';
    }

    public function selectSchoolYearByID($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID";
        $result = $this->pdo->executeQuery($data, $sql);

        return ($result && $result->rowCount() > 0)? $result->fetch() : null;
    }

    public function selectNextSchoolYearByID($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear WHERE sequenceNumber=(SELECT MIN(sequenceNumber) FROM gibbonSchoolYear WHERE sequenceNumber > (SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID))";

        $result = $this->pdo->executeQuery($data, $sql);

        return ($result && $result->rowCount() > 0)? $result->fetch() : null;
    }

    public function selectPreviousSchoolYearByID($gibbonSchoolYearID)
    {
        $data = array('gibbonSchoolYearID' => $gibbonSchoolYearID);
        $sql = "SELECT gibbonSchoolYearID, name FROM gibbonSchoolYear WHERE sequenceNumber=(SELECT MAX(sequenceNumber) FROM gibbonSchoolYear WHERE sequenceNumber < (SELECT sequenceNumber FROM gibbonSchoolYear WHERE gibbonSchoolYearID=:gibbonSchoolYearID))";
        $result = $this->pdo->executeQuery($data, $sql);

        return ($result && $result->rowCount() > 0)? $result->fetch() : null;
    }
}
