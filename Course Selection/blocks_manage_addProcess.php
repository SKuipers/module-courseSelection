<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Module\CourseSelection\Domain\BlocksGateway;

$_POST['address'] = '/modules/Course Selection/blocks_manage_addEdit.php';

require_once '../../gibbon.php';

// Module Bootstrap
require 'module.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/blocks_manage_addEdit.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_addEdit.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $data = array();
    $data['gibbonSchoolYearID'] = $_POST['gibbonSchoolYearID'] ?? '';
    $data['gibbonDepartmentIDList'] = $_POST['gibbonDepartmentIDList'] ?? '';
    $data['name'] = $_POST['name'] ?? '';
    $data['description'] = $_POST['description'] ?? '';
    $data['countable'] = $_POST['countable'] ?? '';

    if (!empty($data['gibbonDepartmentIDList'])) {
        $data['gibbonDepartmentIDList'] = implode(',', $data['gibbonDepartmentIDList']);
    }

    if (empty($data['gibbonSchoolYearID']) || empty($data['name'])) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $gateway = $container->get(BlocksGateway::class);

        $insertID = $gateway->insert($data);

        if (empty($insertID)) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        } else {
            $URL .= "&return=success0&editID=$insertID";
            header("Location: {$URL}");
            exit;
        }
    }
}
