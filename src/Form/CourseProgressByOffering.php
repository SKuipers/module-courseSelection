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
class CourseProgressByOffering extends Element
{
    protected $offering;

    protected $messages;

    public function __construct($offeringData)
    {
        $this->setClass('right');
        $this->offering = $offeringData;
    }

    public function setMessage($key, $value = '')
    {
        $this->messages[$key] = $value;

        return $this;
    }

    public function getMessage($key)
    {
        return (isset($this->messages[$key]))? $this->messages[$key] : '';
    }

    public function getOutput()
    {
        $output = '';

        $this->setAttribute('title', 'Min '.$this->offering['minSelect'].' Max '.$this->offering['maxSelect']);
        $this->setAttribute('data-min', $this->offering['minSelect']);
        $this->setAttribute('data-max', $this->offering['maxSelect']);

        $output .= '<div class="courseProgressByOffering" '.$this->getAttributeString().'>';

            $output .= '<div class="progressBar" style="width:100%"><div class="complete" style="width:0%;"></div></div>';

            $output .= '<div class="valid success hidden">';
                $output .= $this->getMessage('complete');
            $output .= '</div>';

            $output .= '<div class="invalid warning hidden">';
                $output .= $this->getMessage('invalid');
            $output .= '</div>';

            $output .= '<div class="continue information hidden">';
                $output .= $this->getMessage('continue');
            $output .= '</div>';

        $output .= '</div>';

        return $output;
    }
}
