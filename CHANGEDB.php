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
$sql[$count][1]="
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Course Selection'), 'Copy Course Selections', 0, 'Tools', 'Create new course selections from existing course enrolments.', 'tools_copy.php', 'tools_copy.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Course Selection' AND gibbonAction.name='Copy Course Selections'));end" ;

//v0.0.04
$count++;
$sql[$count][0]="0.0.04" ;
$sql[$count][1]="
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'selectionComplete', 'Message on Completion', 'The text to display when the course selection process is complete.', 'Great! The course selection form is complete, you\'re ready to submit.');end
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'selectionInvalid', 'Message on Invalid', 'The text to display when an invalid selection has been made.', 'The form is incomplete or contains an invalid choice. Please check your course selections above.');end
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'selectionContinue', 'Message to Continue', 'The text to display when the course selection is in progress.', 'Continue selecting courses. You can submit a partial selection now and complete your choices at a later date.');end";

//v0.0.05
$count++;
$sql[$count][0]="0.0.05" ;
$sql[$count][1]="
UPDATE `gibbonAction` SET `name`='Copy Selections By Course', `URLList`='tools_copyByCourse.php', `entryURL`='tools_copyByCourse.php' WHERE name='Copy Course Selections' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonModule.name='Course Selection');end
";

//v0.0.06
$count++;
$sql[$count][0]="0.0.06" ;
$sql[$count][1]="
CREATE TABLE `courseSelectionApproval` (
  `courseSelectionApprovalID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT ,
  `courseSelectionChoiceID` INT(12) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDApproved` INT(10) UNSIGNED ZEROFILL NULL ,
  `timestampApproved` DATETIME NULL ,
  PRIMARY KEY (`courseSelectionApprovalID`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE utf8_general_ci;end
CREATE TABLE `courseSelectionRecommendation` (
  `courseSelectionRecommendationID` INT(12) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT ,
  `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL DEFAULT NULL,
  `gibbonPersonIDStudent` INT(10) UNSIGNED ZEROFILL NOT NULL ,
  `gibbonCourseID` INT(8) UNSIGNED ZEROFILL NULL ,
  `gibbonPersonIDRecommended` INT(10) UNSIGNED ZEROFILL NULL ,
  `timestampRecommended` DATETIME NULL ,
  PRIMARY KEY (`courseSelectionRecommendationID`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE utf8_general_ci;end
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'activeSchoolYear', 'Course Selection School Year', 'Sets the default school year to be pre-selected on various pages.', (SELECT gibbonSchoolYearID FROM gibbonSchoolYear WHERE status='Upcoming' ORDER BY sequenceNumber ASC LIMIT 1));end
";

//v0.0.07
$count++;
$sql[$count][0]="0.0.07" ;
$sql[$count][1]="
UPDATE `gibbonAction` SET `name`='Course Approval by Offering', `URLList`='approval_byOffering.php', `entryURL`='approval_byOffering.php' WHERE name='Course Approval by Student' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonModule.name='Course Selection');end
ALTER TABLE `courseSelectionApproval` DROP `courseSelectionApprovalID`;end
ALTER TABLE `courseSelectionApproval` ADD PRIMARY KEY(`courseSelectionChoiceID`);end
";

//v0.0.08
$count++;
$sql[$count][0]="0.0.08" ;
$sql[$count][1]="
UPDATE `gibbonAction` SET `name`='Total Requests by Course', `URLList`='report_requestsByCourse.php', `entryURL`='report_requestsByCourse.php' WHERE name='Course Selection Numbers' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonModule.name='Course Selection');end
";

//v0.0.09
$count++;
$sql[$count][0]="0.0.09" ;
$sql[$count][1]="
UPDATE `gibbonAction` SET `name`='Approve Requests by Course', `URLList`='approval_byCourse.php', `entryURL`='approval_byCourse.php', category='Approval' WHERE name='Course Approval by Class' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonModule.name='Course Selection');end
UPDATE `gibbonAction` SET `name`='Approve Requests by Offering', category='Approval' WHERE name='Course Approval by Offering' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonModule.name='Course Selection');end
UPDATE `gibbonAction` SET `name`='Copy Requests By Course' WHERE name='Copy Selections By Course' AND gibbonModuleID=(SELECT gibbonModuleID FROM gibbonModule WHERE gibbonModule.name='Course Selection');end
";

//v0.1.00
$count++;
$sql[$count][0]="0.1.00" ;
$sql[$count][1]="
CREATE TABLE `courseSelectionTTResults` (
  `courseSelectionTTResultsID` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT ,
  `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL NULL ,
  `gibbonPersonIDStudent` INT(10) UNSIGNED ZEROFILL NULL ,
  `gibbonCourseID` INT(8) UNSIGNED NULL ,
  `gibbonCourseClassID` INT(8) UNSIGNED ZEROFILL NULL ,
  `weight` DECIMAL(6,2) NULL ,
  PRIMARY KEY (`courseSelectionTTResultsID`),
  INDEX `gibbonSchoolYear` (`gibbonSchoolYearID`, `gibbonPersonIDStudent`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE utf8_general_ci;end
CREATE TABLE `courseSelectionTTFlag` (
  `courseSelectionTTFlagID` INT(10) UNSIGNED ZEROFILL NOT NULL AUTO_INCREMENT ,
  `gibbonSchoolYearID` INT(3) UNSIGNED ZEROFILL NULL ,
  `gibbonPersonIDStudent` INT(10) UNSIGNED ZEROFILL NULL ,
  `gibbonCourseClassID` INT(8) UNSIGNED ZEROFILL NULL ,
  `type` VARCHAR(30) NULL ,
  `reason` VARCHAR(255) NULL ,
  PRIMARY KEY (`courseSelectionTTFlagID`),
   INDEX `gibbonSchoolYear` (`gibbonSchoolYearID`, `gibbonPersonIDStudent`, `gibbonCourseClassID`)
) ENGINE=MyISAM CHARSET=utf8 COLLATE utf8_general_ci;end
INSERT INTO `gibbonSetting` (`scope` ,`name` ,`nameDisplay` ,`description` ,`value`) VALUES ('Course Selection', 'timetablingResults', 'Timetabling Results', 'Performance and result counts from last timetable operation.', '');end
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Course Selection'), 'Timetabling Engine', 0, 'Timetable', '', 'tt_engine.php', 'tt_engine.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Course Selection' AND gibbonAction.name='Timetabling Engine'));end
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Course Selection'), 'View Results by Course', 0, 'Timetable', '', 'tt_resultsByCourse.php', 'tt_resultsByCourse.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Course Selection' AND gibbonAction.name='View Results by Course'));end
INSERT INTO `gibbonAction` (`gibbonModuleID`, `name`, `precedence`, `category`, `description`, `URLList`, `entryURL`, `defaultPermissionAdmin`, `defaultPermissionTeacher`, `defaultPermissionStudent`, `defaultPermissionParent`, `defaultPermissionSupport`, `categoryPermissionStaff`, `categoryPermissionStudent`, `categoryPermissionParent`, `categoryPermissionOther`) VALUES ((SELECT gibbonModuleID FROM gibbonModule WHERE name='Course Selection'), 'View Results by Student', 0, 'Timetable', '', 'tt_resultsByStudent.php', 'tt_resultsByStudent.php', 'Y', 'N', 'N', 'N', 'N', 'Y', 'N', 'N', 'N') ;end
INSERT INTO `gibbonPermission` (`permissionID` ,`gibbonRoleID` ,`gibbonActionID`) VALUES (NULL , '1', (SELECT gibbonActionID FROM gibbonAction JOIN gibbonModule ON (gibbonAction.gibbonModuleID=gibbonModule.gibbonModuleID) WHERE gibbonModule.name='Course Selection' AND gibbonAction.name='View Results by Student'));end
";
