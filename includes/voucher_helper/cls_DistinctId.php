<?php
/**
 * Created by PhpStorm.
 * User: mustafa
 * Date: 002, 1/2/2015
 * Time: 11:10 AM
 */

class DistinctId
{
    protected $DistinctId;
    protected $oVoucherType;
    protected $VoucherNumber;
    protected $isDraft;
    protected $CostCenterId;
    protected $ProjectId;

    function __construct(VoucherType $oVoucherType, $DistinctId, $CoatCenterId, $ProjectId, $isDraft='N')
    {
        $this->oVoucherType = $oVoucherType;
        $this->DistinctId = $DistinctId;
        $this->CostCenterId = $CoatCenterId;
        $this->ProjectId = $ProjectId;
        $this->isDraft = $isDraft;
        $this->VoucherNumber = $this->getVoucherNumberFromTable();
    }


    function createNew($VoucherDate)
    {
        $LockKey = "finac_vn_lock_" . Company::getLoggedInCompanyId() . "_" . Year::getYearId() . "_" . $this->oVoucherType->getVoucherType();
        DBQM::query("SELECT GET_LOCK('{$LockKey}', 10)");

        $this->VoucherNumber = $this->getNextVoucherNumber();
        DBQM::query("INSERT INTO distinct_id VALUE (
                                                        0, 
                                                        ".escape(Company::getLoggedInCompanyId(), 'int').", 
                                                        ".escape($this->oVoucherType->getVoucherType(), 'int').",
                                                        ".escape($this->VoucherNumber, 'int').",
                                                        NULL,
                                                        ".escape($VoucherDate).", 
                                                        ".escape(DatabaseHelper::NOW()).",
                                                        ".escape(SecurityShell::getLoggedInUserId(), 'int').",
                                                        'N',
                                                        NULL,
                                                        NULL,
                                                        'N',
                                                        NULL,
                                                        NULL,
                                                        ".escape(Year::getYearId()).",
                                                        ".escape($this->oVoucherType->isApprovalRequired() ? 'Y' : 'N').",
                                                        null,
                                                        ".escape($this->CostCenterId, 'int', true).",
                                                        ".escape($this->ProjectId, 'int', true).",
                                                        ".escape($this->isDraft, 'text', true)."
        )");

        $this->DistinctId = DatabaseHelper::getLastInsertId();

        DBQM::query("SELECT RELEASE_LOCK('{$LockKey}')");

        $oVoucherApproval = new VoucherApproval($this->DistinctId, $this->oVoucherType);
        $oVoucherApproval->createNew();
    }


    /* ************************ Voucher Number ************************** */
    function getNextVoucherNumber()
    {
        $Select = DBQM::query("
                    SELECT 
                        MAX(voucher_number) 
                    FROM 
                        distinct_id
                    WHERE 
                        year_id = ".escape(Year::getYearId())." AND 
                        voucher_type_id = ".escape($this->oVoucherType->getVoucherType())
        );
        return (int)DBQM::result($Select, 0) + 1;
    }

    private function getVoucherNumberFromTable()
    {
        $Select = DBQM::query("SELECT voucher_number FROM distinct_id WHERE id = ".escape($this->DistinctId));
        return DBQM::result($Select, 0);
    }

    function getVoucherNumber()
    {
        return $this->VoucherNumber;
    }

    function updateVoucherId($VoucherId)
    {
        DBQM::query("UPDATE distinct_id SET voucher_id = ".escape($VoucherId)." WHERE id = ".escape($this->DistinctId));
    }
    /* ************************ Voucher Number ************************** */

    function getDistinctId()
    {
        return $this->DistinctId;
    }

    function getTime()
    {
        $Select = DBQM::query("SELECT TIME(created_date_time) FROM distinct_id WHERE id = ".escape($this->DistinctId));
        $Time = DBQM::result($Select, 0);

        return empty($Time) ? DatabaseHelper::TIME() : " ".$Time;
    }

    function updateVoucherDate($VoucherDate)
    {
        DBQM::query("UPDATE distinct_id SET voucher_date = ".escape($VoucherDate).", cost_center_id = ".escape($this->CostCenterId, 'int', true).", project_id = ".escape($this->ProjectId, 'int', true).", is_draft = ".escape($this->isDraft)." WHERE id = ".escape($this->DistinctId));
    }

    static function getVoucherTypeIdByDistinctId($DistinctId)
    {
        $Select = DBQM::query("SELECT voucher_type_id FROM distinct_id WHERE id = ".escape($DistinctId, 'int'));
        return DBQM::result($Select, 0);
    }

    function undoDeleteVoucher()
    {
        DBQM::query("UPDATE distinct_id SET is_deleted = 'N', deleted_date_time = NULL, deleted_by = NULL WHERE id = ".escape($this->DistinctId));
    }

    function deleteVoucher()
    {
        DBQM::query("UPDATE distinct_id SET is_deleted = 'Y', deleted_date_time = ".escape(DatabaseHelper::NOW()).", deleted_by = ".escape(SecurityShell::getLoggedInUserId())." WHERE id = ".escape($this->DistinctId));
    }

    function setUpdatedInfo()
    {
        DBQM::query("UPDATE distinct_id SET is_updated = 'Y', updated_date_time = ".escape(DatabaseHelper::NOW()).", updated_by = ".escape(SecurityShell::getLoggedInUserId())." WHERE id = ".escape($this->DistinctId));
    }

    function getVoucherDeleteStatus()
    {
        $Select = DBQM::query("SELECT is_deleted, deleted_by, deleted_date_time, is_draft, created_by FROM distinct_id WHERE id = ".escape($this->getDistinctId()));
        return DBQM::fetch_assoc($Select);
    }

    static function getDateByDistinctId($DistinctId)
    {
        $Select = DBQM::query("SELECT voucher_date FROM distinct_id WHERE id = ".escape($DistinctId, 'int'));
        return DBQM::result($Select, 0);
    }

    function getData()
    {
        $Select = DBQM::query("
            SELECT 
                did.*,
                lcb.username AS created_by_username,
                lub.username AS updated_by_username,
                ldb.username AS deleted_by_username
            FROM 
                distinct_id did 
                LEFT JOIN 
                    sh_login lcb 
                    on did.created_by = lcb.id 
                LEFT JOIN 
                    sh_login lub 
                    on did.updated_by = lub.id 
                LEFT JOIN 
                    sh_login ldb 
                    on did.deleted_by = ldb.id 
            WHERE
                did.id = ".escape($this->DistinctId)."
        ");

        return DBQM::fetch_assoc($Select);
    }

    static function getVoucherDetails($DistinctId)
    {
        $Select = DBQM::query("SELECT * FROM distinct_id WHERE id = ".escape($DistinctId));
        return DBQM::fetch_assoc($Select);
    }
}