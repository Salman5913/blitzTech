<?php
if (is_file("connection/localhost.php"))
    include_once("connection/localhost.php");
else
    include_once("../connection/localhost.php");

SecurityShell::redirectIfNotLoggedIn();

$SiteURL = ProjectHelper::SiteURL();
$oNotification = new Notification();
$oHeaderUtils = new HeaderUtils();
$oLinkedVoucherTabs = new LinkedVoucherTabs($_GET['voucher_type_id']);

$SelectFullDayNotification = $oNotification->getTodayNotification();

$oYear = new Year();
$oHeaderBranch = new Branch();

?>
<!doctype html>
<html lang="en">
<!-- Legacy inline theme (disabled): replaced by assets/css/finac-theme.css -->
<style media="not all">
    :root {
        --finac-primary: #b57bee;
        --finac-primary-dark: #0b3866;
        --finac-primary-light: #d2adf5;
        --finac-secondary: #0b3866;
        --finac-secondary-dark: #082a4d;
        --finac-dark-base: #061d38;
        --finac-dark-surface: #0a2f55;
        --finac-gradient-main: linear-gradient(135deg, #0b3866 0%, #b57bee 100%);
        --finac-gradient-btn: linear-gradient(135deg, #0b3866 0%, #b57bee 100%);
        --finac-gradient-btn-hover: linear-gradient(135deg, #0b3866 0%, #c897f2 100%);
    }

    #page-topbar,
    .vertical-menu,
    #side-menu,
    .navbar-brand-box {
        font-family: 'Plus Jakarta Sans', 'Segoe UI', Arial, sans-serif !important;
    }

    body[data-layout="detached"] #layout-wrapper::before {
        content: "";
        position: absolute;
        top: 0px;
        left: 0;
        right: 0;
        width: 100%;
        height: 140px;
        background: var(--finac-gradient-main) !important;
        box-shadow: 0 4px 20px rgba(11, 56, 102, 0.3);
    }

    body[data-layout="detached"] #layout-wrapper {
        display: flex !important;
        position: relative;
        width: 100% !important;
    }

    body > #layout-wrapper > .vertical-menu,
    .vertical-menu {
        flex: 0 0 280px !important;
        width: 280px !important;
        min-width: 280px !important;
        max-width: 280px !important;
        background: #0b3866 !important;
        box-shadow: 4px 0 24px rgba(22, 62, 59, 0.25);
        border-right: 1px solid rgba(181, 123, 238, 0.2);
        position: relative !important;
        z-index: 1001;
    }

    body[data-layout="detached"] .right-panel,
    .right-panel {
        flex: 1 1 auto !important;
        width: calc(100% - 280px) !important;
        max-width: calc(100% - 280px) !important;
        min-width: 0 !important;
        padding-left: 20px !important;
        padding-right: 20px !important;
        padding-bottom: 60px !important;
    }

    body[data-layout="detached"] .main-content,
    .main-content {
        margin-left: 0 !important;
        width: 100% !important;
        min-width: 0 !important;
    }

    body.vertical-collpsed .vertical-menu {
        flex: 0 0 70px !important;
        width: 70px !important;
        min-width: 70px !important;
        max-width: 70px !important;
    }

    body.vertical-collpsed .right-panel,
    body[data-layout="detached"].vertical-collpsed .right-panel {
        width: calc(100% - 70px) !important;
        max-width: calc(100% - 70px) !important;
    }

    body.vertical-collpsed .main-content,
    body[data-layout="detached"].vertical-collpsed .main-content {
        margin-left: 0 !important;
    }

    body[data-layout="detached"] .page-content,
    .page-content {
        padding: 85px 0 60px 0 !important;
    }

    #sidebar-menu {
        padding: 10px 0 60px 0 !important;
    }

    #sidebar-menu .menu-title {
        color: #ffffff !important;
        opacity: 0.95;
        font-weight: 700 !important;
        font-size: 0.76rem !important;
        text-transform: uppercase !important;
        letter-spacing: 0.08em !important;
        padding: 18px 20px 8px 20px !important;
        display: block;
        margin: 0 !important;
    }

    #sidebar-menu ul li {
        width: 100% !important;
        margin: 0 !important;
        padding: 0 !important;
    }

    #sidebar-menu ul li a {
        color: #ffffff !important;
        display: flex !important;
        align-items: center !important;
        position: relative !important;
        padding: 13px 22px !important;
        margin: 0 !important;
        border-radius: 0 !important;
        border: none !important;
        font-weight: 500 !important;
        font-size: 0.9rem !important;
        gap: 0 !important;
        text-align: left !important;
        width: 100% !important;
        background: transparent !important;
        box-shadow: none !important;
        transform: none !important;
        transition: background-color 0.2s ease, color 0.2s ease !important;
    }

    #sidebar-menu ul li a i {
        color: #ffffff !important;
        font-size: 1.25rem !important;
        min-width: 28px !important;
        width: 28px !important;
        line-height: 1 !important;
        margin-right: 18px !important;
        flex: 0 0 28px !important;
        opacity: 0.95 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        transform: none !important;
        vertical-align: middle !important;
    }

    #sidebar-menu ul li a.has-arrow:after {
        content: "" !important;
        position: absolute !important;
        right: 22px !important;
        top: 50% !important;
        width: 8px !important;
        height: 8px !important;
        border: solid #ffffff !important;
        border-width: 0 2px 2px 0 !important;
        display: inline-block !important;
        transform: translateY(-65%) rotate(45deg) !important;
        opacity: 0.95 !important;
        transition: transform 0.25s ease !important;
    }

    #sidebar-menu ul li.mm-active > a.has-arrow:after,
    #sidebar-menu ul li a.has-arrow[aria-expanded="true"]:after {
        transform: translateY(-25%) rotate(-135deg) !important;
    }

    #sidebar-menu ul li a:hover,
    #sidebar-menu ul li.mm-active > a,
    #sidebar-menu ul li a.active {
        color: #ffffff !important;
        background: rgba(0, 0, 0, 0.12) !important;
        border: none !important;
        box-shadow: none !important;
        transform: none !important;
    }

    #sidebar-menu ul li a:hover i,
    #sidebar-menu ul li.mm-active > a i {
        color: #ffffff !important;
        opacity: 1 !important;
        transform: none !important;
    }

    #sidebar-menu ul li ul.sub-menu {
        background: rgba(0, 0, 0, 0.16) !important;
        border-radius: 0 !important;
        margin: 0 !important;
        padding: 4px 0 !important;
        border: none !important;
        width: 100% !important;
    }

    #sidebar-menu ul li ul.sub-menu li a {
        padding: 9px 20px 9px 68px !important;
        font-size: 0.84rem !important;
        color: rgba(255, 255, 255, 0.88) !important;
        margin: 0 !important;
        border: none !important;
        transform: none !important;
    }

    /* Sub-menu rows: label + "view all" eye icon on ONE line */
    #sidebar-menu ul li ul.sub-menu li {
        display: flex !important;
        align-items: center !important;
        position: relative;
    }

    #sidebar-menu ul li ul.sub-menu li > a:first-child {
        flex: 1 1 auto !important;
        width: auto !important;
        min-width: 0 !important;
        white-space: normal;
        line-height: 1.35;
    }

    #sidebar-menu ul li ul.sub-menu li > span {
        flex: 0 0 auto !important;
        display: flex !important;
        align-items: center !important;
        padding-right: 14px;
    }

    #sidebar-menu ul li ul.sub-menu li > span a {
        width: 30px !important;
        height: 30px !important;
        padding: 0 !important;
        justify-content: center !important;
        border-radius: 6px !important;
    }

    #sidebar-menu ul li ul.sub-menu li > span a i {
        flex: 0 0 auto !important;
        width: auto !important;
        min-width: 0 !important;
        margin: 0 !important;
        font-size: 1.05rem !important;
        justify-content: center !important;
    }

    /* Several <ul class="sub-menu"> inside one item (Inventory etc.): no gaps between groups */
    #sidebar-menu ul li ul.sub-menu {
        padding: 0 !important;
    }
    #sidebar-menu ul li > ul.sub-menu:first-of-type {
        padding-top: 4px !important;
    }
    #sidebar-menu ul li > ul.sub-menu:last-of-type {
        padding-bottom: 4px !important;
    }

    #sidebar-menu ul li ul.sub-menu li a:hover,
    #sidebar-menu ul li ul.sub-menu li a.active {
        color: #ffffff !important;
        background: rgba(255, 255, 255, 0.14) !important;
        border: none !important;
        box-shadow: none !important;
        font-weight: 600 !important;
        transform: none !important;
    }

    .color th, td > button {
        background: var(--finac-primary-dark) !important;
        color: #ffffff !important;
    }

    .col.border-right.bg-color.p-2.text-white.d-block {
        background: var(--finac-dark-base) !important;
        color: #ffffff !important;
    }

    div.bg-soft-success {
        background: var(--finac-gradient-main) !important;
        font-weight: 600 !important;
        color: #ffffff !important;
        border-left: 4px solid #061d38 !important;
        border-radius: 8px !important;
        box-shadow: 0 4px 14px rgba(11, 56, 102, 0.25) !important;
    }

    table.dynamic tbody tr td.text {
        padding: 4px 8px !important;
        background-color: #061d38 !important;
        color: #ffffff !important;
        font-weight: bold;
    }

    body[data-layout="detached"] table.color.dataTable thead th, 
    body[data-layout="detached"] table.dataTable thead td {
        border-bottom-color: #0b3866 !important;
    }

    .input-group-prepend span {
        background: #f2f7f6 !important;
        border: 1px solid var(--finac-border) !important;
        color: #000000 !important;
    }

    .filter-submit-btn {
        background: var(--finac-gradient-btn) !important;
        border: none !important;
        color: #ffffff !important;
        box-shadow: 0 3px 10px rgba(11, 56, 102, 0.25) !important;
    }
    .filter-submit-btn:hover {
        background: var(--finac-gradient-btn-hover) !important;
        box-shadow: 0 5px 15px rgba(11, 56, 102, 0.4) !important;
    }

    .mab {
        background-color: #061d38 !important;
    }

    body[data-layout="detached"] button.color, 
    body[data-layout="detached"] .button.color, 
    body[data-layout="detached"] a.color {
        background: var(--finac-gradient-btn) !important;
        color: #ffffff !important;
        border: none !important;
        box-shadow: 0 3px 10px rgba(11, 56, 102, 0.25) !important;
        border-radius: 6px !important;
    }

    .input-group-text i, .input-group-text {
        color: #0b3866 !important;
    }

    #page-topbar {
        position: fixed;
        top: 0;
        right: 0;
        left: 0;
        z-index: 1002;
        background: transparent !important;
        height: 70px;
    }

    #page-topbar > .container-fluid {
        padding: 0 !important;
        height: 70px !important;
    }

    /* Left zone = sidebar width (280px), right zone = content gutter (20px) */
    .navbar-header {
        display: flex !important;
        justify-content: space-between !important;
        align-items: center !important;
        margin: 0 !important;
        height: 70px !important;
        padding: 0 20px 0 0 !important;
        width: 100% !important;
    }

    .navbar-header > div {
        display: flex !important;
        align-items: center !important;
        height: 70px !important;
    }

    /* Toggle button sits on the same left edge as the sidebar icons (22px) */
    .navbar-header > div:first-child {
        flex: 0 0 280px !important;
        width: 280px !important;
        padding-left: 22px !important;
    }

    body.vertical-collpsed .navbar-header > div:first-child {
        flex: 0 0 70px !important;
        width: 70px !important;
        padding-left: 16px !important;
    }

    /* Right-side chips: one row, equal gaps, no stray margins/baselines */
    .navbar-header > div:last-child {
        justify-content: flex-end !important;
        gap: 10px;
    }

    .navbar-header > div:last-child > .dropdown {
        display: flex !important;
        align-items: center !important;
        height: 38px !important;
        margin: 0 !important;
    }

    .navbar-header > div:last-child > .dropdown.d-none {
        display: none !important;
    }

    @media (min-width: 992px) {
        .navbar-header > div:last-child > .dropdown.d-lg-inline-block {
            display: flex !important;
        }
    }

    .navbar-brand-box {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: flex-start !important;
        height: 70px !important;
        width: auto !important;
        background: transparent !important;
        padding-right: 15px !important;
    }

    .navbar-brand-box .logo {
        display: flex !important;
        align-items: center !important;
        line-height: normal !important;
    }

    .navbar-brand-box .logo img {
        max-height: 42px !important;
        width: auto !important;
        vertical-align: middle !important;
    }

    #vertical-menu-btn {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: 38px !important;
        width: 38px !important;
        padding: 0 !important;
        margin: 0 !important;
        border-radius: 8px !important;
        color: #000000 !important;
        background: #ffffff !important;
        border: 1.5px solid #d4e3e1 !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        cursor: pointer;
        font-size: 16px !important;
        transition: all 0.2s ease !important;
    }

    #vertical-menu-btn:hover {
        background: #f4f8f7 !important;
        color: #0b3866 !important;
        border-color: #0b3866 !important;
    }

    .app-search {
        padding: 0 10px !important;
        margin-bottom: 0 !important;
        display: inline-flex !important;
        align-items: center !important;
    }

    .app-search .position-relative {
        display: flex;
        align-items: center;
    }

    .app-search .form-control {
        background: #ffffff !important;
        border: 1.5px solid #d4e3e1 !important;
        color: #000000 !important;
        border-radius: 20px !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        height: 38px !important;
        line-height: 38px !important;
        padding: 6px 18px !important;
        width: 250px !important;
        font-size: 0.85rem !important;
        transition: all 0.2s ease !important;
    }

    .app-search .form-control::placeholder {
        color: #666666 !important;
    }

    .app-search .form-control:focus {
        border-color: #0b3866 !important;
        box-shadow: 0 0 0 3.5px rgba(11, 56, 102, 0.25) !important;
        background: #ffffff !important;
        outline: none !important;
    }

    .header-item {
        height: 70px !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: none !important;
        border: none !important;
        color: #ffffff !important;
        font-size: 0.86rem !important;
        font-weight: 500 !important;
        padding: 0 14px !important;
        background: transparent !important;
        border-radius: 0 !important;
        transition: background-color 0.2s ease !important;
    }

    /* Distinct Pill Badge for User Profile & Quick Actions in Header */
    .header-chip-btn {
        background: #ffffff !important;
        color: #000000 !important;
        border-radius: 20px !important;
        height: 38px !important;
        padding: 6px 15px !important;
        border: 1.5px solid #d4e3e1 !important;
        font-size: 0.84rem !important;
        font-weight: 600 !important;
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        box-shadow: 0 2px 8px rgba(0, 0, 0, 0.08) !important;
        transition: all 0.2s ease !important;
        line-height: 1 !important;
        white-space: nowrap;
        margin: 0 !important;
    }

    .header-chip-btn i {
        color: #0b3866 !important;
        display: inline-flex !important;
        align-items: center !important;
        font-size: 17px !important;
        line-height: 1 !important;
        margin-top: 0 !important;
        vertical-align: middle !important;
    }

    .header-chip-btn span {
        line-height: 1 !important;
    }

    .header-chip-btn:hover,
    .header-chip-btn:focus {
        background: #f4f8f7 !important;
        color: #0b3866 !important;
        border-color: #0b3866 !important;
        box-shadow: 0 4px 12px rgba(11, 56, 102, 0.2) !important;
    }

    .dropdown.loading {
        display: inline-flex !important;
        align-items: center !important;
        justify-content: center !important;
        height: 70px !important;
        color: #ffffff !important;
        font-size: 1.2rem !important;
        padding: 0 8px !important;
    }
</style>

<head>
    <meta charset="utf-8" />
    <title>Dashboard | BLIZTECH</title>
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta content="Cloud Based Complete ERP Solution" name="description" />
    <meta content="3DM Solutions" name="author" />

    <!-- App favicon -->
    <link rel="shortcut icon" type="image/png" href="<?=$SiteURL?>assets/images/blitztech2.png">
    <!-- jquery.vectormap css -->
    <link href="<?=$SiteURL?>assets/libs/admin-resources/jquery.vectormap/jquery-jvectormap-1.2.2.css" rel="stylesheet" type="text/css" />

    <!-- Bootstrap Css -->
    <link href="<?=$SiteURL?>assets/css/bootstrap.css" id="bootstrap-style" rel="stylesheet" type="text/css" />
    <!-- Icons Css -->
    <link href="<?=$SiteURL?>assets/css/icons.css" rel="stylesheet" type="text/css" />
    <!-- App Css-->
    <link href="<?= $SiteURL ?>assets/css/app.css?v=2.9" id="app-style" rel="stylesheet" type="text/css"/>
    <!-- DataTables -->
    <link href="<?=$SiteURL?>assets/libs/datatables.net-bs4/css/dataTables.bootstrap4.min.css" rel="stylesheet" type="text/css" />
    <link href="<?=$SiteURL?>assets/libs/select2/css/select2.min.css" rel="stylesheet" type="text/css" />
    <!-- Custom Css -->
    <link href="<?=$SiteURL?>assets/css/custom.css?v=4" rel="stylesheet" type="text/css" />
    <!-- FINAC redesign (loads last) -->
    <link href="<?=$SiteURL?>assets/css/finac-theme.css?v=12" rel="stylesheet" type="text/css" />
    <!-- Global app font: Plus Jakarta Sans (loads last) -->
    <link href="<?=$SiteURL?>assets/css/app-font.css?v=1" rel="stylesheet" type="text/css" />
    <link href="<?=$SiteURL?>assets/css/header-v2.css?v=1" rel="stylesheet" type="text/css" />
    <!-- Sweet Alert -->
    <link href="<?=$SiteURL?>assets/libs/sweetalert2/src/sweetalert2.scss" rel="stylesheet" type="text/css" />

    <!-- JAVASCRIPT -->
    <script src="<?=$SiteURL?>assets/libs/jquery/jquery.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/bootstrap/js/bootstrap.bundle.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/metismenu/metisMenu.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/simplebar/simplebar.min.js"></script>
    <script src="<?=$SiteURL?>assets/js/jquery-ui.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/autonumeric/autonumeric.js"></script>
    <script src="<?=$SiteURL?>assets/libs/select2/js/select2.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/bootstrap-datepicker/js/bootstrap-datepicker.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/sweetalert2/dist/sweetalert2.js"></script>
    <script src="<?=$SiteURL?>assets/libs/sweetalert2/dist/sweetalert2.all.js"></script>

    <!-- Required datatable js -->
    <script src="<?=$SiteURL?>assets/libs/datatables.net/js/jquery.dataTables.min.js"></script>
    <script src="<?=$SiteURL?>assets/libs/datatables.net-bs4/js/dataTables.bootstrap4.min.js"></script>
    <script src="<?=$SiteURL?>js/fnLengthChange.js"></script>

    <script src="<?=$SiteURL?>assets/js/app.js"></script>

    <? Autocomplete::getJS(); ?>

    <script src="<?=$SiteURL?>assets/js/stock-row.js"></script>
    <script src="<?= $SiteURL ?>assets/js/finac-app.js?v=2"></script>
    <script src="<?= $SiteURL ?>assets/js/table.dynamic.js?v=1"></script>
    <script src="<?= $SiteURL ?>assets/js/jquery.table2excel.min.js"></script>
    <script src="<?= $SiteURL ?>assets/js/ai.js"></script>
    <script src="<?=$SiteURL?>assets/libs/apexcharts/apexcharts.min.js"></script>
    <script src="<?=$SiteURL?>js/voucher_helper.js"></script>


    <script type="text/javascript">
        SiteURL = '<?=$SiteURL?>';
        voucher_type_id = '<?=$_GET['voucher_type_id']?>';
        voucher_number = '<?=$_GET['__voucher_number']?>';
        distinct_id = '<?=$_GET['__voucher_distinct_id']?>';
        is_edit = '<?=$_GET['__is_edit']?>';

        var branch_id = '<?=$_SESSION['__branch_id']?>';
        var branch_name = '<?=Branch::getName($_SESSION['__branch_id'])?>';
        var branch_disable = '<?=$oHeaderBranch->isBranchDisabled()?>';

    </script>
</head>

<body data-layout="detached" data-topbar="colored" class="<?=Utils::getThemeClass()?>">

<!-- Begin page -->
<div id="layout-wrapper">
    <header id="page-topbar">
        <div class="container-fluid">
            <div class="navbar-header">
                <div class="d-flex align-items-center">
                    <!-- LOGO -->
                    <a href="<?=$SiteURL?>index.php" class="finac-brand" title="Go to dashboard">
                        <span class="finac-brand-mark" aria-hidden="true">
                            <svg viewBox="0 0 24 24" width="20" height="20"><path d="M13.5 2 5 13.2h5.6L9.5 22 19 9.8h-5.7z" fill="#0b3866"/></svg>
                        </span>
                        <span class="finac-brand-text">BLITZTECH</span>
                    </a>

                    <button type="button" class="btn btn-sm header-item toggle-btn waves-effect" id="vertical-menu-btn" title="Show / hide menu" aria-label="Show or hide menu">
                        <i class="mdi mdi-menu"></i>
                    </button>
                </div>

                <!-- Search -->


                <div class="d-flex align-items-center hdr-right">
                   

                    <button type="button" class="hdr-icon-btn d-none d-md-inline-flex" id="hdr-fullscreen" title="Full screen" aria-label="Toggle full screen">
                        <i class="mdi mdi-fullscreen"></i>
                    </button>

                    <div class="dropdown d-inline-block">
                        <button type="button" class="hdr-user" id="page-header-user-dropdown" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                            <span class="hdr-avatar"><?=htmlspecialchars(strtoupper(substr(SecurityShell::getLoggedInUsername(), 0, 1)))?></span>
                            <span class="hdr-user-name d-none d-md-inline"><?=SecurityShell::getLoggedInUsername()?></span>
                            <i class="mdi mdi-chevron-down"></i>
                        </button>
                        <div class="dropdown-menu dropdown-menu-right hdr-menu">
                            <div class="hdr-menu-head">
                                <span class="hdr-avatar hdr-avatar-lg"><?=htmlspecialchars(strtoupper(substr(SecurityShell::getLoggedInUsername(), 0, 1)))?></span>
                                <div>
                                    <b><?=SecurityShell::getLoggedInUsername()?></b>
                                    <small><?=Branch::getName($_SESSION['__branch_id'])?> &middot; <?=Year::getYearName()?></small>
                                </div>
                            </div>
                            <div class="dropdown-divider"></div>
                            <a class="dropdown-item hdr-logout" href="<?=$SiteURL?>security_shell/op_logout.php"><i class="mdi mdi-logout-variant"></i> Log out</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </header>
    <script>
        document.getElementById('hdr-fullscreen').addEventListener('click', function () {
            var ic = this.querySelector('i');
            if (!document.fullscreenElement) {
                document.documentElement.requestFullscreen && document.documentElement.requestFullscreen();
                ic.className = 'mdi mdi-fullscreen-exit';
            } else {
                document.exitFullscreen && document.exitFullscreen();
                ic.className = 'mdi mdi-fullscreen';
            }
        });
    </script> <!-- ========== Left Sidebar Start ========== -->
    <div class="vertical-menu">

        <div class="h-100 pb-5">

            <!--<div class="user-wid text-center py-4">
                <div class="mt-3">

                    <div href="#" class="text-light font-weight-medium font-size-16">PSG Software</div>
                </div>
            </div>-->

            <!-- Sidemenu -->
            <div id="sidebar-menu" class="pt-4 pb-5">
                <!-- Left Menu Start -------------------------------------------------------------------------------------------------------------------------------------------------------->
                <ul class="metismenu list-unstyled" id="side-menu">
                    <li class="menu-title"> Quick Links</li>
                    <li><a href="<?=$SiteURL?>index.php"><i class="mdi mdi-view-dashboard-outline"></i>Dashboard</a></li>
                    <? if (CompanyModule::isModuleActive(CompanyModule::CRM)) { ?>
<!--                        <li><a href="--><?//= $SiteURL ?><!--dashboard/crm.php"><i class="mdi mdi-account-tie-outline"></i>CRM</a></li>-->
                    <? } if (CompanyModule::isModuleActive(CompanyModule::SALES)) { ?>
<!--                    <li><a href="--><?//=$SiteURL?><!--sales/sales_voucher.php?voucher_type_id=2"><i class="mdi mdi-cash-register"></i>Sales Invoice Voucher</a></li>-->
                    <? } if (CompanyModule::isModuleActive(CompanyModule::PURCHASE)) { ?>
<!--                    <li><a href="--><?//=$SiteURL?><!--purchase/purchase_voucher.php?voucher_type_id=1"><i class="mdi mdi-cart-arrow-down"></i>Purchase Invoice Voucher</a></li>-->

                    <? } if (CompanyModule::isModuleActive(CompanyModule::PROJECT)) { ?>
<!--                    <li class="menu-title">Project Management</li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-briefcase-variant-outline"></i>-->
<!--                            <span>Project</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::PM, '', false, 'Create New'); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-file-percent-outline"></i>-->
<!--                            <span>Quotation</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::QTV, '', false,"Create New"); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-calculator"></i>-->
<!--                            <span>Estimate</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuDefaultReport(VoucherType::QTEV,"View All Estimates"); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-account-clock-outline"></i>-->
<!--                            <span>Employee WorkTime</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::EWV, '', false,"Create Nem"); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-arrow-decision-outline"></i>-->
<!--                            <span>Stock Requisition</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::STRV,'', false,'New Requisition '); ?>
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::STRRV, '', false,'New Requisition Return'); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-truck-delivery"></i>-->
<!--                            <span>Delivery Challan</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuReport("Delivery Challan", 'sales/report_project_sales.php?voucher_type_id='.VoucherType::SIV.'&invoice_type=D', '', false, CompanyModule::PROJECT, 'Delivery Challan'); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-receipt-text-outline"></i>-->
<!--                            <span>Project Invoices</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuReport('View All', "sales/report_project_sales.php?voucher_type_id=".VoucherType::SIV.'&invoice_type=I', '', false, CompanyModule::PROJECT, 'Project Invoices'); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-bank-transfer"></i>-->
<!--                            <span>Project-Wise Payment</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuReport("Bulk Project-Wise Payment", "project/bulk_project_wise_payment.php?voucher_type_id=".VoucherType::PWPV); ?>
<!--                            --><?// $oHeaderUtils->menuDefaultReport(VoucherType::PWPV,'View All'); ?>
<!--                        </ul>-->
<!--                    </li>-->
                    <? } ?>
                    <? if (CompanyModule::isModuleActive(CompanyModule::CRM)) { ?>
<!--                    <li class="menu-title">CRM</li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-account-question-outline"></i>-->
<!--                            <span>Inquiries</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::CRMI); ?>
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::CRSP); ?>
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::CRIT); ?>
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::CCRD); ?>
<!--                            --><?// $oHeaderUtils->menuVoucherType(VoucherType::CRMM); ?>
<!--                        </ul>-->
<!--                    </li>-->
                    <? } ?>

                    <li class="menu-title">Components</li>
                    <? if (CompanyModule::isModuleActive(CompanyModule::CHART_OF_ACCOUNT)) { ?>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="mdi mdi-sitemap"></i>
                                <span>Chart of Account</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <? $oHeaderUtils->menuReport("Categories", "chart_of_account/manage_coa_categories.php", "", false, CompanyModule::CHART_OF_ACCOUNT, "Categories Management"); ?>
                                <? $oHeaderUtils->menuReport('Transaction Account', 'chart_of_account/manage_accounts.php?is_employee=N','chart_of_account/report_transaction_accounts.php?is_employee=N', false, CompanyModule::CHART_OF_ACCOUNT, 'Transaction Account'); ?>
                            </ul>
                        </li>
                    <? } ?>
                    <? if (CompanyModule::isModuleActive(CompanyModule::STOCK)) { ?>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="mdi mdi-warehouse"></i>
                                <span>Inventory</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <? $oHeaderUtils->menuReport('Products', 'stock/stock_management.php', 'stock/report_stock.php', false, CompanyModule::STOCK, 'Item Profile'); ?>
                                <? $oHeaderUtils->menuReport('Category', 'stock_category/manage_stock_category.php', '', false, CompanyModule::STOCK, 'Category'); ?>
                                <? $oHeaderUtils->menuReport('Attribute', 'stock_attribute/stock_attr_manager.php', '', false, CompanyModule::STOCK, 'Attribute'); ?>
                                <? $oHeaderUtils->menuReport('Branches', 'branch/branch_manager.php', 'branch/report_branch.php', false, CompanyModule::STOCK, 'Branch'); ?>
                            </ul>
                            <ul class="sub-menu" aria-expanded="false">
                                <? $oHeaderUtils->menuReport('Stock Manufacturing', 'manufacturing/pre_manufacture.php', VoucherType::getDefaultReportLink(VoucherType::BMV), false, ); ?>
                                <? $oHeaderUtils->menuVoucherType(VoucherType::UPV, '', false, "Stock Unpacking"); ?>
                                <? $oHeaderUtils->menuVoucherType(VoucherType::MFSR, '', false, "Stock Recipes"); ?>
                                <? $oHeaderUtils->menuVoucherType(VoucherType::STDV, '', false, "Stock Dispose"); ?>
                                <? $oHeaderUtils->menuVoucherType(VoucherType::SMV, '', false, "Stock Movement"); ?>
                            </ul>
                        </li>
                    <? } ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-trending-up"></i>
                            <span>Sales</span>
                        </a>
                        <? if (CompanyModule::isModuleActive(CompanyModule::SALES)) { ?>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuVoucherType(VoucherType::SIV, 'sales/report_sales.php?voucher_type_id='.VoucherType::SIV, false, "Invoices"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::SRV, 'sales/report_sales.php?voucher_type_id='.VoucherType::SRV, false, "Return"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::SOV, 'sales/report_sales.php?voucher_type_id='.VoucherType::SOV, false, "Orders"); ?>
                        </ul>
                        <? } ?>

                        <? if (CompanyModule::isModuleActive(CompanyModule::CHART_OF_ACCOUNT)) { ?>
                            <ul class="sub-menu" aria-expanded="false">
                                <? $oHeaderUtils->menuReport('Customers', 'chart_of_account/manage_accounts.php?is_employee=N&coa_id='.getConstantCategory()->getCustomer(), 'chart_of_account/report_transaction_accounts.php?is_employee=N&coa_id='.getConstantCategory()->getCustomer(), false, CompanyModule::CHART_OF_ACCOUNT, 'Transaction Account'); ?>
                                <? $oHeaderUtils->menuReport('Sales Person', 'chart_of_account/manage_accounts.php?is_employee=N&coa_id='.getConstantCategory()->getSalesMan(),'chart_of_account/report_transaction_accounts.php?is_employee=N&coa_id='.getConstantCategory()->getSalesMan(), false, CompanyModule::CHART_OF_ACCOUNT, 'Transaction Account'); ?>
                            </ul>
                        <? } ?>

                    </li>

                    <? if (CompanyModule::isModuleActive(CompanyModule::SALES) and SecurityShell::isVoucherAccess(VoucherType::OIV)) { ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-point-of-sale"></i>
                            <span>POS</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuVoucherType(VoucherType::OIV, 'sales/report_sales.php?voucher_type_id='.VoucherType::OIV, false, "Order Invoice"); ?>
                        </ul>
                    </li>
                    <? } ?>

                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-silverware-fork-knife"></i>
                            <span>Restaurant</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport('Web Orders', 'restaurant/manage_web_orders.php'); ?>
                            <? $oHeaderUtils->menuReport('Website Menu', 'restaurant/manage_menu.php'); ?>
                            <? $oHeaderUtils->menuReport('Deals', 'restaurant/deals.php'); ?>
                            <? $oHeaderUtils->menuReport('Deals Report', 'restaurant/deals_report.php'); ?>
                            <? $oHeaderUtils->menuReport('Delivery Areas', 'restaurant/delivery_areas.php'); ?>
                        </ul>
                    </li>

                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-basket-outline"></i>
                            <span>Purchase</span>
                        </a>
                        <? if (CompanyModule::isModuleActive(CompanyModule::PURCHASE)) { ?>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuVoucherType(VoucherType::PIV, 'purchase/report_purchase.php?voucher_type_id='.VoucherType::PIV, false,"Invoices"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::PRV, 'purchase/report_purchase.php?voucher_type_id='.VoucherType::PRV, false, "Return"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::POV, 'purchase/report_purchase.php?voucher_type_id='.VoucherType::POV, false, "Orders"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::PR, '', false, "Requisitions"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::OPS, 'purchase/report_purchase.php?voucher_type_id='.VoucherType::OPS, false, "Opening Stock"); ?>
                        </ul>
                        <? } ?>



                        <? if (CompanyModule::isModuleActive(CompanyModule::CHART_OF_ACCOUNT)) { ?>
                            <ul class="sub-menu" aria-expanded="false">
                                <? $oHeaderUtils->menuReport('Suppliers', 'chart_of_account/manage_accounts.php?is_employee=N&coa_id='.getConstantCategory()->getSupplier(), 'chart_of_account/report_transaction_accounts.php?is_employee=N&coa_id='.getConstantCategory()->getSupplier(), false, CompanyModule::CHART_OF_ACCOUNT, 'Transaction Account'); ?>
                            </ul>
                        <? } ?>
                    </li>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-basket"></i>
                            <span>Stock Use</span>
                        </a>

                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuVoucherType(VoucherType::SU, '', false, "Stock Use"); ?>
                        </ul>
                    </li>

                    <? if (CompanyModule::isModuleActive(CompanyModule::PAYMENT_RECEIPTS)) { ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-swap-horizontal"></i>
                            <span>Payment/Receipts</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuVoucherType(VoucherType::CPV, '', false, "Cash Payment"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::BPV, '', false, "Bank Payment"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::CRV, '', false,"Cash Receipts"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::BRV, '', false, "Bank Receipts"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::CQV, '', false, "Cheque Receipts"); ?>
                        </ul>
                    </li>
                    <? } if (CompanyModule::isModuleActive(CompanyModule::PAYMENT_RECEIPTS)) { ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-cash-minus"></i>
                            <span>Expenses</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuVoucherType(VoucherType::CPV, '', false, "Cash Expenses"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::BPV, '', false, "Bank Expenses"); ?>

                        </ul>
                    </li>
                    <? } ?>

                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-file-document-edit-outline"></i>
                            <span>Other Vouchers</span>
                        </a>
                        <? if (CompanyModule::isModuleActive(CompanyModule::OTHER_VOUCHERS)) { ?>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuVoucherType(VoucherType::JV, '', false, "Journal Vouchers"); ?>
                            <? $oHeaderUtils->menuVoucherType(VoucherType::OPA, '', false, "Accounts Opening"); ?>
                        </ul>
                        <? } if (CompanyModule::isModuleActive(CompanyModule::BANK_STATEMENT)) { ?>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuVoucherType(VoucherType::BSIV, '', false, "Bank Statements"); ?>
                        </ul>
                        <? } ?>
                    </li>

                    <? if (CompanyModule::isModuleActive(CompanyModule::HR)) { ?>
<!--                    <li class="menu-title">HR Management</li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-account-group-outline"></i>-->
<!--                            <span>Employee</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuReport('Create/Update','chart_of_account/manage_accounts.php?is_employee=Y&coa_id='.getConstantCategory()->getEmployee(),'chart_of_account/report_transaction_accounts.php?is_employee=Y&coa_id='.getConstantCategory()->getEmployee(),false, CompanyModule::CHART_OF_ACCOUNT, 'Transaction Account'); ?>
<!--                            --><?// $oHeaderUtils->menuReport('Bulk Employee Update','chart_of_account/manage_employees.php', false, CompanyModule::HR, 'Can Change Employee Data'); ?>
<!--                            --><?// $oHeaderUtils->menuReport('Salary History','chart_of_account/report_salary_history.php',false, CompanyModule::HR, 'Salary History'); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-calendar-check-outline"></i>-->
<!--                            <span>Attendance Sheet</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu" aria-expanded="false">-->
<!--                            --><?// $oHeaderUtils->menuReport('Month Logs', 'attendance_sheet/manage_logs.php','attendance_sheet/report_logs.php',false, CompanyModule::HR, 'Attendance Sheet'); ?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-hand-holding-usd"></i>-->
<!--                            <span>Loan / Advances</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu">-->
<!--                            --><?// $oHeaderUtils->menuReport('Loan','loan/manage_loan.php?type=L&voucher_type_id='.VoucherType::LV,'loan/report_loan.php?type=L',false, CompanyModule::HR, 'Loan'); ?>
<!--                            --><?// $oHeaderUtils->menuReport('Advances','loan/manage_loan.php?type=A&voucher_type_id='.VoucherType::LV,'loan/report_loan.php?type=A',false, CompanyModule::HR, 'Advances'); ?>
<!--                            --><?// $oHeaderUtils->menuReport('Employee Wise Loan','loan/report_all_employee_loan.php',false, CompanyModule::HR, 'Employee Wise Loan Report');?>
<!--                        </ul>-->
<!--                    </li>-->
<!--                    <li>-->
<!--                        <a href="javascript: void(0);" class="has-arrow waves-effect">-->
<!--                            <i class="mdi mdi-cash-register"></i>-->
<!--                            <span>Salary Sheet</span>-->
<!--                        </a>-->
<!--                        <ul class="sub-menu">-->
<!--                            --><?// $oHeaderUtils->menuReport("Create New", "salary/salary_log_selection.php?voucher_type_id=".VoucherType::SM, "salary/report_salary.php", false,CompanyModule::HR,'Salary Sheet'); ?>
<!--                        </ul>-->
<!--                    </li>-->
                    <? } ?>
                    <li class="menu-title">Reports</li>
                    <? if (CompanyModule::isModuleActive(CompanyModule::SALES)) { ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-chart-timeline-variant"></i>
                            <span>Sales Reports</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("Avg Price & Qty", "sales/report_avg.php",'',false,CompanyModule::SALES, 'Avg Price & Qty'); ?>
                            <? $oHeaderUtils->menuReport("Datewise Sales", "sales/daily_report.php",'',false,CompanyModule::SALES, 'Datewise Sales Report'); ?>
                            <? $oHeaderUtils->menuReport("Brand/Category Wise Sales", "sales/report_stock_brand_category.php",'',false,CompanyModule::SALES, 'Brand/Category Wise Sales'); ?>
                        </ul>
                    </li>
                    <? } ?>

                    <? if (CompanyModule::isModuleActive(CompanyModule::PURCHASE)) { ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-chart-bar"></i>
                            <span>Purchase Reports</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("Avg Price & Qty", "purchase/report_avg.php",'',false,CompanyModule::PURCHASE, 'Avg Price & Qty Report'); ?>
                            <? $oHeaderUtils->menuReport("Datewise Purchases", "purchase/daily_report.php",'',false,CompanyModule::PURCHASE, 'Datewise Purchase Report'); ?>
                            <? $oHeaderUtils->menuReport("Brand/Category Wise Purchase", "purchase/report_stock_brand_category.php",'',false,CompanyModule::PURCHASE, 'Brand/Category Wise Report'); ?>
                        </ul>
                    </li>
                    <? } ?>

                    <? if (CompanyModule::isModuleActive(CompanyModule::STOCK)) { ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-chart-donut-variant"></i>
                            <span>Stock Reports</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("Stock Ledger", "stock_report/stock_ledger.php", "", false, CompanyModule::STOCK, "Stock Ledger"); ?>
                            <? $oHeaderUtils->menuReport("Inventory Valuation", "stock_report/inventory_valuation_summary.php", "", false, CompanyModule::STOCK, "Inventory Valuation"); ?>
                            <? $oHeaderUtils->menuReport("Price List", "stock_report/stock_price_list.php", "", false, CompanyModule::STOCK, "Price List"); ?>
                            <? $oHeaderUtils->menuReport("Stock Holding", "stock_report/holding.php", "", false, CompanyModule::STOCK, "Stock Holding"); ?>
                            <? $oHeaderUtils->menuReport("Stock Overview", "stock_report/overview.php", "", false, CompanyModule::STOCK, "Stock Overview"); ?>
                            <? $oHeaderUtils->menuReport("Branch-wise Full Stock", "stock_report/branch_wise_full_stock.php", "", false, CompanyModule::STOCK, "Branch-wise Full Stock"); ?>
                            <? $oHeaderUtils->menuReport("Avg Profit", "stock_report/profit.php", "", false, CompanyModule::STOCK, "Avg Profit"); ?>
                            <? $oHeaderUtils->menuReport("Reorder Stock", "stock_report/reorder.php", "", false, CompanyModule::STOCK, "Reorder Stock"); ?>
                            <? $oHeaderUtils->menuReport("Brand / Category Wise Qty", "stock_report/brand_category.php", "", false, CompanyModule::STOCK, "Brand / Category Wise Qty"); ?>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("MIS", "stock_report/mis.php", "", false, CompanyModule::STOCK, "MIS"); ?>
                            <? $oHeaderUtils->menuReport("Monthly Profit", "stock_report/monthly_profit.php", "", false, CompanyModule::STOCK, "Monthly Profit"); ?>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("Zero Stock Ledger", "stock_report/zero_stock_ledger.php", "", false, CompanyModule::STOCK, "Zero Stock Ledger"); ?>
                            <? $oHeaderUtils->menuReport("Stock Batch Transitions", "stock_report/batch_transitions.php", "", false, CompanyModule::STOCK, "Stock Batch Transitions"); ?>
                            <? $oHeaderUtils->menuReport("Deleted Batches Sales", "stock_report/deleted_batches_sales.php", "", false, CompanyModule::STOCK, "Deleted Batches Sales"); ?>
                            <? $oHeaderUtils->menuReport("Qty Not Adjusted Sales", "stock_report/qty_not_adjusted.php", "", false, CompanyModule::STOCK, "Qty Not Adjusted Sales"); ?>
                        </ul>
                    </li>
                    <? } ?>

                    <? if (CompanyModule::isModuleActive(CompanyModule::ACCOUNTING)) { ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-scale-balance"></i>
                            <span>Accounting Report</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("Ledger Report", "accounting_reports/ledger.php", "", false,CompanyModule::ACCOUNTING, "Account Ledger"); ?>
                            <? $oHeaderUtils->menuReport("Category Ledger Balance", "accounting_reports/category_balance.php", "", false, CompanyModule::ACCOUNTING, "Category Ledger Balance"); ?>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("Detailed Customer Aging", "accounting_reports/aging.php?type=customer", "", false, CompanyModule::ACCOUNTING, "Customer Aging"); ?>
                            <? $oHeaderUtils->menuReport("Detailed Supplier Aging", "accounting_reports/aging.php?type=supplier", "", false, CompanyModule::ACCOUNTING, "Supplier Aging"); ?>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("Sum. Customer Aging", "accounting_reports/aging_summarize.php?type=customer", "", false,CompanyModule::ACCOUNTING, "Customer Aging"); ?>
                            <? $oHeaderUtils->menuReport("Sum. Supplier Aging", "accounting_reports/aging_summarize.php?type=supplier", "", false, CompanyModule::ACCOUNTING, "Supplier Aging"); ?>
                        </ul>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport("General Journal", "accounting_reports/general_journal.php", "", false, CompanyModule::ACCOUNTING, "General Journal"); ?>
                            <? $oHeaderUtils->menuReport("Cash Flow", "accounting_reports/cashflow.php", "", false, CompanyModule::ACCOUNTING, "Cash Flow"); ?>
                            <? $oHeaderUtils->menuReport("Income Statement", "accounting_reports/income_statement.php", "", false, CompanyModule::ACCOUNTING, "Income Statement"); ?>
                            <? $oHeaderUtils->menuReport("Trial Balance", "accounting_reports/trial_balance.php", "", false, CompanyModule::ACCOUNTING, "Trial Balance"); ?>
                            <? $oHeaderUtils->menuReport("Balance Sheet", "accounting_reports/balance_sheet.php", "", false, CompanyModule::ACCOUNTING, "Balance Sheet"); ?>
                        </ul>
                    </li>
                    <? } ?>

                    <? if (CompanyModule::isModuleActive(CompanyModule::CUSTOMIZED_REPORTS)) { ?>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-table-large"></i>
                            <span>Customized Reports</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->customizedReports(); ?>
                        </ul>
                        <? if (SecurityShell::isAccess(CompanyModule::CHART_OF_ACCOUNT, "Can Create Custom Reports")) { ?>
                            <ul class="sub-menu" aria-expanded="false">
                                <? $oHeaderUtils->newCustomizedReport(); ?>
                            </ul>
                        <? } ?>
                    </li>
                    <? } ?>
                    <? if (ProjectHelper::HAS_COST_CENTER && CompanyModule::isModuleActive('Settings') && SecurityShell::isAccess('Settings', 'Cost Center')) { ?>
                    <li class="menu-title">Settings</li>
                    <li>
                        <a href="javascript: void(0);" class="has-arrow waves-effect">
                            <i class="mdi mdi-cog-outline"></i>
                            <span>Settings</span>
                        </a>
                        <ul class="sub-menu" aria-expanded="false">
                            <? $oHeaderUtils->menuReport('Cost Centers', 'costcenter/cost_center_manager.php', 'costcenter/report_cost_center.php', false, 'Settings', 'Cost Center'); ?>
                        </ul>
                    </li>
                    <? } ?>
                    <li class="menu-title">Others</li>
                    <? if (CompanyModule::isModuleActive(CompanyModule::SECURITY_SHELL)) { ?>
                        <li>
                            <a href="javascript: void(0);" class="has-arrow waves-effect">
                                <i class="mdi mdi-shield-lock-outline"></i>
                                <span>Security Shell</span>
                            </a>
                            <ul class="sub-menu" aria-expanded="false">
                                <? $oHeaderUtils->menuReport("Users", "security_shell/user_management.php", "security_shell/view_users.php", false, CompanyModule::SECURITY_SHELL, 'Users'); ?>
                                <? $oHeaderUtils->menuReport("User Groups","security_shell/group_management.php", "security_shell/view_groups.php", false, CompanyModule::SECURITY_SHELL, 'Groups'); ?>
                            </ul>
                        </li>
                    <? } ?>
                </ul>
            </div>
            <!-- Sidebar -->
        </div>
    </div>
    <!-- Left Sidebar End -->

    <!-- ============================================================== -->
    <!-- Start right Content here -->
    <!-- ============================================================== -->

    <div class="container-fluid right-panel">
        <div class="main-content">

            <div class="page-content">
                <!-- start page title -->
                <div class="row">
                    <div class="col-12">
                        <form class="app-search d-block d-lg-none">
                            <div class="position-relative">
                                <input type="text" data-type="erp-search" class="form-control" placeholder="Search...">
                            </div>
                        </form>
                    </div>
                    <div class="col-12">
                        <div class="page-title-box d-block d-sm-flex align-items-center justify-content-between">
                            <h4 class="page-title mb-0 font-size-18"><?=$_GET['_page_title']?></h4>

                            <div class="page-title-right d-none d-sm-flex">
                                <ol class="breadcrumb m-0">
                                    <li class="breadcrumb-item active"><?=$_GET['_page_title_text']?></li>
                                </ol>
                            </div>

                        </div>
                    </div>
                </div>

                <div class="row">
                    <? while ($RowNotification = DBQM::fetch_assoc($SelectFullDayNotification)) { ?>
                        <div class="col-12">
                            <div class="p-1 mb-2 bg-soft-info pl-2 pr-2 rounded"><strong><?=$RowNotification['title']?>: </strong><?=$RowNotification['message']?></div>
                        </div>
                    <? } ?>
                </div>
                <!-- end page title -->
                <? $oLinkedVoucherTabs->getTabs(); ?>

