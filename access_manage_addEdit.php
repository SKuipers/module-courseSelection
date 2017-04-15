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
MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE. See the
GNU General Public License for more details.

You should have received a copy of the GNU General Public License
along with this program. If not, see <http://www.gnu.org/licenses/>.
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use Modules\CourseSelection\Domain\AccessGateway;

// Autoloader & Module includes
$loader->addNameSpace('Modules\CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/access_manage_addEdit.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo "You do not have access to this action." ;
    echo "</div>" ;
} else {
    $gateway = new AccessGateway($pdo);

    $values = array(
        'courseSelectionAccessID' => '',
        'gibbonSchoolYearID'      => '',
        'dateStart'               => '',
        'dateEnd'                 => '',
        'accessType'              => '',
        'gibbonRoleIDList'   => ''
    );

    if (isset($_GET['courseSelectionAccessID'])) {
        $result = $gateway->selectOne($_GET['courseSelectionAccessID']);
        if ($result && $result->rowCount() == 1) {
            $values = $result->fetch();
        }

        $actionName = __('Edit Access');
        $actionURL = $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/access_manage_editProcess.php';
    } else {
        $actionName = __('Add Access');
        $actionURL = $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/access_manage_addProcess.php';
    }

    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/access_manage.php'>".__('Course Selection Access')."</a> > </div><div class='trailEnd'>".$actionName.'</div>';
    echo "</div>" ;

    if (isset($_GET['return'])) {
        $editLink = (isset($_GET['editID']))? $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/access_manage_addEdit.php&courseSelectionAccessID='.$_GET['editID'] : '';
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('accessAddEdit', $actionURL);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('courseSelectionAccessID', $values['courseSelectionAccessID']);
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    $row = $form->addRow();
        $row->addLabel('gibbonSchoolYearID', __('School Year'));
        $row->addSelectSchoolYear('gibbonSchoolYearID', 'Active')->isRequired()->selected($values['gibbonSchoolYearID']);

    $row = $form->addRow();
        $row->addLabel('dateStart', __('Start Date'));
        $row->addDate('dateStart')->isRequired()->setValue(dateConvertBack($guid, $values['dateStart']));

    $row = $form->addRow();
        $row->addLabel('dateEnd', __('End Date'));
        $row->addDate('dateEnd')->isRequired()->setValue(dateConvertBack($guid, $values['dateEnd']));

    $row = $form->addRow();
        $row->addLabel('accessType', __('Access Type'));
        $row->addSelect('accessType')->fromArray(array(
                'View' => __('View'),
                'Request' => __('Request Courses (approval)'),
                'Select' => __('Select Courses (no approval)')
            ))->isRequired()->selected($values['accessType']);

    $row = $form->addRow();
        $row->addLabel('gibbonRoleIDList', __('Available to Roles'));
        $row->addSelectRole('gibbonRoleIDList')
            ->isRequired()
            ->selectMultiple()
            ->placeholder(null)
            ->selected(explode(',', $values['gibbonRoleIDList']));

    $rolePermissionResults = $gateway->getAccessRolesWithoutSelectionPermission($values['courseSelectionAccessID'] );

    if ($rolePermissionResults && $rolePermissionResults->rowCount() > 0) {
        $rolePermissionsMissingList = $rolePermissionResults->fetchAll(\PDO::FETCH_COLUMN, 0);

        $row = $form->addRow();
            $row->addAlert(sprintf(__('Without access to the Course Selection action the role(s) %1$s will not be able to make course selections. Adjust the role permissions in Admin > User Admin > Manage Permissions.'), '<b>'.implode(',', $rolePermissionsMissingList).'</b>' ), 'warning');
    }


    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();
}
