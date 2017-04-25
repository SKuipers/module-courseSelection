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

use Gibbon\Forms\FormFactory;
use Gibbon\Modules\CourseSelection\Domain\SelectionsGateway;

/**
 * CourseSelectionFormFactory
 *
 * Handles Form object creation for the Course Selection process
 *
 * @version v14
 * @since   19th April 2017
 */
class CourseSelectionFormFactory extends FormFactory
{

    public function __construct()
    {
    }

    public static function create()
    {
        return new CourseSelectionFormFactory();
    }

    public function createCourseSelection($name)
    {
        return new CourseSelection($name);
    }

    public function createCourseGrades()
    {
        return new CourseGrades();
    }

    public function createCourseProgressByBlock($blockData)
    {
        return new CourseProgressByBlock($blockData);
    }

    public function createCourseProgressByOffering($offeringData)
    {
        return new CourseProgressByOffering($offeringData);
    }
}
