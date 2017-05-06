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

namespace CourseSelection\Form;

use Gibbon\Forms\Layout\Element;

/**
 * Course Grades - Form Element
 *
 * @version v14
 * @since   19th April 2017
 */
class CourseGrades extends Element
{
    protected $grades;

    public function __construct()
    {
    }

    public function fromResults($gradesRequest)
    {
        $this->grades = ($gradesRequest->rowCount() > 0)? $gradesRequest->fetchAll() : array();

        return $this;
    }

    public function fromArray($grades)
    {
        $this->grades = (is_array($grades))? $grades : array();

        return $this;
    }

    public function getOutput()
    {
        $output = '';

        $output .= implode('', array_map(function ($grade) {
            if (empty($grade['grade'])) return '';
            $output = ($grade['schoolYearStatus'] == 'Current')? '<div title="Current Year '.$grade['reportName'].'" class="courseGrades" style="background:#fff4da;">' : '<div title="Final Grade" class="courseGrades" style="background:#D4F6DC;">';
            $output .= $grade['courseNameShort'].' ('.$grade['schoolYearName'].'): ';
            $output .= intval($grade['grade']).'%';
            $output .= '</div>';
            return $output;
        }, $this->grades));

        return $output;
    }
}
