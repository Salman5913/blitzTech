<?
include_once("../connection/localhost.php");

$_GET['_page_title'] = 'Website On / Off';
$_GET['_page_title_text'] = 'When customers can visit and order on the restaurant website';

include_once("../includes/inc_header.php");

$ActiveTab = 'website';
include_once(__DIR__ . '/_tabs.php');

function h($Value) { return htmlspecialchars((string)$Value, ENT_QUOTES, 'UTF-8'); }

$Settings = RestaurantWebsite::getSettings();
$Status = RestaurantWebsite::getStatus();
?>
<div class="row">
    <div class="col-lg-7">
        <div class="card">
            <div class="d-flex align-items-center">
                <h3 class="card-title mb-0">Website On / Off</h3>
                <? if ($Status['open']) { ?>
                    <span class="badge badge-soft-success ml-auto"><i class="mdi mdi-circle-medium"></i> Open now</span>
                <? } else { ?>
                    <span class="badge badge-soft-danger ml-auto"><i class="mdi mdi-circle-medium"></i> Closed now</span>
                <? } ?>
            </div>
            <p class="text-muted mt-2">While the website is closed, customers see a popup with the message below and can't order. Set opening and closing times to open and close the website every day by itself.</p>
            <form method="post" action="op_save_website_status.php">
                <div class="form-group">
                    <div class="custom-control custom-switch" dir="ltr">
                        <input type="checkbox" class="custom-control-input" id="is_open" name="is_open" value="Y" <?=$Settings['is_open'] ? 'checked' : ''?>>
                        <label class="custom-control-label font-weight-bold" for="is_open">Website is On</label>
                    </div>
                    <small class="text-muted">Switch off to close the website until you switch it on again.</small>
                </div>
                <div class="row">
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="open_time">Opening Time</label>
                            <input type="time" name="open_time" id="open_time" value="<?=h(substr((string)$Settings['open_time'], 0, 5))?>">
                        </div>
                    </div>
                    <div class="col-sm-6">
                        <div class="form-group">
                            <label for="close_time">Closing Time</label>
                            <input type="time" name="close_time" id="close_time" value="<?=h(substr((string)$Settings['close_time'], 0, 5))?>">
                        </div>
                    </div>
                </div>
                <small class="text-muted d-block mb-3">Leave both empty to keep the website open all day. A closing time before the opening time (e.g. 6:00 PM to 2:00 AM) runs past midnight.</small>
                <div class="form-group">
                    <label for="closed_message">Popup Message</label>
                    <input type="text" name="closed_message" id="closed_message" maxlength="255" value="<?=h($Settings['closed_message'])?>"
                           placeholder="<?=h(RestaurantWebsite::getClosedMessage(['closed_message' => '', 'open_time' => $Settings['open_time'] ?: '18:00:00']))?>">
                    <small class="text-muted">Leave empty for: "We're closed right now. Kindly visit at (opening time)."</small>
                </div>
                <div class="text-right">
                    <button class="btn button color" type="submit"><i class="mdi mdi-content-save"></i> Save</button>
                </div>
            </form>
        </div>
    </div>

    <div class="col-lg-5">
        <div class="card">
            <h3 class="card-title">What customers see now</h3>
            <? if ($Status['open']) { ?>
                <p class="mb-0"><i class="mdi mdi-check-circle text-success"></i> The website is open for orders<?=$Settings['close_time'] ? ' until ' . h(RestaurantWebsite::formatTime($Settings['close_time'])) : ''?>.</p>
            <? } else { ?>
                <div class="border rounded p-3 text-center">
                    <div style="font-size: 32px">🕕</div>
                    <b>We're closed</b>
                    <p class="text-muted mb-0 mt-1"><?=h($Status['message'])?></p>
                </div>
            <? } ?>
        </div>
    </div>
</div>
<? include_once("../includes/inc_footer.php"); ?>
