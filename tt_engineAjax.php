<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

include '../../functions.php';

if (empty($secureFilePath)) {
    $secureFilePath = $_SESSION[$guid]['absolutePath'].'/uploads';
}

echo file_exists($secureFilePath.'/engine/batchProcessing.txt')? '1' : '0';

