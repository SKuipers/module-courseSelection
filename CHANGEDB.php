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
$sql[$count][1]="ALTER TABLE `courseSelectionChoice` ADD `courseSelectionBlockID` INT(10) UNSIGNED ZEROFILL NULL AFTER `gibbonCourseID`;end" ;


?>
