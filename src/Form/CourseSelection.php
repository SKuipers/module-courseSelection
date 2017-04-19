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

/**
 * Course Selection - Form Element
 *
 * @version v14
 * @since   19th April 2017
 */
class CourseSelection extends Input
{
    protected $description;
    protected $checked = array();
    protected $readOnly;

    protected $courses;
    protected $selectedChoices;

    public function __construct($selectionsGateway, $name, $courseSelectionBlockID, $gibbonPersonIDStudent)
    {
        $this->setName($name);
        $this->addClass('courseChoice');
        $this->addClass('courseBlock'.$courseSelectionBlockID);
        $this->setAttribute('data-block', $courseSelectionBlockID);

        $coursesRequest = $selectionsGateway->selectCoursesByBlock($courseSelectionBlockID);

        if ($coursesRequest && $coursesRequest->rowCount() > 0) {
            $this->courses = $coursesRequest->fetchAll();

            $selectedChoicesRequest = $selectionsGateway->selectChoicesByBlockAndPerson($courseSelectionBlockID, $gibbonPersonIDStudent);
            $this->selectedChoices = ($selectedChoicesRequest->rowCount() > 0)? $selectedChoicesRequest->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_UNIQUE) : array();
        }
    }

    public function description($value = '')
    {
        $this->description = $value;
        return $this;
    }

    public function setReadOnly($value)
    {
        $this->setAttribute('readonly', $value);

        return $this;
    }

    public function getReadOnly()
    {
        return $this->getAttribute('readonly');
    }

    public function checked($value)
    {
        if ($value === 1 || $value === true) $value = 'on';
        $this->checked = (!is_array($value))? array($value) : $value;

        return $this;
    }

    protected function getChoiceStatus($value)
    {
        return (isset($this->selectedChoices[$value]['status']))? $this->selectedChoices[$value]['status'] : '';
    }

    protected function getIsChecked($value)
    {
        if (empty($value) || empty($this->selectedChoices)) {
            return '';
        }

        $status = $this->getChoiceStatus($value);

        return ($status == 'Locked' || $status == 'Approved' || $status == 'Requested')? 'checked' : '';
    }

    protected function getElement()
    {
        $output = '';

        $name = (count($this->courses)>1 && stripos($this->getName(), '[]') === false)? $this->getName().'[]' : $this->getName();

        if (!empty($this->courses) && is_array($this->courses)) {
            foreach ($this->courses as $course) {
                $value = $course['gibbonCourseID'];
                $label = $course['courseName'];

                $this->setName($name);
                $this->setAttribute('checked', $this->getIsChecked($value));
                $this->setValue($value);

                $output .= '<div class="courseChoiceContainer" data-status="'.$this->getChoiceStatus($value).'">';

                if ($this->getReadOnly() && $this->getIsChecked($value) == false) continue;

                if ($this->getReadOnly() == false) {

                    $locked = ($this->getChoiceStatus($value) == 'Locked' || $this->getChoiceStatus($value) == 'Approved');

                    $this->setAttribute('disabled', $locked);
                    $this->setAttribute('data-locked', $locked);


                    $output .= '<input type="checkbox" '.$this->getAttributeString().'> &nbsp;';
                }

                $output .= '<label title="'.$label.'">'.$label.'</label>';

                if ($this->getChoiceStatus($value) == 'Locked') {
                    $output .= '<span class="courseTag small emphasis">&nbsp; '.__('Required').'</span>';
                } else if ($this->getChoiceStatus($value) == 'Approved') {
                    $output .= '<span class="courseTag small emphasis">&nbsp; '.__('Approved').'</span>';
                } else if ($this->getChoiceStatus($value) == 'Recommended') {
                    $output .= '<span class="courseTag small emphasis">&nbsp; '.__('Recommended').'</span>';
                }

                $output .= '</div>';
            }
        }

        return $output;
    }
}
