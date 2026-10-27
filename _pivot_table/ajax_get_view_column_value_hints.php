<?php
include_once ("../connection/localhost.php");

/**
 * @var $oPivotTable PivotTable
 */
$oPivotTable = new $_GET['pivot_table_class']();

if (!$oPivotTable instanceof PivotTable)
    die("Unidentified Class");

$DataArray = $oPivotTable->getSelectionValues($_GET['column_name']);
$ExistingValues = explode(",", $_GET['existing_values']);

if (empty($DataArray)) die("No Hint Available");
?>
<table class="table data-table-report color table-striped table-condensed">
    <tbody>
    <? foreach ($DataArray as $Key => $Value) { ?>
        <tr>
            <td style="vertical-align: middle;"><input type="checkbox" data-js="value_selection_hint" value="<?=$Key?>" id="<?=$Key?>" <?=in_array($Key, $ExistingValues) ? ' checked ' : '' ?>  /></td>
            <td><label style="padding: 0" for="<?=$Key?>"><?=$Value?></label></td>
        </tr>
    <? } ?>
    </tbody>
</table>
<button type="button" class="btn btn-danger" id="btn-pull-values">Select These</button>