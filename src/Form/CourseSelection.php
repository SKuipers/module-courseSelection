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
    protected $selectStatus = false;

    protected $blockID;
    protected $courses;
    protected $selectedChoices;

    public function __construct($selectionsGateway, $name, $courseSelectionBlockID, $gibbonPersonIDStudent)
    {
        $this->blockID = $courseSelectionBlockID;

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

    public function canSelectStatus($value = true)
    {
        $this->selectStatus = $value;

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

        return ($status == 'Required' || $status == 'Approved' || $status == 'Requested' || $status == 'Selected')? 'checked' : '';
    }

    protected function getElement()
    {
        $output = '';

        if (!empty($this->courses) && is_array($this->courses)) {
            foreach ($this->courses as $course) {
                $courseID = $course['gibbonCourseID'];
                $label = $course['courseName'];
                $status = $this->getChoiceStatus($courseID);

                $name = 'courseSelection['.$this->blockID.']['.$courseID.']';

                $this->setName($name);
                $this->setID($this->blockID.'-'.$courseID);
                $this->setAttribute('checked', $this->getIsChecked($courseID));
                $this->setAttribute('data-course', $courseID);

                $this->setValue('Selected');

                if ($this->getReadOnly() && $this->getIsChecked($courseID) == false) continue;

                $output .= '<div class="courseChoiceContainer" data-status="'.$status.'">';

                if ($this->getReadOnly() == false) {

                    $locked = ($status == 'Required' || $status == 'Approved')? 'true' : 'false';

                    $this->setAttribute('disabled', $locked == 'true');
                    $this->setAttribute('data-locked', $locked);

                    $output .= '<input type="checkbox" '.$this->getAttributeString().'> &nbsp;';
                }

                $output .= '<label for="'.$this->getID().'" title="'.$course['courseNameShort'].'">'.$label.'</label>';

                if ($this->selectStatus == true) {
                    $output .= '<select name="courseStatus['.$this->blockID.']['.$courseID.']" class="courseStatusSelect pullRight">';
                    $output .= '<option value="" '.($status == ''? 'selected' : '').'> </option>';
                    $output .= '<option value="Required" '.($status == 'Required'? 'selected' : '').'>'.__('Required').'</option>';
                    $output .= '<option value="Approved" '.($status == 'Approved'? 'selected' : '').'>'.__('Approved').'</option>';
                    $output .= '<option value="Recommended" '.($status == 'Recommended'? 'selected' : '').'>'.__('Recommended').'</option>';
                    $output .= '<option value="Requested" '.($status == 'Requested' ? 'selected' : '').'>'.__('Requested').'</option>';

                    $output .= '</select>';
                } else {
                    if ($status == 'Required' || $status == 'Approved' || $status == 'Recommended') {
                        $output .= '<span class="courseTag pullRight small emphasis">&nbsp; '.__($status).'</span>';
                    }
                }


                $output .= '</div>';
            }
        }

        return $output;
    }
}
