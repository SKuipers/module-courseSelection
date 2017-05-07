<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

//$results = cartesian([[0,1], [0,1,2,3], [0,1,2]]);

$gibbonPersonIDStudent = '0000002185'; // '0000002012';

$data = array('gibbonPersonIDStudent' => $gibbonPersonIDStudent);
$sql = "SELECT gibbonCourse.gibbonCourseID, CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) as className
        FROM courseSelectionChoice
        JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
        JOIN gibbonCourse ON (gibbonCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
        JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
        WHERE courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
        AND courseSelectionChoice.status <> 'Removed'
        AND courseSelectionChoice.status <> 'Recommended'
";
$result = $pdo->executeQuery($data, $sql);

$courses = ($result && $result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_COLUMN) : array();

// echo '<pre>';
// print_r($courses);
// echo '</pre>';

$startTime = microtime();

$possibilities = cartesian(array_values($courses));
$usablePossibilities = array();

foreach ($possibilities as $classes) {
    $periods = array_count_values(array_map(function($s) {return strrchr($s, '.');}, $classes));

    if (count($periods) >= count($classes) ) {
        $usablePossibilities[] = $classes;
    }
}

$timetableDay = array( '1-1' => '', '1-2' => '', '1-3' => '', '1-4' => '', '2-1' => '', '2-2' => '', '2-3' => '', '2-4' => '' );
$timetable = array();

foreach ($usablePossibilities as $classes) {
    $periods = array_map(function($s) {return substr(strrchr($s, '.'), 1);}, $classes);

    $timetable[] = array_merge($timetableDay, array_combine($periods, $classes));
}

echo '<pre>';
echo 'Duration: '.number_format(microtime() - $startTime, 6).'ms'."\n";
echo 'Possibilities: '.count($possibilities)."\n";
echo 'Usable Possibilities: '.count($usablePossibilities)."\n";

echo "Courses: ";
print_r($timetable);
echo '</pre>';


// // $data = array('gibbonPersonIDStudent' => $gibbonPersonIDStudent);
// // $sql = "SELECT gibbonTTColumnRow.name, CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) as className
// //         FROM courseSelectionChoice
// //         JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
// //         JOIN gibbonCourse AS oldCourse ON (oldCourse.gibbonCourseID=courseSelectionChoice.gibbonCourseID)
// //         JOIN gibbonCourse ON (oldCourse.nameShort=gibbonCourse.nameShort AND gibbonCourse.gibbonSchoolYearID=011)
// //         JOIN gibbonCourseClass ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
// //         JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
// //         JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnRowID=gibbonTTDayRowClass.gibbonTTColumnRowID)
// //         WHERE courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
// //         AND courseSelectionChoice.status <> 'Removed'
// //         AND courseSelectionChoice.status <> 'Recommended'
// //         AND gibbonTTDayRowClass.gibbonTTDayID=0000000002
// //         ORDER BY gibbonTTColumnRow.timeStart
// // ";
// // $result = $pdo->executeQuery($data, $sql);

// $data = array('gibbonPersonIDStudent' => $gibbonPersonIDStudent);
// $sql = "SELECT CONCAT(gibbonTTDay.nameShort, '.', gibbonTTColumnRow.name), CONCAT(gibbonCourse.nameShort,'.',gibbonCourseClass.nameShort) as className
//         FROM gibbonTTDay
//         JOIN gibbonTTColumnRow ON (gibbonTTColumnRow.gibbonTTColumnID=gibbonTTDay.gibbonTTColumnID)
//         JOIN gibbonTTDayRowClass ON (gibbonTTDayRowClass.gibbonTTColumnRowID=gibbonTTColumnRow.gibbonTTColumnRowID AND gibbonTTDayRowClass.gibbonTTDayID=gibbonTTDay.gibbonTTDayID)
//         LEFT JOIN gibbonCourseClass ON (gibbonTTDayRowClass.gibbonCourseClassID=gibbonCourseClass.gibbonCourseClassID)
//         LEFT JOIN gibbonCourse ON (gibbonCourseClass.gibbonCourseID=gibbonCourse.gibbonCourseID)
//         LEFT JOIN gibbonCourse AS newCourse ON (newCourse.nameShort=gibbonCourse.nameShort AND newCourse.gibbonSchoolYearID=012)
//         LEFT JOIN courseSelectionChoice ON (courseSelectionChoice.gibbonCourseID=newCourse.gibbonCourseID)
//         LEFT JOIN courseSelectionApproval ON (courseSelectionApproval.courseSelectionChoiceID=courseSelectionChoice.courseSelectionChoiceID)
//         WHERE (gibbonTTDay.gibbonTTDayID=0000000002 OR gibbonTTDay.gibbonTTDayID=0000000021)
//         AND courseSelectionChoice.gibbonPersonIDStudent=:gibbonPersonIDStudent
//         AND courseSelectionChoice.status <> 'Removed'
//         AND courseSelectionChoice.status <> 'Recommended'
//         AND courseSelectionChoice.courseSelectionChoiceID IS NOT NULL
//         AND courseSelectionApproval.courseSelectionChoiceID IS NOT NULL
//         GROUP BY gibbonCourseClass.gibbonCourseClassID
//         ORDER BY gibbonTTDay.nameShort, gibbonTTColumnRow.timeStart
// ";
// $result = $pdo->executeQuery($data, $sql);

// $courses = ($result && $result->rowCount() > 0)? $result->fetchAll(\PDO::FETCH_GROUP|\PDO::FETCH_COLUMN) : array();

// echo '<pre>';
// print_r($courses);
// echo '</pre>';

// $startTime = microtime();

// $possibilities = cartesian(array_values($courses));

// $usablePossibilities = array();

// foreach ($possibilities as $classes) {
//     $courses = array_count_values(array_map(function($s) {return strchr($s, '.', true);}, $classes));

//     if (count($courses) >= count($classes) - 2 ) {
//         $usablePossibilities[] = $classes;
//     }
// }


// echo '<pre>';
// echo 'Duration: '.number_format(microtime() - $startTime, 6).'ms'."\n";
// echo 'Possibilities: '.count($possibilities)."\n";
// echo 'Usable Possibilities: '.count($usablePossibilities)."\n";

// echo "Courses: ";
// print_r($possibilities);
// echo '</pre>';


?>
