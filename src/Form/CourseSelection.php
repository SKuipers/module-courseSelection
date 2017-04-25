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

    public function __construct($name)
    {
        $this->setName($name);
        $this->addClass('courseChoice');
    }

    public function fromResults($coursesRequest)
    {
        $this->courses = ($coursesRequest->rowCount() > 0)? $coursesRequest->fetchAll() : array();

        return $this;
    }

    public function fromArray($courses)
    {
        $this->courses = (is_array($courses))? $courses : array();

        return $this;
    }

    public function selected($selected = array())
    {
        $this->selectedChoices = $selected;

        return $this;
    }

    public function description($value = '')
    {
        $this->description = $value;
        return $this;
    }

    public function setBlockID($value)
    {
        $this->blockID = $value;

        $this->addClass('courseBlock'.$this->blockID);
        $this->setAttribute('data-block', $this->blockID);

        return $this;
    }

    public function getReadOnly()
    {
        return $this->getAttribute('readonly');
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

                    $locked = ($status == 'Required' || $status == 'Approved' || $status == 'Locked')? 'true' : 'false';

                    $this->setAttribute('disabled', $locked == 'true');
                    $this->setAttribute('data-locked', $locked);

                    $output .= '<input type="checkbox" '.$this->getAttributeString().'> &nbsp;';
                }

                $output .= '<label for="'.$this->getID().'" title="'.$course['courseNameShort'].'">'.$label.'</label>';

                if ($this->selectStatus == true) {
                    $output .= '<select name="courseStatus['.$this->blockID.']['.$courseID.']" class="courseStatusSelect pullRight">';
                    $output .= '<option value="" '.(($status == 'Removed' || $status == '')? 'selected' : '').'> </option>';
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
