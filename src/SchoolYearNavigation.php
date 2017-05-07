<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
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
