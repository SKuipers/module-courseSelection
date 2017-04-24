<?php
//USE ;end TO SEPERATE SQL STATEMENTS. DON'T USE ;end IN ANY OTHER PLACES!

$sql=array() ;
$count=0 ;

//v0.0.00
$sql[$count][0]="0.0.00" ;


//v0.0.01
$count++;
$sql[$count][0]="0.0.01" ;

//v0.0.02
$count++;
$sql[$count][0]="0.0.02" ;
$sql[$count][1]="
ALTER TABLE `courseSelectionChoice` ADD `courseSelectionBlockID` INT(10) UNSIGNED ZEROFILL NULL AFTER `gibbonCourseID`;end
ALTER TABLE `courseSelectionChoice` CHANGE `status` `status` ENUM('Required','Approved','Requested','Selected','Recommended','Declined','Removed') CHARACTER SET utf8 COLLATE utf8_general_ci NOT NULL DEFAULT 'Selected';end
ALTER TABLE `courseSelectionOfferingBlock` ADD `sequenceNumber` INT(3) NULL AFTER `maxSelect`;end" ;

//v0.0.03
$count++;
$sql[$count][0]="0.0.03" ;
$sql[$count][1]="INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Course Selection'), 'Copy Course Selections', 0, 'Tools', 'Create new course selections from existing course enrolments.', 'tools_copy.php', 'tools_copy.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Course Selection' AND gibbonAction.name='Copy Course Selections'));end" ;


?>
