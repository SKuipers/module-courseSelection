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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program.  If not, see <http://www.gnu.org/licenses/>.
*/

/**
 * From JSfiddle http://jsfiddle.net/9uvfP/ shared by Andrew Chiu
 */
function cartesian($args) {
    $r = array();

    $helper = function($arr, $i, $max) use (&$helper, &$args, &$r) {
        for ($j=0; $j<count($args[$i]); $j++) {
            $a = array_slice($arr, 0); // clone arr
            array_push($a, $args[$i][$j]);
            if ($i==$max) {
                array_push($r, $a);
            } else {
                $helper($a, $i+1, $max);
            }
        }
    };

    $helper([], 0, count($args)-1);

    return $r;
};

