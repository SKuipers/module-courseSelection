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
$name="Course Selection" ;
$description="Student Course Request and Approval System" ;
$entryURL="selection.php" ;
$type="Additional" ;
$category="Learn" ;
$version="0.0.01" ;
$author="Sandra Kuipers" ;
$url="https://github.com/SKuipers" ;

// Module tables
$moduleTables[]="CREATE TABLE `courseSelectionAccess` (
  `courseSelectionAccessID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonSchoolYearID` int(3) unsigned zerofill DEFAULT NULL,
  `gibbonRoleIDList` varchar(255) DEFAULT NULL,
  `dateStart` date DEFAULT NULL,
  `dateEnd` date DEFAULT NULL,
  `accessType` enum('View','Request','Select') NOT NULL DEFAULT 'Request',
  PRIMARY KEY (`courseSelectionAccessID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[]="CREATE TABLE `courseSelectionBlock` (
  `courseSelectionBlockID` int(10) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `gibbonSchoolYearID` int(3) unsigned zerofill DEFAULT NULL,
  `gibbonDepartmentID` int(4) unsigned zerofill DEFAULT NULL,
  `name` varchar(90) NOT NULL,
  `description` varchar(255) NOT NULL,
  `minSelect` smallint(3) DEFAULT NULL,
  `maxSelect` smallint(3) DEFAULT NULL,
  PRIMARY KEY (`courseSelectionBlockID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[]="CREATE TABLE `courseSelectionBlockCourse` (
  `courseSelectionBlockID` int(10) unsigned zerofill NOT NULL,
  `gibbonCourseID` int(8) unsigned zerofill NOT NULL,
  PRIMARY KEY (`courseSelectionBlockID`,`gibbonCourseID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[]="CREATE TABLE `courseSelectionOffering` (
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

$moduleTables[]="CREATE TABLE `courseSelectionOfferingRestriction` (
  `courseSelectionOfferingRestrictionID` int(8) unsigned zerofill NOT NULL AUTO_INCREMENT,
  `courseSelectionOfferingID` int(8) unsigned zerofill NOT NULL,
  `gibbonSchoolYearID` int(3) unsigned zerofill DEFAULT NULL,
  `gibbonYearGroupID` int(3) unsigned zerofill DEFAULT NULL,
  PRIMARY KEY (`courseSelectionOfferingRestrictionID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;

$moduleTables[]="CREATE TABLE `courseSelectionOfferingBlock` (
  `courseSelectionOfferingID` int(8) unsigned zerofill NOT NULL,
  `courseSelectionBlockID` int(10) unsigned zerofill NOT NULL,
  `minSelect` smallint(3) DEFAULT NULL,
  `maxSelect` smallint(3) DEFAULT NULL,
  PRIMARY KEY (`courseSelectionOfferingID`,`courseSelectionBlockID`)
) ENGINE=MyISAM DEFAULT CHARSET=utf8;" ;


// courseSelectionRequisite (Ruleset/Rule)
// courseSelectionMeta
// courseSelectionRequest
// courseSelectionRequestOffering
// courseSelectionRequestLog


//gibbonSettings entries
$gibbonSetting[]="INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'requireApproval', 'Require Course Approval', 'Require a staff member to approve course selections.', 'Y');";
$gibbonSetting[]="INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'infoTextOfferings', 'Course Offerings Introduction', 'Information to display with the course offerings.', '');";
$gibbonSetting[]="INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'infoTextSelectionBefore', 'Course Selection Introduction', 'Information to display before the course selections form.', '');";
$gibbonSetting[]="INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'infoTextSelectionAfter', 'Course Selection Postscript', 'Information to display after the course selections form.', '');";


//Action rows
//One array per action
$actionRows[] = array(
    'name'                      => 'Course Selection Access',
    'precedence'                => '0',
    'category'                  => 'Administration',
    'description'               => '',
    'URLList'                   => 'access_manage.php,access_manage_addEdit.php,access_manage_delete.php',
    'entryURL'                  => 'access_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Manage Course Offerings',
    'precedence'                => '0',
    'category'                  => 'Administration',
    'description'               => '',
    'URLList'                   => 'offerings_manage.php,offerings_manage_addEdit.php,offerings_manage_delete.php',
    'entryURL'                  => 'offerings_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Manage Course Blocks',
    'precedence'                => '0',
    'category'                  => 'Administration',
    'description'               => '',
    'URLList'                   => 'blocks_manage.php,blocks_manage_addEdit.php,blocks_manage_delete.php',
    'entryURL'                  => 'blocks_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Manage Prerequisites',
    'precedence'                => '0',
    'category'                  => 'Administration',
    'description'               => '',
    'URLList'                   => 'rules_manage.php,rules_manage_addEdit.php,rules_manage_delete.php',
    'entryURL'                  => 'rules_manage.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);


$actionRows[] = array(
    'name'                      => 'Course Selection_all',
    'precedence'                => '1',
    'category'                  => 'Courses',
    'description'               => '',
    'URLList'                   => 'selection.php,selectionChoices.php',
    'entryURL'                  => 'selection.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Course Selection_my',
    'precedence'                => '0',
    'category'                  => 'Courses',
    'description'               => '',
    'URLList'                   => 'selection.php,selectionChoices.php',
    'entryURL'                  => 'selection.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'N',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'Y',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'N',
    'categoryPermissionStudent' => 'Y',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Course Approval by Class',
    'precedence'                => '0',
    'category'                  => 'Courses',
    'description'               => '',
    'URLList'                   => 'approval_byClass.php',
    'entryURL'                  => 'approval_byClass.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Course Approval by Student',
    'precedence'                => '0',
    'category'                  => 'Courses',
    'description'               => '',
    'URLList'                   => 'approval_byPerson.php',
    'entryURL'                  => 'approval_byPerson.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Course Selection Numbers',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => '',
    'URLList'                   => 'report_courseSelection.php',
    'entryURL'                  => 'report_courseSelection.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Students Not Selected',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => '',
    'URLList'                   => 'report_studentsNotSelected.php',
    'entryURL'                  => 'report_studentsNotSelected.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Students Not Approved',
    'precedence'                => '0',
    'category'                  => 'Reports',
    'description'               => '',
    'URLList'                   => 'report_studentsNotApproved.php',
    'entryURL'                  => 'report_studentsNotApproved.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);

$actionRows[] = array(
    'name'                      => 'Course Selection Settings',
    'precedence'                => '0',
    'category'                  => 'Settings',
    'description'               => '',
    'URLList'                   => 'settings.php',
    'entryURL'                  => 'settings.php',
    'entrySidebar'              => 'Y',
    'menuShow'                  => 'Y',
    'defaultPermissionAdmin'    => 'Y',
    'defaultPermissionTeacher'  => 'N',
    'defaultPermissionStudent'  => 'N',
    'defaultPermissionParent'   => 'N',
    'defaultPermissionSupport'  => 'N',
    'categoryPermissionStaff'   => 'Y',
    'categoryPermissionStudent' => 'N',
    'categoryPermissionParent'  => 'N',
    'categoryPermissionOther'   => 'N',
);


//Hooks
// $hooks[0]="" ; //Serialised array to create hook and set options. See Hooks documentation online.
