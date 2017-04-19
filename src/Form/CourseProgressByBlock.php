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

use Gibbon\Forms\Layout\Element;

/**
 * Course Grades - Form Element
 *
 * @version v14
 * @since   19th April 2017
 */
class CourseProgressByBlock extends Element
{
    protected $block;

    public function __construct($blockData)
    {
        $this->block = $blockData;
    }

    public function getOutput()
    {
        $output = '';



        $this->setAttribute('data-block', $this->block['courseSelectionBlockID']);
        $this->setAttribute('data-min', $this->block['minSelect']);
        $this->setAttribute('data-max', $this->block['maxSelect']);

        $output .= '<div class="courseProgressByBlock" '.$this->getAttributeString().'>';

        $output .= '<div class="indicator">';
            $output .= '<img class="valid" title="'.__('Complete').'" src="./themes/Default/img/iconTick.png" style="display:none;">';
            $output .= '<img class="invalid" title="'.__('Incomplete').'" src="./themes/Default/img/iconCross.png" style="display:none;">';
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }
}
