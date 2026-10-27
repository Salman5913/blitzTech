<?php


class VoucherApproval
{
    private $DistinctId;
    /**
     * @var VoucherType
     */
    private $oVoucherType;

    public function __construct($DistinctId, VoucherType $oVoucherType)
    {
        $this->DistinctId = $DistinctId;
        $this->oVoucherType = $oVoucherType;
    }

    function createNew()
    {
        DBQM::query("INSERT INTO distinct_id_voucher_approval VALUES (
                                            0,
                                            ".escape($this->DistinctId, 'int').",
                                            NULL,
                                            NULL,
                                            NULL,
                                            NULL
        )");
    }

    function markAsApproved()
    {
        if ($this->oVoucherType->isApprovalRequired())
        {
            if (SecurityShell::isAccess("Voucher Approval", "Level 1"))
            {
                DBQM::query("
                    UPDATE 
                        distinct_id_voucher_approval 
                    SET 
                        l1_approved_by_user_id = ".escape(SecurityShell::getLoggedInUserId(), 'int').", 
                        l1_approved_by_date_time = ".escape(DatabaseHelper::NOW()).", 
                        l2_approved_by_user_id = NULL, 
                        l2_approved_by_date_time = NULL 
                    WHERE 
                        distinct_id = ".escape($this->DistinctId, 'int'));
            }

            if (SecurityShell::isAccess("Voucher Approval", "Level 2"))
            {
                $SelectIfLevel1IsNotEmpty = DBQM::query("SELECT * FROM distinct_id_voucher_approval WHERE l1_approved_by_user_id IS NOT NULL AND distinct_id = ".escape($this->DistinctId));

                if (!DatabaseHelper::isRecordSetEmpty($SelectIfLevel1IsNotEmpty))
                {
                    DBQM::query("
                        UPDATE 
                            distinct_id_voucher_approval 
                        SET 
                            l2_approved_by_user_id = ".escape(SecurityShell::getLoggedInUserId(), 'int').", 
                            l2_approved_by_date_time = ".escape(DatabaseHelper::NOW())."
                        WHERE 
                            distinct_id = ".escape($this->DistinctId, 'int')
                    );
                }
                else
                    ErrorHandler::setError("Level 1 Approval Required First!");
            }
        }

    }


    /* ************************ Methods for Back End ******************** */
    function isApprovedForEntries()
    {
        if ($this->oVoucherType->isApprovalRequired())
        {
            $Select = DBQM::query("SELECT id FROM distinct_id_voucher_approval WHERE distinct_id = ".escape($this->DistinctId, 'int')." AND l2_approved_by_user_id IS NOT NULL");
            return !DatabaseHelper::isRecordSetEmpty($Select);
        }
        else
            return true;
    }
    /* ************************ Methods for Back End ******************** */




    /* ************************ Methods for Front End ******************** */
    function canApproveVouchers()
    {
        return (SecurityShell::isAccess("Voucher Approval", "Level 1") or SecurityShell::isAccess("Voucher Approval", "Level 2"));
    }
    /* ************************ Methods for Front End ******************** */

    static function pendingApprovalVoucherList($VoucherTypeId)
    {
        $List = array();
        if (!empty($VoucherTypeId))
        {
            $Select = DBQM::query("SELECT voucher_id FROM vw_pending_voucher_approval WHERE voucher_type_id = ".escape($VoucherTypeId));
            while ($Row = DBQM::fetch_assoc($Select))
            {
                $List[] = $Row['voucher_id'];
            }
        }

        return $List;
    }
}