<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Web Orders';
$_GET['_page_title_text'] = 'Orders placed on the restaurant website';

include_once("../includes/inc_header.php");

$ActiveTab = 'orders';
include_once(__DIR__ . '/_tabs.php');

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }

$Filter = $_GET['filter'] ?? RestaurantOrder::FILTER_ACTIVE;
$Type = $_GET['type'] ?? '';
$Counts = RestaurantOrder::countByFilter();
$SelectOrders = RestaurantOrder::getOrders($Filter, $Type);

// Status chips: key => [label, icon]. Received / Preparing / On the way are all under Active (status shows on each card)
$StatusChips = [
    RestaurantOrder::FILTER_ACTIVE => ['Active', 'mdi-clock-outline'],
    RestaurantOrder::STATUS_COMPLETED => ['Completed', 'mdi-check-circle-outline'],
    RestaurantOrder::STATUS_CANCELLED => ['Cancelled', 'mdi-close-circle-outline'],
    '' => ['All', 'mdi-format-list-bulleted'],
];
$TypeChips = [
    '' => ['All types', 'mdi-format-list-bulleted'],
    'delivery' => ['Delivery', 'mdi-moped'],
    'pickup' => ['Pickup', 'mdi-shopping-outline'],
];

// Keeps the other filter when one is clicked
function filterUrl($Status, $Type) { return 'manage_web_orders.php?filter=' . urlencode($Status) . '&type=' . urlencode($Type); }
?>
<style>
    .order-filters .chip { display: inline-flex; align-items: center; gap: 6px; border: 1px solid #e3e6ec; background: #fff; color: #444; border-radius: 999px; padding: 6px 12px; font-size: 13px; text-decoration: none; margin: 0 6px 6px 0; }
    .order-filters .chip:hover { border-color: #0b3866; color: #0b3866; text-decoration: none; }
    .order-filters .chip.active { background: #0b3866; border-color: #0b3866; color: #fff; }
    .order-filters .chip .count { background: rgba(0,0,0,.08); border-radius: 999px; padding: 0 7px; font-size: 12px; }
    .order-filters .chip.active .count { background: rgba(255,255,255,.25); }
    .order-search { max-width: 320px; }

    /* Status colours: --c1/--c2 gradient, --soft tint, --ink text on the tint */
    .st-received   { --c1: #22c55e; --c2: #059669; --soft: #ecfdf5; --ink: #047857; }
    .st-preparing  { --c1: #fbbf24; --c2: #f97316; --soft: #fff7ed; --ink: #c2410c; }
    .st-on_the_way { --c1: #22d3ee; --c2: #3b82f6; --soft: #eff6ff; --ink: #1d4ed8; }
    .st-completed  { --c1: #a78bfa; --c2: #6366f1; --soft: #f5f3ff; --ink: #4338ca; }
    .st-cancelled  { --c1: #cbd5e1; --c2: #94a3b8; --soft: #f8fafc; --ink: #475569; }

    .order-card { border: 0; border-radius: 16px; background: #fff; box-shadow: 0 4px 18px rgba(15,23,42,.08); overflow: hidden; transition: transform .15s, box-shadow .15s; }
    .order-card:hover { transform: translateY(-2px); box-shadow: 0 10px 26px rgba(15,23,42,.12); }
    .order-card.st-cancelled { opacity: .8; }
    .order-head { background: linear-gradient(135deg, var(--c1), var(--c2)); color: #fff; border-radius: 12px; padding: 12px 14px; }
    .order-head .order-time { color: rgba(255,255,255,.88); font-size: 12px; }
    .order-code { font-size: 18px; font-weight: 700; letter-spacing: .3px; }
    .order-pill { display: inline-flex; align-items: center; gap: 3px; border-radius: 999px; padding: 3px 10px; font-size: 12px; font-weight: 600; white-space: nowrap; }
    .order-pill.status { background: #fff; color: var(--ink); box-shadow: 0 1px 4px rgba(0,0,0,.12); }
    .order-pill.type { background: rgba(255,255,255,.22); color: #fff; border: 1px solid rgba(255,255,255,.45); }
    .order-customer { background: var(--soft); border: 1px solid rgba(0,0,0,.04); border-radius: 12px; padding: 10px 12px; font-size: 13px; }
    .order-customer a { color: var(--ink); font-weight: 600; }
    .order-items td { border-top: 1px dashed #e6e9ef; padding: 7px 4px; vertical-align: top; font-size: 14px; }
    .order-items tr:first-child td { border-top: 0; }
    .order-qty { display: inline-block; min-width: 26px; text-align: center; background: var(--soft); color: var(--ink); border-radius: 6px; padding: 0 5px; font-weight: 700; margin-right: 4px; }
    .order-total td { font-size: 17px; font-weight: 700; background: var(--soft); color: var(--ink); border-top: 0 !important; }
    .order-total td:first-child { border-radius: 10px 0 0 10px; padding-left: 10px; }
    .order-total td:last-child { border-radius: 0 10px 10px 0; padding-right: 10px; }
    .order-footer { background: #fafbfd; border-top: 1px solid #eef0f4; padding: 10px 14px; gap: 8px; flex-wrap: wrap; }
    .order-actions { display: flex; align-items: center; gap: 6px; flex-wrap: nowrap; }
    .order-footer .btn { white-space: nowrap; display: inline-flex; align-items: center; justify-content: center; gap: 4px; line-height: 1.5; }
    .order-icon-btn { width: 36px; height: 36px; padding: 0; border-radius: 10px; font-size: 18px; border: 0 !important; }
    .order-icon-btn.ic-print  { background: #f3e8ff; color: #7e22ce; }
    .order-icon-btn.ic-edit   { background: #dbeafe; color: #1d4ed8; }
    .order-icon-btn.ic-cancel { background: #fee2e2; color: #b91c1c; }
    .order-icon-btn.ic-print:hover  { background: #7e22ce; color: #fff; }
    .order-icon-btn.ic-edit:hover   { background: #1d4ed8; color: #fff; }
    .order-icon-btn.ic-cancel:hover { background: #b91c1c; color: #fff; }
    .order-icon-btn.disabled, .order-icon-btn:disabled { opacity: .35; pointer-events: none; }
    .order-status-form { margin: 0; margin-left: auto; }
    .order-status-btn { min-height: 36px; border-radius: 10px; padding: 4px 14px; font-weight: 600; border: 0; color: #fff !important; background: linear-gradient(135deg, var(--c1), var(--c2)); box-shadow: 0 3px 10px rgba(0,0,0,.15); }
    .order-status-btn:hover { filter: brightness(1.06); }
    .order-locked { font-size: 12px; }

    /* Responsive: tablets / phones */
    @media (max-width: 767.98px) {
        .order-filters .d-flex.align-items-center { width: 100%; flex-wrap: wrap; gap: 6px; margin-top: 4px; }
        .order-search { max-width: none; flex: 1 1 100%; margin-right: 0 !important; }
        .order-code { font-size: 16px; }
        .order-items td { font-size: 13px; }
        #new_order_toast { left: 12px; right: 12px; top: 70px; text-align: center; }
    }
    @media (max-width: 400px) {
        .order-status-form { flex: 1 1 100%; margin-left: 0; }
        .order-status-btn { width: 100%; }
        .order-actions { flex: 1 1 100%; justify-content: space-between; }
        .order-icon-btn { flex: 1 1 0; width: auto; }
    }

    .live-dot { display: inline-block; width: 8px; height: 8px; border-radius: 50%; background: #28a745; margin-right: 4px; animation: live-pulse 2s infinite; }
    .live-dot.offline { background: #adb5bd; animation: none; }
    @keyframes live-pulse { 0%, 100% { opacity: 1; } 50% { opacity: .3; } }
    .order-col.new-order .order-card { box-shadow: 0 0 0 3px #28a745, 0 2px 10px rgba(0,0,0,.06); animation: new-order-flash 1s 3; }
    @keyframes new-order-flash { 50% { box-shadow: 0 0 0 6px rgba(40,167,69,.35); } }
    #new_order_toast { position: fixed; top: 80px; right: 20px; z-index: 2000; background: #28a745; color: #fff; border-radius: 10px; padding: 12px 16px; box-shadow: 0 6px 20px rgba(0,0,0,.2); font-weight: 600; cursor: pointer; display: none; }
</style>

<div id="new_order_toast" title="Click to close"></div>

<div class="card mb-3" id="order_filters_card">
    <div class="card-body pb-2">
        <div class="order-filters mb-1">
            <? foreach ($StatusChips as $Key => [$Label, $Icon]) { ?>
                <a href="<?=filterUrl($Key, $Type)?>" class="chip <?=(string)$Filter === (string)$Key ? 'active' : ''?>">
                    <i class="mdi <?=$Icon?>"></i> <?=h($Label)?>
                    <span class="count"><?=(int)($Counts[$Key] ?? 0)?></span>
                </a>
            <? } ?>
        </div>
        <div class="order-filters d-flex flex-wrap align-items-center justify-content-between">
            <div>
                <? foreach ($TypeChips as $Key => [$Label, $Icon]) { ?>
                    <a href="<?=filterUrl($Filter, $Key)?>" class="chip <?=(string)$Type === (string)$Key ? 'active' : ''?>">
                        <i class="mdi <?=$Icon?>"></i> <?=h($Label)?>
                    </a>
                <? } ?>
            </div>
            <div class="d-flex align-items-center">
                <input type="search" id="order_search" class="form-control form-control-sm order-search mr-2" placeholder="Search code, name, phone, area">
                <span class="text-muted small text-nowrap">
                    <span id="live_status" title="New website orders appear here automatically"><span class="live-dot"></span>Live</span> ·
                    <a href="#" id="sound_toggle" title="Play a sound when a new order arrives"><i class="mdi mdi-volume-high"></i> Sound on</a> ·
                    <a href="manage_menu.php">Website Menu</a>
                </span>
            </div>
        </div>
    </div>
</div>

<div class="row" id="order_list">
    <? $Count = 0; while ($Order = DBQM::fetch_assoc($SelectOrders)) { $Count++;
        $isInvoiced = !empty($Order['erp_sales_id']);
        $isCancelled = $Order['status'] == RestaurantOrder::STATUS_CANCELLED;
        $NextStatus = RestaurantOrder::getNextStatus($Order['status']);
        $isPickup = $Order['order_type'] == 'pickup';
        $CanCancel = !$isInvoiced && !$isCancelled;
        $Items = RestaurantOrder::getItems($Order['id']);
        $SearchText = strtolower($Order['order_code'] . ' ' . $Order['customer_name'] . ' ' . $Order['customer_phone'] . ' ' . $Order['delivery_area']);
    ?>
        <div class="col-12 col-md-6 col-xl-4 mb-3 order-col" data-search="<?=h($SearchText)?>" data-order-id="<?=(int)$Order['id']?>" data-order-code="<?=h($Order['order_code'])?>">
            <div class="card order-card st-<?=h($Order['status'])?> h-100 mb-0">
                <div class="card-body pb-2">
                    <div class="order-head d-flex justify-content-between align-items-start mb-2">
                        <div>
                            <div class="order-code"><?=h($Order['order_code'])?></div>
                            <div class="order-time"><i class="mdi mdi-clock-outline"></i> <?=date('M j, g:i A', strtotime($Order['created_at']))?></div>
                        </div>
                        <div class="text-right">
                            <span class="order-pill status"><?=h(RestaurantOrder::getStatusLabel($Order['status'], $Order['order_type']))?></span>
                            <div class="mt-1">
                                <span class="order-pill type"><i class="mdi <?=$isPickup ? 'mdi-shopping-outline' : 'mdi-moped'?>"></i> <?=$isPickup ? 'Pickup' : 'Delivery'?></span>
                            </div>
                        </div>
                    </div>

                    <div class="order-customer mb-2">
                        <div>
                            <b><?=h($Order['customer_name'])?></b>
                            · <a href="tel:<?=h($Order['customer_phone'])?>"><i class="mdi mdi-phone-outline"></i> <?=h($Order['customer_phone'])?></a>
                        </div>
                        <? if (!$isPickup && $Order['customer_address']) { ?>
                            <div class="mt-1"><i class="mdi mdi-map-marker-outline"></i> <?=h($Order['customer_address'])?>
                                <? if (!empty($Order['delivery_area'])) { ?> <span class="badge badge-soft-info"><?=h($Order['delivery_area'])?></span><? } ?>
                            </div>
                        <? } ?>
                        <? if ($Order['notes']) { ?>
                            <div class="mt-1"><i class="mdi mdi-note-text-outline"></i> <i><?=h($Order['notes'])?></i></div>
                        <? } ?>
                    </div>

                    <table class="table table-borderless table-sm order-items mb-1">
                        <? foreach ($Items as $Item) { ?>
                            <tr>
                                <td>
                                    <span class="order-qty"><?=(int)$Item['qty']?>×</span> <?=h($Item['item_name'])?>
                                    <? if (!empty($Item['deal_id'])) { ?> <span class="badge badge-soft-info">Deal</span><? } ?>
                                    <? foreach (RestaurantOrder::getModifiers($Item['id']) as $Modifier) { ?>
                                        <div class="text-muted small pl-3">+ <?=h($Modifier['name'])?> (<?=number_format($Modifier['price'])?>)</div>
                                    <? } ?>
                                </td>
                                <td class="text-right text-nowrap"><?=number_format($Item['qty'] * $Item['unit_price'])?></td>
                            </tr>
                        <? } ?>
                        <? if ($Order['delivery_fee'] > 0) { ?>
                            <tr><td class="text-muted">Delivery fee</td><td class="text-right text-nowrap"><?=number_format($Order['delivery_fee'])?></td></tr>
                        <? } ?>
                        <tr class="order-total"><td>Total</td><td class="text-right text-nowrap"><?=number_format($Order['total'])?></td></tr>
                    </table>

                    <? if ($isInvoiced) { ?>
                        <div class="text-success small">
                            <? $InvoiceVoucherTypeId = RestaurantOrder::getInvoiceVoucherTypeId($Order['erp_sales_id']); ?>
                            <i class="mdi mdi-check-circle-outline"></i> <?=$InvoiceVoucherTypeId == VoucherType::WIV ? 'Website Invoice' : 'Sales Invoice'?>
                            <a href="../sales/print_voucher.php?id=<?=(int)$Order['erp_sales_id']?>&voucher_type_id=<?=$InvoiceVoucherTypeId?>" target="_blank"><?=VoucherType::getCode($InvoiceVoucherTypeId)?>-<?=(int)$Order['erp_voucher_number']?></a>
                        </div>
                    <? } elseif ($Order['erp_error']) { ?>
                        <div class="alert alert-danger py-1 px-2 small mb-0 mt-1"><i class="mdi mdi-alert-outline"></i> Invoice not created: <?=h($Order['erp_error'])?></div>
                    <? } ?>
                </div>

                <? $isEditable = $CanCancel && RestaurantOrder::isEditable($Order); ?>
                <div class="order-footer d-flex align-items-center">
                    <form method="post" action="op_save_web_order.php" class="m-0 order-actions">
                        <input type="hidden" name="id" value="<?=(int)$Order['id']?>">
                        <button type="button" class="btn btn-sm order-icon-btn ic-print" onclick="printOrder(this)" title="Print order" aria-label="Print order"><i class="mdi mdi-printer"></i></button>
                        <? if (!$isCancelled) { ?>
                            <? if ($isEditable) { ?>
                                <a href="web_order_edit.php?id=<?=(int)$Order['id']?>" class="btn btn-sm order-icon-btn ic-edit" title="Edit order" aria-label="Edit order"><i class="mdi mdi-pencil-outline"></i></a>
                            <? } else { ?>
                                <span class="btn btn-sm order-icon-btn ic-edit disabled" title="Only received, not invoiced orders can be edited" aria-disabled="true"><i class="mdi mdi-pencil-outline"></i></span>
                            <? } ?>
                            <? if ($CanCancel) { ?>
                                <button class="btn btn-sm order-icon-btn ic-cancel" name="status" value="<?=RestaurantOrder::STATUS_CANCELLED?>" onclick="return confirm('Cancel <?=h($Order['order_code'])?>?')" title="Cancel order" aria-label="Cancel order"><i class="mdi mdi-close"></i></button>
                            <? } else { ?>
                                <span class="btn btn-sm order-icon-btn ic-cancel disabled" title="Invoiced orders are cancelled with a sales return in the ERP" aria-disabled="true"><i class="mdi mdi-close"></i></span>
                            <? } ?>
                        <? } ?>
                        <? if ($isInvoiced && !$isCancelled) { ?>
                            <span class="text-muted order-locked text-nowrap" title="Invoiced orders are cancelled with a sales return in the ERP"><i class="mdi mdi-lock-outline"></i> Invoiced</span>
                        <? } ?>
                    </form>
                    <form method="post" action="op_save_web_order.php" class="order-status-form">
                        <input type="hidden" name="id" value="<?=(int)$Order['id']?>">
                        <? if ($NextStatus) { ?>
                            <button class="btn btn-sm order-status-btn st-<?=h($NextStatus)?>" name="status" value="<?=$NextStatus?>" title="Move order to: <?=h(RestaurantOrder::getStatusLabel($NextStatus, $Order['order_type']))?>">
                                <?=$NextStatus == RestaurantOrder::STATUS_COMPLETED ? '<i class="mdi mdi-check"></i> Complete &amp; Invoice' : '<i class="mdi ' . ($NextStatus == RestaurantOrder::STATUS_PREPARING ? 'mdi-chef-hat' : 'mdi-arrow-right') . '"></i> ' . h(RestaurantOrder::getStatusLabel($NextStatus, $Order['order_type']))?>
                            </button>
                        <? } elseif ($Order['status'] == RestaurantOrder::STATUS_COMPLETED && !$isInvoiced) { ?>
                            <button class="btn btn-sm order-status-btn st-completed" name="status" value="<?=RestaurantOrder::STATUS_COMPLETED?>"><i class="mdi mdi-refresh"></i> Retry Invoice</button>
                        <? } ?>
                    </form>
                </div>
            </div>
        </div>
    <? } ?>

    <? if (!$Count) { ?>
        <div class="col-12">
            <div class="card text-center text-muted py-5">
                <i class="mdi mdi-silverware-fork-knife mdi-36px"></i>
                No orders here.
            </div>
        </div>
    <? } ?>
    <div class="col-12 text-center text-muted d-none" id="no_search_results">No orders match your search.</div>
</div>

<script>
function printOrder(btn) {
    var card = btn.closest('.order-card');
    if (!card) return;
    var body = card.querySelector('.card-body').cloneNode(true);
    body.querySelectorAll('.alert').forEach(function (el) { el.remove(); });
    var win = window.open('', '_blank', 'width=380,height=600');
    if (!win) { alert('Please allow pop-ups to print the order.'); return; }
    win.document.write('<!doctype html><html><head><meta charset="utf-8"><title>' + card.querySelector('.order-code').textContent + '</title>' +
        '<style>body{font-family:Arial,sans-serif;font-size:13px;margin:10px;color:#000}' +
        '.order-code{font-size:18px;font-weight:700}.badge{border:1px solid #000;border-radius:4px;padding:1px 5px;font-size:11px;margin-left:4px}' +
        '.d-flex{display:flex;justify-content:space-between}.text-right{text-align:right}.mt-1{margin-top:4px}' +
        '.order-customer{border-top:1px dashed #000;border-bottom:1px dashed #000;padding:6px 0;margin:8px 0}' +
        'table{width:100%;border-collapse:collapse}td{padding:4px 0;vertical-align:top;border-top:1px dashed #ccc}' +
        '.order-total td{font-weight:700;font-size:15px;border-top:1px solid #000}.small,.text-muted{font-size:11px}.pl-3{padding-left:12px}' +
        'a{color:#000;text-decoration:none}.mdi{display:none}</style></head><body>' + body.innerHTML + '</body></html>');
    win.document.close();
    win.focus();
    setTimeout(function () { win.print(); win.close(); }, 250);
}

(function () {
    var search = document.getElementById('order_search');

    function applySearch() {
        var q = search.value.trim().toLowerCase();
        var cols = document.querySelectorAll('.order-col');
        var empty = document.getElementById('no_search_results');
        var shown = 0;
        cols.forEach(function (col) {
            var match = !q || col.getAttribute('data-search').indexOf(q) !== -1;
            col.classList.toggle('d-none', !match);
            if (match) shown++;
        });
        empty.classList.toggle('d-none', shown > 0 || !cols.length);
    }
    search.addEventListener('input', applySearch);

    /* ************* Live updates: poll the orders fingerprint, swap the cards when it changes ************* */
    var POLL_MS = 5000;
    var state = <?=json_encode(RestaurantOrder::getChangeState())?>;
    var liveStatus = document.getElementById('live_status');
    var toast = document.getElementById('new_order_toast');
    var baseTitle = document.title;
    var unseen = 0;

    // Sound: browsers allow audio only after a click on the page, so the context starts on the first click
    var soundOn = true, audio = null;
    try { soundOn = localStorage.getItem('web_orders_sound') !== 'off'; } catch (e) {}
    var soundToggle = document.getElementById('sound_toggle');
    function showSoundState() {
        soundToggle.innerHTML = soundOn ? '<i class="mdi mdi-volume-high"></i> Sound on' : '<i class="mdi mdi-volume-off"></i> Sound off';
    }
    soundToggle.addEventListener('click', function (e) {
        e.preventDefault();
        soundOn = !soundOn;
        try { localStorage.setItem('web_orders_sound', soundOn ? 'on' : 'off'); } catch (e2) {}
        showSoundState();
        if (soundOn) beep();
    });
    document.addEventListener('click', function () {
        if (!audio && (window.AudioContext || window.webkitAudioContext)) audio = new (window.AudioContext || window.webkitAudioContext)();
        if (audio && audio.state === 'suspended') audio.resume();
    });
    function beep() {
        if (!soundOn || !audio) return;
        [880, 660, 880].forEach(function (freq, i) {
            var osc = audio.createOscillator(), gain = audio.createGain();
            osc.frequency.value = freq;
            gain.gain.value = 0.15;
            osc.connect(gain); gain.connect(audio.destination);
            osc.start(audio.currentTime + i * 0.25);
            osc.stop(audio.currentTime + i * 0.25 + 0.18);
        });
    }
    showSoundState();

    function setLive(isLive) {
        liveStatus.innerHTML = '<span class="live-dot' + (isLive ? '' : ' offline') + '"></span>' + (isLive ? 'Live' : 'Reconnecting...');
    }

    // codes = new orders shown on this page; empty when the current filter hides them
    function announce(codes) {
        unseen += Math.max(codes.length, 1);
        document.title = '(' + unseen + ') New order - ' + baseTitle;
        toast.textContent = codes.length > 1 ? codes.length + ' new website orders: ' + codes.join(', ')
            : 'New website order' + (codes.length ? ' ' + codes[0] : ' (not in this filter)');
        toast.style.display = 'block';
        beep();
    }
    function markSeen() { unseen = 0; document.title = baseTitle; }
    toast.addEventListener('click', function () { toast.style.display = 'none'; markSeen(); });
    window.addEventListener('focus', markSeen);
    document.addEventListener('click', markSeen);

    // Re-render the cards and filter counts from the server, keeping the search and the scroll position
    function refreshOrders(previousLatestId) {
        return fetch(location.href, { cache: 'no-store', credentials: 'same-origin', headers: {'X-Background-Request': '1'} })
            .then(function (res) { return res.text(); })
            .then(function (html) {
                var doc = new DOMParser().parseFromString(html, 'text/html');
                var newList = doc.getElementById('order_list'), newFilters = doc.getElementById('order_filters_card');
                if (!newList || !newFilters) return;

                document.getElementById('order_list').innerHTML = newList.innerHTML;
                var filters = document.getElementById('order_filters_card');
                filters.querySelectorAll('.order-filters')[0].innerHTML = newFilters.querySelectorAll('.order-filters')[0].innerHTML;

                var newCodes = [];
                document.querySelectorAll('.order-col').forEach(function (col) {
                    if (parseInt(col.getAttribute('data-order-id'), 10) > previousLatestId) {
                        col.classList.add('new-order');
                        newCodes.push(col.getAttribute('data-order-code'));
                    }
                });
                applySearch();
                return newCodes;
            });
    }

    function poll() {
        fetch('ajax_web_orders_state.php', { cache: 'no-store', credentials: 'same-origin', headers: {'X-Background-Request': '1'} })
            .then(function (res) { if (!res.ok) throw new Error(res.status); return res.json(); })
            .then(function (now) {
                setLive(true);
                if (now.signature === state.signature) return;
                var previousLatestId = state.latestId;
                state = now;
                return refreshOrders(previousLatestId).then(function (newCodes) {
                    // An order newer than the last one seen = a new website order (it may be hidden by the current filter)
                    if (now.latestId > previousLatestId) announce(newCodes || []);
                });
            })
            .catch(function () { setLive(false); })
            .then(function () { setTimeout(poll, POLL_MS); });
    }
    setTimeout(poll, POLL_MS);
})();
</script>
<? include_once("../includes/inc_footer.php"); ?>
