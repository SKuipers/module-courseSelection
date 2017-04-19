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

namespace Gibbon\Modules\CourseSelection\Form;

use Gibbon\Forms\Input\Input;
use Gibbon\Forms\Traits\MultipleOptionsTrait;

/**
 * Course Selection - Form Element
 *
 * @version v14
 * @since   19th April 2017
 */
class CourseSelection extends Input
{
    use MultipleOptionsTrait;

    protected $description;
    protected $checked = array();

    public function __construct($selectionsGateway, $name, $courseSelectionBlockID, $gibbonPersonIDStudent)
    {
        $this->setName($name);
        $this->setValue('on');

        $coursesRequest = $selectionsGateway->selectCourseChoicesByBlock($courseSelectionBlockID);

        if ($coursesRequest && $coursesRequest->rowCount() > 0) {
            // Add Course Choices
            $courses = $coursesRequest->fetchAll();
            $courseChoices = array_combine(array_column($courses, 'gibbonCourseID'), array_column($courses, 'courseName'));

            $this->fromArray($courseChoices);

            // Select Choices
            $selectedChoicesRequest = $selectionsGateway->selectChoicesByBlockAndPerson($courseSelectionBlockID, $gibbonPersonIDStudent);
            $selectedChoices = ($selectedChoicesRequest->rowCount() > 0)? $selectedChoicesRequest->fetchAll() : array();
            $selectedChoiceIDList = array_column($selectedChoices, 'gibbonCourseID');

            $this->checked($selectedChoiceIDList);
        }
    }

    public function description($value = '')
    {
        $this->description = $value;
        return $this;
    }

    public function checked($value)
    {
        if ($value === 1 || $value === true) $value = 'on';
        $this->checked = (!is_array($value))? array($value) : $value;

        return $this;
    }

    protected function getIsChecked($value)
    {
        if (empty($value) || empty($this->checked)) {
            return '';
        }

        return (in_array($value, $this->checked, true))? 'checked' : '';
    }

    protected function getElement()
    {
        $output = '';

        $this->options = (!empty($this->getOptions()))? $this->getOptions() : array($this->getValue() => $this->description);
        $name = (count($this->options)>1 && stripos($this->getName(), '[]') === false)? $this->getName().'[]' : $this->getName();

        if (!empty($this->options) && is_array($this->options)) {
            foreach ($this->options as $value => $label) {
                $this->setName($name);
                $this->setAttribute('checked', $this->getIsChecked($value));
                if ($value != 'on') $this->setValue($value);


                $output .= '<input type="checkbox" '.$this->getAttributeString().'> &nbsp;';
                $output .= '<label title="'.$label.'">'.$label.'</label><br/>';
            }
        }

        return $output;
    }
}
