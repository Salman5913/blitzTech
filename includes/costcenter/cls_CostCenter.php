<?php

/**
 * Cost centers (cc_cost_center), managed from Settings > Cost Centers.
 *
 * Users can be limited to some cost centers in Security Shell > Users (sh_user_cost_center); a user without any
 * sees all. Every voucher saves its cost center in distinct_id.cost_center_id (BasicVoucher::initForSave), and
 * reports filter on it with getFilterSql().
 */
class CostCenter extends BasicForm
{
    public function __construct()
    {
        parent::__construct(
            "cc_cost_center",
            "",
            "",
            "Cost Center"
        );

        $this->setAccessAreas('Settings', 'Cost Center');
    }

    function getSearchQuery(): mysqli_result
    {
        return DBQM::query("
            SELECT
                cc.*
            FROM
                cc_cost_center cc
            ORDER BY
                cc.cost_center_name
        ");
    }

    /** [id => name] of every cost center */
    static function getOptions()
    {
        $Options = [];
        $Select = DBQM::query("SELECT id, cost_center_name FROM cc_cost_center ORDER BY cost_center_name");
        while ($Row = DBQM::fetch_assoc($Select))
            $Options[$Row['id']] = $Row['cost_center_name'];

        return $Options;
    }

    static function getName($CostCenterId)
    {
        return empty($CostCenterId) ? '' : DBQM::result(DBQM::query("SELECT cost_center_name FROM cc_cost_center WHERE id = ".escape($CostCenterId, 'int')), 0);
    }

    /* ************************* Users ************************* */
    /** Cost center ids assigned to a user (none = all cost centers); cost centers that no longer exist are left out */
    static function getUserCostCenterIds($UserId)
    {
        $Ids = [];
        $Select = DBQM::query("SELECT ucc.cost_center_id FROM sh_user_cost_center ucc JOIN cc_cost_center cc ON cc.id = ucc.cost_center_id
                               WHERE ucc.user_id = ".escape($UserId, 'int')." ORDER BY cc.cost_center_name");
        while ($Row = DBQM::fetch_assoc($Select))
            $Ids[] = (int)$Row['cost_center_id'];

        return $Ids;
    }

    static function saveUserCostCenters($UserId, array $CostCenterIds)
    {
        if (empty($UserId))
            return;

        DBQM::query("DELETE FROM sh_user_cost_center WHERE user_id = ".escape($UserId, 'int'));
        foreach (array_unique(array_filter(array_map('intval', $CostCenterIds))) as $CostCenterId)
            DBQM::query("INSERT INTO sh_user_cost_center (user_id, cost_center_id) VALUES (".escape($UserId, 'int').", ".escape($CostCenterId, 'int').")");
    }

    static function isSingleCostCenterAssigned()
    {
        return count(self::getUserCostCenterIds(SecurityShell::getLoggedInUserId())) == 1;
    }

    static function getSingleCostCenterId()
    {
        $Ids = self::getUserCostCenterIds(SecurityShell::getLoggedInUserId());
        return count($Ids) == 1 ? $Ids[0] : '';
    }

    /** Cost center selected on new vouchers: the logged in user's assigned one (the first by name when several), or '' */
    static function getUserDefaultCostCenterId()
    {
        return self::getUserCostCenterIds(SecurityShell::getLoggedInUserId())[0] ?? '';
    }

    /* ************************* Vouchers ************************* */
    /**
     * Cost center field of a voucher form, saved by BasicVoucher::initForSave() (name cost_center_id).
     * Like the branch field: a user with assigned cost centers gets theirs selected on new vouchers and can only
     * pick those (Autocomplete::searchData); a user without any sees all. On edit the voucher keeps its own.
     * $FormGroupClass: extra classes for the form-group, e.g. 'mb-lg-0' to line up with its row.
     */
    static function designVoucherField($CostCenterId, $isEdit, $ColumnClass = 'col-xl-3 col-lg-4 col-md-6', $FormGroupClass = '')
    {
        if (!ProjectHelper::HAS_COST_CENTER)
            return;

        if (!$isEdit && empty($CostCenterId))
            $CostCenterId = self::getUserDefaultCostCenterId();
        ?>
        <div class="<?=$ColumnClass?>">
            <div class="form-group <?=$FormGroupClass?>">
                <label for="cost_center_id">Cost Center</label>
                <input type="text" name="cost_center_id" id="cost_center_id" <? Autocomplete::Search(Autocomplete::TYPE_COST_CENTER, $CostCenterId); ?>>
            </div>
        </div>
        <?php
    }

    /* ************************* Reports ************************* */
    /** Cost center filter of a report form (GET cost_center_id) */
    static function designReportFilter($ColumnClass = 'col-md-3')
    {
        if (!ProjectHelper::HAS_COST_CENTER)
            return;
        ?>
        <div class="<?=$ColumnClass?>">
            <div class="form-group">
                <label for="cost_center_id">Cost Center</label>
                <input type="text" name="cost_center_id" id="cost_center_id" <? Autocomplete::Search(Autocomplete::TYPE_COST_CENTER, $_GET['cost_center_id'] ?? ''); ?>>
            </div>
        </div>
        <?php
    }

    /**
     * Cost center a report is filtered on: the one picked in the filter ([] = no filter). A cost center the user
     * isn't assigned to matches nothing. Vouchers saved before cost centers have none, so no pick shows everything.
     * @return int[]
     */
    static function getReportCostCenterIds($CostCenterId = null)
    {
        $CostCenterId = (int)($CostCenterId ?? ($_GET['cost_center_id'] ?? 0));
        if (!$CostCenterId)
            return [];

        $UserIds = self::getUserCostCenterIds(SecurityShell::getLoggedInUserId());
        return (!$UserIds || in_array($CostCenterId, $UserIds)) ? [$CostCenterId] : [0];
    }

    /**
     * " AND ..." for a report query, on a distinct_id column (e.g. "l.distinct_id", "s.distinct_id") or, with
     * $isCostCenterColumn, on a cost_center_id column (e.g. "did.cost_center_id"). Empty when no filter applies.
     */
    static function getFilterSql($Column, $isCostCenterColumn = false, $CostCenterId = null)
    {
        $Ids = self::getReportCostCenterIds($CostCenterId);
        if (!$Ids)
            return '';

        $In = implode(', ', $Ids);
        return $isCostCenterColumn
            ? " AND {$Column} IN ({$In}) "
            : " AND {$Column} IN (SELECT cc_did.id FROM distinct_id cc_did WHERE cc_did.cost_center_id IN ({$In})) ";
    }
}
