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

//This file describes the module, including database tables

//Basic variables
$name="Course Selection" ; //The name of the variable as it appears to users. Needs to be unique to installation. Also the name of the folder that holds the unit.
$description="Student Course Request and Approval System" ; //Short text description
$entryURL="index.php" ; //The landing page for the unit, used in the main menu
$type="Additional" ; //Do not change.
$category="Learn" ; //The main menu area to place the module in
$version="0.0.01" ; //Verson number
$author="Sandra Kuipers" ; //Your name
$url="https://github.com/SKuipers" ; //Your URL

// Module tables
$moduleTables[0]="CREATE TABLE `courseSelectionAccess` (
  `courseSelectionAccessID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonSchoolYearID` int(3) unsigned zerofill DEFAULT NULL,
  `gibbonRollGroupIDList` varchar(255) DEFAULT NULL,
  `dateStart` date DEFAULT NULL,
  `dateEnd` date DEFAULT NULL,
  `accessType` enum('View','Request') NOT NULL DEFAULT 'Request',
  PRIMARY KEY (`courseSelectionAccessID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[1]="CREATE TABLE `courseSelectionBlock` (
  `courseSelectionBlockID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonSchoolYearID` int(3) unsigned zerofill DEFAULT NULL,
  `gibbonDepartmentID` int(4) unsigned zerofill DEFAULT NULL,
  `name` varchar(90) NOT NULL,
  `description` varchar(255) NOT NULL,
  `minSelect` smallint(3) DEFAULT NULL,
  `maxSelect` smallint(3) DEFAULT NULL,
  `sequenceNumber` smallint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`courseSelectionBlockID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[2]="CREATE TABLE `courseSelectionBlockCourse` (
  `courseSelectionBlockID` int(10) unsigned zerofill NOT NULL,
  `gibbonCourseID` int(8) unsigned zerofill NOT NULL,
  PRIMARY KEY (`courseSelectionBlockID`,`gibbonCourseID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[3]="CREATE TABLE `courseSelectionOffering` (
  `courseSelectionOfferingID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonSchoolYearID` int(3) unsigned zerofill DEFAULT NULL,
  `gibbonYearGroupIDList` varchar(255) DEFAULT NULL,
  `name` varchar(90) DEFAULT NULL,
  `description` varchar(255) DEFAULT NULL,
  `minSelect` smallint(3) DEFAULT NULL,
  `maxSelect` smallint(3) DEFAULT NULL,
  `sequenceNumber` smallint(3) unsigned DEFAULT NULL,
  PRIMARY KEY (`courseSelectionOfferingID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[4]="CREATE TABLE `courseSelectionOfferingBlock` (
  `courseSelectionOfferingID` int(8) unsigned zerofill NOT NULL,
  `courseSelectionBlockID` int(10) unsigned zerofill NOT NULL,
  PRIMARY KEY (`courseSelectionOfferingID`,`courseSelectionBlockID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;


// courseSelectionRequisite (Ruleset/Rule)
// courseSelectionMeta
// courseSelectionRequest
// courseSelectionRequestOffering
// courseSelectionRequestLog


//gibbonSettings entries
// $gibbonSetting[0]=""; //One array entry for every gibbonSetting entry you need to create. The scope field for the setting should be your module name.
// $gibbonSetting[1]="";


//Action rows
//One array per action
$actionRows[0]["name"]="Course Selection" ; //The name of the action (appears to user in the right hand side module menu)
$actionRows[0]["precedence"]="0"; //If it is a grouped action, the precedence controls which is highest action in group
$actionRows[0]["category"]="Actions" ; //Optional: subgroups for the right hand side module menu
$actionRows[0]["description"]="" ; //Text description
$actionRows[0]["URLList"]="index.php" ; //List of pages included in this action
$actionRows[0]["entryURL"]="index.php" ; //The landing action for the page.
$actionRows[0]["entrySidebar"]="Y" ;
$actionRows[0]["menuShow"]="Y" ;
$actionRows[0]["defaultPermissionAdmin"]="Y" ; //Default permission for built in role Admin
$actionRows[0]["defaultPermissionTeacher"]="N" ; //Default permission for built in role Teacher
$actionRows[0]["defaultPermissionStudent"]="N" ; //Default permission for built in role Student
$actionRows[0]["defaultPermissionParent"]="N" ; //Default permission for built in role Parent
$actionRows[0]["defaultPermissionSupport"]="N" ; //Default permission for built in role Support
$actionRows[0]["categoryPermissionStaff"]="Y" ; //Should this action be available to user roles in the Staff category?
$actionRows[0]["categoryPermissionStudent"]="N" ; //Should this action be available to user roles in the Student category?
$actionRows[0]["categoryPermissionParent"]="N" ; //Should this action be available to user roles in the Parent category?
$actionRows[0]["categoryPermissionOther"]="N" ; //Should this action be available to user roles in the Other category?

//Hooks
// $hooks[0]="" ; //Serialised array to create hook and set options. See Hooks documentation online.
