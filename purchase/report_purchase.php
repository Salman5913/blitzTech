<?
$_GET['_page_title'] = 'Purchase Report';
$_GET['_page_title_text'] = 'Purchase Report';

include_once("../includes/inc_header.php");

$oPurchase = new Purchase();
$ConvertInvoices = '';

$Select = $oPurchase->getReport($_GET['date_from'], $_GET['date_till'], $_GET['invoice_number'], $_GET['stock_id'], $_GET['supplier_account_id'], $_GET['description'], $_GET['voucher_type_id']);

// Load rows into an array to compute basic metrics dynamically
$RowsArray = [];
$Total = 0;
while ($Row = DBQM::fetch_assoc($Select)) {
    $RowsArray[] = $Row;
    $Total += $Row['voucher_total'];
}
$num_rows = count($RowsArray);
$mean = $num_rows > 0 ? $Total / $num_rows : 0;
?>
<style>
    /* Sales/Purchase Analytics Dashboard Styling */
    .sales-metric-card {
        border: none !important;
        border-radius: 12px !important;
        box-shadow: 0 4px 20px rgba(0, 0, 0, 0.02) !important;
        background: #ffffff !important;
        overflow: hidden;
        position: relative;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1), box-shadow 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .sales-metric-card:hover {
        transform: translateY(-3px);
        box-shadow: 0 8px 25px rgba(59, 93, 231, 0.08) !important;
    }
    
    .sales-metric-label {
        font-size: 0.72rem;
        letter-spacing: 0.06em;
        font-weight: 600;
        text-transform: uppercase;
        color: #8c90a6;
        margin-bottom: 8px;
    }
    
    .sales-metric-value {
        color: #000035;
        font-weight: 700;
        font-size: 1.5rem;
        margin-bottom: 0;
    }
    
    .sales-icon-box {
        width: 48px;
        height: 48px;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 1.5rem;
        transition: transform 0.25s cubic-bezier(0.4, 0, 0.2, 1);
    }
    
    .sales-metric-card:hover .sales-icon-box {
        transform: scale(1.08);
    }
    
    /* Accent borders */
    .card-accent-bar {
        position: absolute;
        bottom: 0;
        left: 0;
        width: 100%;
        height: 3px;
    }
    
    .accent-blue { background: linear-gradient(90deg, #3b5de7, #50a5f1); }
    .accent-green { background: linear-gradient(90deg, #45cb85, #34c38f); }
    .accent-yellow { background: linear-gradient(90deg, #eeb902, #f1b44c); }
    
    /* Icon backdrops */
    .icon-bg-blue { background: rgba(59, 93, 231, 0.08); color: #3b5de7; }
    .icon-bg-green { background: rgba(69, 203, 133, 0.08); color: #45cb85; }
    .icon-bg-yellow { background: rgba(238, 185, 2, 0.08); color: #eeb902; }
    
    /* Premium Filter Styling */
    .premium-filter-wrapper {
        background: #f8f9fa;
        border-radius: 12px;
        padding: 24px;
        border: 1px solid rgba(59, 93, 231, 0.1);
        margin-top: 15px;
        box-shadow: inset 0 2px 10px rgba(0,0,0,0.01);
    }
    
    .premium-filter-wrapper label {
        font-weight: 600;
        color: #000035;
        font-size: 0.8rem;
        letter-spacing: 0.04em;
        text-transform: uppercase;
        margin-bottom: 8px;
    }
    
    .premium-filter-wrapper .form-control {
        border-radius: 8px;
        border: 1px solid #e1e5eb;
        padding: 10px 15px;
        font-size: 0.9rem;
        transition: all 0.25s ease;
        background: #ffffff;
    }
    
    .premium-filter-wrapper .form-control:focus {
        border-color: #3b5de7;
        box-shadow: 0 0 0 0.2rem rgba(59, 93, 231, 0.15);
        background: #ffffff;
    }
    
    .btn-search-premium {
        background: linear-gradient(135deg, #000035 0%, #3b5de7 100%);
        border: none;
        color: white;
        padding: 10px 25px;
        border-radius: 8px;
        font-weight: 600;
        letter-spacing: 0.05em;
        transition: transform 0.25s ease, box-shadow 0.25s ease;
    }
    
    .btn-search-premium:hover {
        transform: translateY(-2px);
        box-shadow: 0 6px 15px rgba(59, 93, 231, 0.25);
        color: white;
    }
    
    /* Data Table Font Size & Styling */
    .data-table-report {
        font-size: 0.85rem;
    }
    
    .data-table-report thead th {
        font-size: 0.8rem;
        font-weight: 700;
        text-transform: uppercase;
        letter-spacing: 0.05em;
        color: #8c90a6;
        border-bottom: 2px solid rgba(59, 93, 231, 0.1) !important;
        padding: 12px 15px;
        vertical-align: middle;
    }
    
    .data-table-report tbody td, .data-table-report tfoot td {
        padding: 12px 15px;
        vertical-align: middle;
    }
</style>
<form action="" method="get" <? Utils::formValidate(); ?>>
    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <? $oPurchase->showVoucherButtonsWithOptions([$_GET['voucher_type_id']]); ?>
                <div class="filter hidden premium-filter-wrapper">
                  <div class="row">
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="form-group mb-2">
                            <label>Date Range</label>
                            <div class="input-daterange input-group" data-provide="datepicker">
                                <input type="text" class="form-control" name="date_from" data-type="date" value="<?=$_GET['date_from'] == '' ? date("M d, Y", strtotime("-15 Days")) : DateEncode($_GET['date_from'], 'M d, Y')?>" data-empty="true" />
                                <input type="text" class="form-control" name="date_till" data-type="date" value="<?=$_GET['date_till'] == '' ? date("M d, Y") : DateEncode($_GET['date_till'], 'M d, Y')?>" data-empty="true" />
                            </div>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-6">
                        <div class="form-group mb-2">
                            <label for="invoice_number">Voucher Number</label>
                            <input type="text" class="form-control" name="invoice_number" id="invoice_number" data-type="int" value="<?=$_GET['invoice_number']?>">
                        </div>
                    </div>
                    <div class="col-xl-4 col-lg-5 col-md-6">
                        <div class="form-group mb-2">
                            <label for="supplier_account_id">Supplier Name</label>
                            <input type="text" class="form-control" name="supplier_account_id" id="supplier_account_id" <? Autocomplete::ACSearchAccount($_GET['supplier_account_id']) ?>>
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-4 col-md-6">
                        <div class="form-group mb-2">
                            <label for="stock_id">Item Name</label>
                            <input type="text" class="form-control" id="stock_id" name="stock_id" <? Autocomplete::ACSearchStock($_GET['stock_id'], '') ?>>
                        </div>
                    </div>
                    <div class="col-xl-2 col-lg-3 col-md-4">
                        <div class="form-group mb-2">
                            <label for="voucher_type_id">Voucher Type</label>
                            <select class="form-control" id="voucher_type_id" name="voucher_type_id"><? Utils::getDropDownOptionsFromArray([
                                    VoucherType::PIV => VoucherType::getName(VoucherType::PIV),
                                    VoucherType::PRV => VoucherType::getName(VoucherType::PRV),
                                    VoucherType::POV => VoucherType::getName(VoucherType::POV),
                                    VoucherType::OPS => VoucherType::getName(VoucherType::OPS)
                                ], $_GET['voucher_type_id']); ?>
                            </select>
                        </div>
                    </div>
                    <div class="col-xl-7 col-lg-9 col-md-8">
                        <div class="form-group mb-2">
                            <label for="description">Description</label>
                            <input type="text" class="form-control" id="description" name="description" value="<?=$_GET['description']?>">
                        </div>
                    </div>
                    <div class="col-xl-3 col-lg-12 col-md-12 text-right d-flex align-items-end justify-content-end mb-2">
                        <input type="hidden" name="action_voucher_type_id" value="<?=$_GET['voucher_type_id']?>">
                        <button type="submit" class="btn-search-premium w-100" name="search" id="search" style="max-width: 200px;">
                            <i class="fa fa-search mr-1"></i> Search
                        </button>
                    </div>
                  </div>
                </div>
            </div>
        </div>
    </div>
    <!-- Premium Dynamic Purchase Analytics Dashboard -->
    <?php if ($num_rows > 0): ?>
    <div class="row mb-4" style="font-family: 'Plus Jakarta Sans', sans-serif;">
        <!-- Card 1: Net Purchase -->
        <div class="col-md-4">
            <div class="card sales-metric-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="sales-metric-label">Net Purchase Volume</h6>
                            <h3 class="sales-metric-value"><?= Utils::format($Total, 2) ?></h3>
                        </div>
                        <div class="sales-icon-box icon-bg-blue">
                            <i class="mdi mdi-cart-outline"></i>
                        </div>
                    </div>
                    <div class="card-accent-bar accent-blue"></div>
                </div>
            </div>
        </div>
        
        <!-- Card 2: Average Order Value -->
        <div class="col-md-4">
            <div class="card sales-metric-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="sales-metric-label">Avg Voucher Size</h6>
                            <h3 class="sales-metric-value"><?= Utils::format($mean, 2) ?></h3>
                        </div>
                        <div class="sales-icon-box icon-bg-green">
                            <i class="mdi mdi-chart-line"></i>
                        </div>
                    </div>
                    <div class="card-accent-bar accent-green"></div>
                </div>
            </div>
        </div>
        
        <!-- Card 3: Total Transactions -->
        <div class="col-md-4">
            <div class="card sales-metric-card">
                <div class="card-body p-4">
                    <div class="d-flex align-items-center justify-content-between">
                        <div>
                            <h6 class="sales-metric-label">Vouchers Tracked</h6>
                            <h3 class="sales-metric-value"><?= $num_rows ?></h3>
                        </div>
                        <div class="sales-icon-box icon-bg-yellow">
                            <i class="mdi mdi-file-document-outline"></i>
                        </div>
                    </div>
                    <div class="card-accent-bar accent-yellow"></div>
                </div>
            </div>
        </div>
    </div>
    <?php endif; ?>

    <div class="row">
        <div class="col-sm-12">
            <div class="card">
                <div class="row">
                    <div class="col-md-9"><h3 class="card-title"> Search Results</div>
                    <div class="col-auto ml-auto"><? UtilsPrint::button() ?></div>
                </div>
                <div class="table-responsive">
                    <table id="datatable" class="table data-table-report color table-striped dt-responsive nowrap <? UtilsPrint::makePrintable(); ?>">
                        <thead>
                        <tr>
                            <th><input type="checkbox" id="check_all" value="Y"> </th>
                            <th>Voucher Number</th>
                            <th>Date</th>
                            <th>Supplier</th>
                            <th>description</th>
                            <th class="text-right">Voucher Total</th>
                            <th class="text-right">Created By</th>
                            <th class="text-right">Voucher Status</th>
                            <th class="text-right"><i class="fas fa-cog"></i> Actions</th>
                        </tr>
                        </thead>
                        <tbody>
                        <?php foreach ($RowsArray as $Row): ?>
                            <tr class="<?=$oPurchase->highlightDeletedRow($Row)?>">
                                <td><label><input type="checkbox" name="distinct_id[<?=$Row['distinct_id']?>]" data-js="distinct_id" id="distinct_id" value="<?=$Row['distinct_id']?>"></label></td>
                                <td><?=$Row['voucher_prefix'].'-'.$Row['voucher_number']?></td>
                                <td><?=DateDecode($Row['voucher_date'], 'M d, Y')?></td>
                                <td><?=$Row['supplier_name']?></td>
                                <td><?=$Row['description']?></td>
                                <td class="text-right" data-js="voucher_total"><?=Utils::format($Row['voucher_total'], 2)?></td>
                                <td class="text-right font-weight-bolder"><?=$Row['created_by']?></td>
                                <td class="text-right"><? $oPurchase->getVoucherStatus($Row); ?></td>
                                <td class="text-right report-actions">
                                    <? $oPurchase->getReportButtons($Row['voucher_type_id'], $Row['id']); ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                        </tbody>
                        <tfoot>
                        <tr>
                            <td colspan="5" class="text-right font-weight-bold display-6" style="color: #000035; vertical-align: middle;">Grand Total:</td>
                            <td class="text-right font-weight-bold display-6 text-success" style="vertical-align: middle;" <? Utils::sumReportColumn('[data-js="voucher_total"]');?>></td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                            <td>&nbsp;</td>
                        </tr>
                        </tfoot>
                    </table>
                </div>
            </div>
        </div>
    </div>
<? UtilsPrint::init("");?>
<? include_once("../includes/inc_footer.php") ?>