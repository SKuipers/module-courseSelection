<?php
/*
Gibbon: Course Selection & Timetabling Engine
Copyright (C) 2017, Sandra Kuipers
*/

use Gibbon\Forms\Form;
use Gibbon\Forms\DatabaseFormFactory;
use CourseSelection\Domain\OfferingsGateway;

// Autoloader & Module includes
$loader->addNameSpace('CourseSelection\\', 'modules/Course Selection/src/');
include "./modules/" . $_SESSION[$guid]["module"] . "/moduleFunctions.php" ;

if (isActionAccessible($guid, $connection2, '/modules/Course Selection/offerings_manage_addEdit.php') == false) {
    //Acess denied
    echo "<div class='error'>" ;
        echo __('You do not have access to this action.');
    echo "</div>" ;
} else {
    $gateway = new OfferingsGateway($pdo);

    $values = array(
        'courseSelectionOfferingID' => '',
        'gibbonSchoolYearID'        => $_REQUEST['gibbonSchoolYearID'] ?? $_SESSION[$guid]['gibbonSchoolYearID'],
        'gibbonYearGroupIDList'     => '',
        'name'                      => '',
        'description'               => '',
        'minSelect'                 => '0',
        'maxSelect'                 => '1',
        'sequenceNumber'            => $gateway->getNextSequenceNumber()
    );

    if (isset($_GET['courseSelectionOfferingID'])) {
        $result = $gateway->selectOne($_GET['courseSelectionOfferingID']);
        if ($result && $result->rowCount() == 1) {
            $values = $result->fetch();
        }

        $action = 'edit';
        $actionName = __('Edit Course Offering');
        $actionURL = $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/offerings_manage_editProcess.php';
    } else {
        $action = 'add';
        $actionName = __('Add Course Offering');
        $actionURL = $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/offerings_manage_addProcess.php';
    }

    echo "<div class='trail'>" ;
    echo "<div class='trailHead'><a href='".$_SESSION[$guid]['absoluteURL']."'>".__('Home')."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q']).'/'.getModuleEntry($_GET['q'], $connection2, $guid)."'>".__(getModuleName($_GET['q']))."</a> > <a href='".$_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/'.getModuleName($_GET['q'])."/offerings_manage.php'>".__('Manage Course Offerings', 'Course Selection')."</a> > </div><div class='trailEnd'>".$actionName.'</div>';
    echo "</div>" ;

    if (isset($_GET['return'])) {
        $editLink = (isset($_GET['editID']))? $_SESSION[$guid]['absoluteURL'].'/index.php?q=/modules/Course Selection/offerings_manage_addEdit.php&courseSelectionOfferingID='.$_GET['editID'] : '';
        returnProcess($guid, $_GET['return'], $editLink, null);
    }

    $form = Form::create('offeringsAddEdit', $actionURL);
    $form->setFactory(DatabaseFormFactory::create($pdo));

    $form->addHiddenValue('courseSelectionOfferingID', $values['courseSelectionOfferingID']);
    $form->addHiddenValue('address', $_SESSION[$guid]['address']);

    if ($action == 'edit') {
        $form->addHiddenValue('gibbonSchoolYearID', $values['gibbonSchoolYearID']);
        $row = $form->addRow();
            $row->addLabel('schoolYearName', __('School Year'));
            $row->addTextField('schoolYearName')->readonly()->setValue($values['schoolYearName']);
    } else {
        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearID', __('School Year'));
            $row->addSelectSchoolYear('gibbonSchoolYearID', 'Active')->isRequired()->selected($values['gibbonSchoolYearID']);
    }

    $row = $form->addRow();
        $row->addLabel('name', __('Name'));
        $row->addTextField('name')->isRequired()->maxLength(90)->setValue($values['name']);

    $row = $form->addRow();
        $row->addLabel('description', __('Description'));
        $row->addTextField('description')->maxLength(255)->setValue($values['description']);

    $row = $form->addRow();
        $row->addLabel('minSelect', __('Min Selections'))->description(__('Across all course blocks.'));
        $row->addNumber('minSelect')->isRequired()->minimum(0)->maximum(100)->setValue($values['minSelect']);

    $row = $form->addRow();
        $row->addLabel('maxSelect', __('Max Selections'))->description(__('Across all course blocks.'));
        $row->addNumber('maxSelect')->isRequired()->minimum(0)->maximum(100)->setValue($values['maxSelect']);

    $row = $form->addRow();
        $row->addLabel('sequenceNumber', __('Sequence Number'));
        $row->addNumber('sequenceNumber')->isRequired()->minimum(0)->maximum(999)->setValue($values['sequenceNumber']);

    $row = $form->addRow();
        $row->addFooter();
        $row->addSubmit();

    echo $form->getOutput();

    if ($action == 'edit' && !empty($values['courseSelectionOfferingID'])) {
        // RESTRICTIONS
        echo '<h3>';
        echo __('Manage Enrolment Restrictions');
        echo '</h3>';

        $blocks = $gateway->selectAllRestrictionsByOffering($values['courseSelectionOfferingID']);

        if ($blocks->rowCount() == 0) {
            echo '<div class="message">';
            echo __('There are currently no enrolment rescrictions applied to this course offering; any active student will be able to make course selections. Use the fields below if you wish to restrict this course offering to students enroled in a specific year group.') ;
            echo '</div>';
        } else {
            echo '<table class="fullWidth colorOddEven" cellspacing="0">';

            echo '<tr class="head">';
                echo '<th>';
                    echo __('School Year');
                echo '</th>';
                echo '<th>';
                    echo __('Year Group');
                echo '</th>';
                echo '<th style="width: 80px;">';
                    echo __('Actions');
                echo '</th>';
            echo '</tr>';

            while ($block = $blocks->fetch()) {
                echo '<tr>';
                    echo '<td>'.$block['schoolYearName'].'</td>';
                    echo '<td>'.$block['yearGroupName'].'</td>';
                    echo '<td>';
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/".$_SESSION[$guid]['module']."/offerings_manage_restriction_deleteProcess.php?courseSelectionOfferingID=".$block['courseSelectionOfferingID']."&courseSelectionOfferingRestrictionID=".$block['courseSelectionOfferingRestrictionID']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                echo '</tr>';
            }

            echo '</table>';
        }

        $form = Form::create('offeringsRestrictionAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/offerings_manage_restriction_addProcess.php');
        $form->setFactory(DatabaseFormFactory::create($pdo));

        $form->addHiddenValue('courseSelectionOfferingID', $values['courseSelectionOfferingID']);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $row = $form->addRow();
            $row->addLabel('gibbonSchoolYearID', __('Student Enrolment'));
            $row->addSelectSchoolYear('gibbonSchoolYearID', 'Active')->isRequired()->setClass('mediumWidth');
            $row->addSelectYearGroup('gibbonYearGroupID')->isRequired()->setClass('mediumWidth');

        $row = $form->addRow();
            $row->addSubmit(__('Add'));

        echo $form->getOutput();


        // BLOCKS
        echo '<h3>';
        echo __('Manage Blocks');
        echo '</h3>';

        $blocks = $gateway->selectAllBlocksByOffering($values['courseSelectionOfferingID']);

        if ($blocks->rowCount() == 0) {
            echo '<div class="error">';
            echo __("There are no records to display.") ;
            echo '</div>';
        } else {
            echo '<table id="offeringBlocks" class="fullWidth colorOddEven" cellspacing="0">';
            echo '<thead>';
            echo '<tr class="head">';
                echo '<th style="width: 40%;">';
                    echo __('Course Block');
                echo '</th>';
                echo '<th style="width: 20%;">';
                    echo __('Courses');
                echo '</th>';
                echo '<th style="width: 15%;">';
                    echo __('Min Selections');
                echo '</th>';
                echo '<th style="width: 15%;">';
                    echo __('Max Selections');
                echo '</th>';
                echo '<th style="width: 10%;">';
                    echo __('Actions');
                echo '</th>';
            echo '</tr>';
            echo '</thead>';
            echo '<tbody>';

            while ($block = $blocks->fetch()) {
                echo '<tr>';
                    echo '<td style="width: 40%;"><div class="drag-handle"></div>'.$block['blockName'].'</td>';
                    echo '<td style="width: 20%;">'.$block['courseCount'].'</td>';
                    echo '<td style="width: 15%;">'.$block['minSelect'].'</td>';
                    echo '<td style="width: 15%;">'.$block['maxSelect'].'</td>';
                    echo '<td style="width: 10%;">';
                        echo '<input type="hidden" name="offeringBlockID" class="offeringBlockID" value="'.$block['courseSelectionBlockID'].'">';
                        echo "<a href='".$_SESSION[$guid]['absoluteURL']."/modules/".$_SESSION[$guid]['module']."/offerings_manage_block_deleteProcess.php?courseSelectionOfferingID=".$block['courseSelectionOfferingID']."&courseSelectionBlockID=".$block['courseSelectionBlockID']."'><img title='".__('Delete')."' src='./themes/".$_SESSION[$guid]['gibbonThemeName']."/img/garbage.png'/></a>";
                    echo '</td>';
                echo '</tr>';
            }

            echo '</tbody>';
            echo '</table>';
            ?>

            <script>

                $('#offeringBlocks tbody').sortable({
                    update: function() {
                        offeringBlockOrderSave(<?php echo $values['courseSelectionOfferingID']; ?>, '<?php echo $_SESSION[$guid]['absoluteURL'].'/modules/Course Selection/'; ?>');
                    }
                }).disableSelection();
            </script>

            <?php
        }

        $form = Form::create('offeringsBlockAdd', $_SESSION[$guid]['absoluteURL'].'/modules/'.$_SESSION[$guid]['module'].'/offerings_manage_block_addProcess.php');

        $form->addHiddenValue('courseSelectionOfferingID', $values['courseSelectionOfferingID']);
        $form->addHiddenValue('address', $_SESSION[$guid]['address']);

        $blockList = $gateway->selectAvailableBlocksBySchoolYear($values['courseSelectionOfferingID'], $values['gibbonSchoolYearID']);

        $row = $form->addRow();
            $row->addLabel('courseSelectionBlockID', __('Course Block'));
            $row->addSelect('courseSelectionBlockID')
                ->fromResults($blockList)
                ->isRequired()
                ->selectMultiple();

        $row = $form->addRow();
            $row->addLabel('minSelect', __('Min Selections'));
            $row->addNumber('minSelect')->isRequired()->minimum(0)->maximum(100)->setValue(1);

        $row = $form->addRow();
            $row->addLabel('maxSelect', __('Max Selections'));
            $row->addNumber('maxSelect')->isRequired()->minimum(0)->maximum(100)->setValue(1);

        $row = $form->addRow();
            $row->addSubmit(__('Add'));

        echo $form->getOutput();
    }
}
