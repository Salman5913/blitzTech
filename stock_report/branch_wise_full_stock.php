<?
$_GET['_page_title'] = 'Branch-wise Stock Report';
$_GET['_page_title_text'] = 'Detailed Report of Stock Movement';

include_once("../includes/inc_header.php");

$oStock = new Stock();
$oStockReport = new StockReport();
$oBranch = new Branch();
$oStockAttribute = new StockAttribute();

$SelectStock = $oStock->getAllStockWithoutAttribute($_GET['category_id'], $_GET['brand']);
$BranchWiseStockData = $oStockReport->getCompleteStockQtyByBranch($_GET['date_till']);
$SelectBranch = $oBranch->getReport();
$SelectAttribute = $oStockAttribute->getReport();

$Attributes = [];
while ($RowAttributes = DBQM::fetch_assoc($SelectAttribute))
    $Attributes[$RowAttributes['id']] = $RowAttributes['name'];
?>
<form action="" method="get" <? Utils::formValidate();?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <h3 class="card-title">Filter Options</h3>
                <div class="row">
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="category_id">Category</label>
                            <input type="text" name="category_id" id="category_id" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_CATEGORY, $_GET['category_id']);?>>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6">
                        <div class="form-group">
                            <label for="brand">Brand</label>
                            <input type="text" name="brand" id="brand" <? Autocomplete::Search(Autocomplete::TYPE_STOCK_BRAND, $_GET['brand']);?>>
                        </div>
                    </div>
                    <div class="col-lg-4 col-md-6">
                        <div class="form-group">
                            <label for="date_till">Date Till</label>
                            <input type="text" name="date_till" id="date_till" data-type="date" value="<?=$_GET['date_till']?>" data-empty="true">
                        </div>
                    </div>
                    <div class="col-lg-2">
                        <div class="form-group">
                            <label>&nbsp;</label>
                            <button type="submit" class="btn color d-block" name="search"><i class="mdi mdi-file-document-box-search"></i> Search</button>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</form>
<div class="row">
    <div class="col-sm-12">
        <div class="card">
            <div class="row">
                <div class="col-auto mr-auto">
                    <h3 class="card-title"> Report</h3>
                </div>
                <div class="col-auto ml-auto">
                    <? UtilsPrint::button() ?>
                </div>
            </div>
            <table class="table color table-striped <? UtilsPrint::makePrintable() ?>">
                <thead>
                <tr>
                    <th>Stock</th>
                    <? while ($RowBranch = DBQM::fetch_assoc($SelectBranch)) { ?>
                        <th class="text-center"><?=$RowBranch['name']?></th>
                    <? } ?>
                    <th>Total</th>
                </tr>
                </thead>
                <tbody>
                <?
                while ($RowStock = DBQM::fetch_assoc($SelectStock)) {
                    $StockAttributes = [];
                    $RowTotal=0;
                    if (empty($BranchWiseStockData[$RowStock['id']])) continue;

                    $isEmpty = true;
                    foreach ($BranchWiseStockData[$RowStock['id']] as $BranchQty) {
                        foreach ($BranchQty as $Qty)
                            if ($Qty > 0)
                                $isEmpty = false;
                    }

                    if ($isEmpty) continue;
                    ?>
                    <tr class="font-weight-bold">
                        <td><?=$RowStock['actual_stock_name']?></td>
                        <?
                        DBQM::reset($SelectBranch);
                        while ($RowBranch = DBQM::fetch_assoc($SelectBranch))
                        {
                            foreach ($BranchWiseStockData[$RowStock['id']][$RowBranch['id']] AS $AttributeId => $Qty)
                                if (!empty($AttributeId) and !in_array($AttributeId, $StockAttributes))
                                    $StockAttributes[] = $AttributeId;

                                $Qty = !empty($BranchWiseStockData[$RowStock['id']][$RowBranch['id']]) ? array_sum($BranchWiseStockData[$RowStock['id']][$RowBranch['id']]) : 0;
                                $RowTotal += $Qty;
                                ?>
                                <td class="text-center"><?=round($Qty)?></td>
                                <?
                            } ?>
                            <td class="text-right"><?=$RowTotal?></td>
                        </tr>
                        <? foreach ($StockAttributes as $AttributeId) {
                            $AttributeTotal = 0;

                            $isEmpty = true;
                            foreach ($BranchWiseStockData[$RowStock['id']] as $BranchQty) {
                                foreach ($BranchQty as $BranchColorId => $Qty)
                                    if ($BranchColorId == $AttributeId && $Qty > 0)
                                        $isEmpty = false;
                            }

                            if ($isEmpty) continue;
                            ?>
                            <tr>
                                <td>&nbsp;&nbsp; <?=$Attributes[$AttributeId]?></td>
                                <?
                                DBQM::reset($SelectBranch);
                                while ($RowBranch = DBQM::fetch_assoc($SelectBranch))
                                {
                                    $AttributeTotal += $BranchWiseStockData[$RowStock['id']][$RowBranch['id']][$AttributeId];
                                    ?>
                                    <td class="text-center"><?=$BranchWiseStockData[$RowStock['id']][$RowBranch['id']][$AttributeId]?></td>
                                <? } ?>
                                <td class="text-right"><?=$AttributeTotal?> &nbsp;&nbsp;</td>
                            </tr>
                        <? } ?>
                    <? } ?>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
<? UtilsPrint::init("Stock Report As On ".DateEncode(empty($_GET['date_till']) ? date('m/d/Y') : $_GET['date_till'], 'M d, Y')); ?>
<? include_once("../includes/inc_footer.php") ?>