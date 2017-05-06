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

namespace CourseSelection\Timetable;

/**
 * Timetabling Engine: Settings
 *
 * Holds a set of pre-defined and user-defined configuration values to modify how the engine runs.
 *
 * @version v14
 * @since   4th May 2017
 */
class EngineSettings
{
    /**
     * Default Configuration Settings
     */
    protected $settings = array(
        'timetableConflictTollerance' => 0,
        'optimalWeight'               => 1.0,
        'maximumOptimalResults'       => 0,
    );

    public function __construct($settings = array())
    {
        $this->settings = array_replace($this->settings, $settings);
    }

    public function __get($key)
    {
        if (!isset($this->settings[$key])) {
            throw new \Exception('Could not access engine setting: invalid key '.$key);
        }

        return $this->settings[$key];
    }

    public function __set($key, $value)
    {
        if (!isset($this->settings[$key])) {
            throw new \Exception('Could not access engine setting: invalid key '.$key);
        }

        $this->settings[$key] = $value;
    }
}
