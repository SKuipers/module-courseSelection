<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

$_POST['address'] = '/modules/Course Selection/blocks_manage_delete.php';

require_once '../../gibbon.php';

use Gibbon\Module\CourseSelection\Domain\BlocksGateway;

// Module Bootstrap
require 'module.php';

$URL = $session->get('absoluteURL').'/index.php?q=/modules/Course Selection/blocks_manage.php';

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/blocks_manage_delete.php') == false) {
    $URL .= '&return=error0';
    header("Location: {$URL}");
    exit;
} else {
    //Proceed!
    $courseSelectionBlockID = $_POST['courseSelectionBlockID'] ?? '';

    if (empty($courseSelectionBlockID)) {
        $URL .= '&return=error1';
        header("Location: {$URL}");
        exit;
    } else {

        $gateway = $container->get(BlocksGateway::class);

        $deleted = $gateway->delete($courseSelectionBlockID);

        if ($deleted == false) {
            $URL .= '&return=error2';
            header("Location: {$URL}");
            exit;
        } else {
            $deleted = $gateway->deleteAllCoursesByBlock($courseSelectionBlockID);

            if ($deleted == false) {
                $URL .= "&return=warning2";
                header("Location: {$URL}");
                exit;
            } else {
                $URL .= "&return=success0";
                header("Location: {$URL}");
                exit;
            }
        }
    }
}
