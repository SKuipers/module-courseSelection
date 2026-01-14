<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

namespace Gibbon\Module\CourseSelection\Form;

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

        $class = 'courseProgressByBlock';
        if (isset($this->block['minSelect']) && $this->block['minSelect'] == "0") {
            $class .= ' complete';
        }

        $output .= '<div class="'.$class.'" '.$this->getAttributeString().'>';

        $output .= '<div class="indicator">';
            $output .= '<img class="valid" title="'.__('Complete').'" src="./themes/Default/img/iconTick.png" style="display:none;">';
            $output .= '<img class="invalid" title="'.__('Incomplete').'" src="./themes/Default/img/iconCross.png" style="display:none;">';
        $output .= '</div>';

        $output .= '</div>';

        return $output;
    }
}
