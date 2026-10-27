<?php


abstract class BasicVoucher extends BasicForm
{
    protected VoucherType $oVoucherType;
    private string $VoucherNumber;

    private $ArchiveData = null;

    private DistinctId $oDistinctId;

    private string $DateTime;

    private Ledger $oLedger;

    /**
     * @var StockLedger
     */
    private
        $oStockLedger;

    /**
     * @var VoucherApproval
     */
    private $oVoucherApproval;

    private array $AllowedVoucherTypes;
    private array $UnApprovedVouchersList;

    private string $PrintLink;
    private bool $AutoPrint = false;
    private bool $HasProject = ProjectHelper::HAS_PROJECT;
    private bool $AllProjectsAC = false;
    private bool $hasReportFilters = true;
    private $VoucherStatus;
    private bool $isAddNewOptionByReport = true;

    function __construct(string $TableName, string|array $DataTableName, string $DataIdColumnName, string|array $AllowedVoucherTypes, private readonly bool $isLedgerObjectRequired=true)
    {
        $this->AllowedVoucherTypes = !is_array($AllowedVoucherTypes) ? [$AllowedVoucherTypes] : $AllowedVoucherTypes;
        $this->UnApprovedVouchersList = VoucherApproval::pendingApprovalVoucherList($_GET['voucher_type_id']);
        parent::__construct($TableName, $DataTableName, $DataIdColumnName, '');
    }

    function setVariablesForEditManually($Id, $VoucherTypeId)
    {
        $_GET['id'] = $Id;
        $_GET['voucher_type_id'] = $VoucherTypeId;
    }

    function setVariablesForSaveManually($Id, $VoucherTypeId, $CostCenterId='', $ProjectId='')
    {
        $_POST['voucher_id'] = $Id;
        $_POST['voucher_type_id'] = $VoucherTypeId;

        if (!empty($CostCenterId))
            $_POST['cost_center_id'] = $CostCenterId;
        if (!empty($ProjectId))
            $_POST['project_id'] = $ProjectId;
    }

    function isLedgerObjectRequired(): bool
    {
        return $this->isLedgerObjectRequired;
    }

    function disableEditIfNoPermission($isEdit)
    {
        if ($isEdit and !SecurityShell::isVoucherEditAccess($this->oVoucherType->getVoucherType()))
        {
            $this->disableEdits();
        }
    }

    function getAllowedVoucherTypes()
    {
        return $this->AllowedVoucherTypes;
    }

    function isProject()
    {
        return $this->HasProject;
    }

    function isAllProjectList()
    {
        return $this->AllProjectsAC;
    }

    function setReportFilters($hasFilters)
    {
        return $this->hasReportFilters = $hasFilters;
    }

    private bool $isDuplicateSubmission = false;

    function initForSave()
    {
        $this->VoucherId = $_POST['voucher_id'];
        $this->isDelete = isset($_POST['btn_delete']);
        $this->isOpenNew = isset($_POST['btn_new']);
        $this->isClose = isset($_POST['btn_close']);
        $this->isDraft = isset($_POST['btn_draft']);

        $this->oVoucherType = new VoucherType($_POST['voucher_type_id']);
        $isDraftValue = ($this->isDraft ? 'Y' : 'N');

        if (!in_array($this->getVoucherType(), $this->AllowedVoucherTypes))
            ErrorHandler::showOnErrorPage("Invalid Voucher");

        $this->oDistinctId = new DistinctId($this->oVoucherType, $this->getVoucherDistinctId(), $_POST['cost_center_id'], $_POST['_project_id'], $isDraftValue);
        $this->DateTime = $this->formatDateTime($_POST['voucher_date']);

        if ($this->isNew())
        {
            $SubmitToken = $_POST['__voucher_submit_token'] ?? '';
            $PayloadHash = md5(Company::getLoggedInCompanyId() . '_' . $this->getVoucherType() . '_' . SecurityShell::getLoggedInUserId() . '_' . json_encode($_POST));

            if (!empty($SubmitToken) && isset($_SESSION['__processed_voucher_tokens'][$SubmitToken]))
            {
                $this->isDuplicateSubmission = true;
                $this->setVoucherId($_SESSION['__processed_voucher_tokens'][$SubmitToken]['voucher_id']);
                $this->VoucherNumber = (string)($_SESSION['__processed_voucher_tokens'][$SubmitToken]['voucher_number'] ?? '');
                $this->oDistinctId = new DistinctId($this->oVoucherType, $_SESSION['__processed_voucher_tokens'][$SubmitToken]['distinct_id'], $_POST['cost_center_id'], $_POST['_project_id'], $isDraftValue);
                return;
            }

            if (isset($_SESSION['__last_voucher_submission']) && $_SESSION['__last_voucher_submission']['hash'] === $PayloadHash && (time() - $_SESSION['__last_voucher_submission']['time']) < 10)
            {
                $this->isDuplicateSubmission = true;
                $this->setVoucherId($_SESSION['__last_voucher_submission']['voucher_id']);
                $this->VoucherNumber = (string)($_SESSION['__last_voucher_submission']['voucher_number'] ?? '');
                $this->oDistinctId = new DistinctId($this->oVoucherType, $_SESSION['__last_voucher_submission']['distinct_id'], $_POST['cost_center_id'], $_POST['_project_id'], $isDraftValue);
                return;
            }

            $this->oDistinctId->createNew($this->getDateTime());
        }
        else
            $this->oDistinctId->updateVoucherDate($this->getDateTime());

        $this->VoucherNumber = $this->oDistinctId->getVoucherNumber();

        /* ******** Voucher Approval ********* */
        $this->oVoucherApproval = new VoucherApproval($this->getDistinctId(), $this->oVoucherType);
        $this->oVoucherApproval->markAsApproved();
        /* ******** Voucher Approval ********* */

        if (!empty($this->VoucherId))
        {
            $this->deleteLinkedData();

            if (!$this->isDelete)
                $this->setAsUpdated();
        }

        if ((!$this->isDelete() AND !$this->isDraft()) and $this->oVoucherApproval->isApprovedForEntries() and $this->isLedgerObjectRequired)
        {
            $this->oLedger = new Ledger($this->getDistinctId(), $this->getDateTime(), $this->getVoucherType(), $this->getVoucherNumber(), $this->getVoucherId());
            $this->oStockLedger = new StockLedger($this->oLedger, $this->getDistinctId(), $this->oVoucherType, $this->getVoucherNumber(), $this->getDateTime());
        }
    }

    function initForEdit()
    {
        parent::initForEdit();

        if (!SecurityShell::isVoucherAccess($_GET['voucher_type_id']))
            ErrorHandler::UnauthorizedAccess();

        $this->oVoucherType = new VoucherType($_GET['voucher_type_id']);

        if (!in_array($this->getVoucherType(), $this->AllowedVoucherTypes))
            ErrorHandler::showOnErrorPage("Invalid Voucher");

        $this->oDistinctId = new DistinctId($this->oVoucherType, $this->getVoucherDistinctId(), '', '');
        $this->VoucherNumber = $this->oDistinctId->getVoucherNumber();

        $_GET['__voucher_number'] = $this->VoucherNumber;
        $_GET['__voucher_distinct_id'] = $this->oDistinctId->getDistinctId();
        $_GET['__is_edit'] = $this->isEdit() ? 'Y' : 'N';

        /* ******** Voucher Approval ********* */
        $this->oVoucherApproval = new VoucherApproval($this->getDistinctId(), $this->oVoucherType);
        /* ******** Voucher Approval ********* */

        $this->setHeader();
    }


    /* ****************** Delete Operations **************** */
    private function createArchiveMasterId()
    {
        DBQM::query("INSERT INTO voucher_modification_tracker VALUES (
             0, 
             ".escape($this->oDistinctId->getDistinctId()).", 
             ".escape(SecurityShell::getLoggedInUserId()).", 
             NOW(),
             ".escape($this->ArchiveData['main']).",
             ".escape($this->ArchiveData['data_table_1'], 'text', true).",
             ".escape($this->ArchiveData['data_table_2'], 'text', true).",
             ".escape($this->ArchiveData['data_table_3'], 'text', true).",
             ".escape($this->ArchiveData['data_table_4'], 'text', true).",
             ".escape($this->ArchiveData['data_table_5'], 'text', true)."
        )");
    }

    private function archiveData()
    {
        $this->ArchiveData['main'] = json_encode($this->getDetails());

        $i=1;
        foreach ($this->DataTableName as $TableName)
        {
            if (!empty($TableName))
            {
                $ArchivingData = [];
                $Select = DBQM::query("SELECT * FROM {$TableName} WHERE {$this->DataIdColumnName} = ".escape($this->VoucherId));
                while($Row = DBQM::fetch_assoc($Select))
                    $ArchivingData[] = $Row;

                $this->ArchiveData['data_table_'.$i] = json_encode($ArchivingData);
                $i++;
            }
        }

        $this->createArchiveMasterId();
    }

    protected function deleteLinkedData()
    {
        $this->archiveData();
        parent::deleteLinkedData();

        Ledger::deleteByDistinctId($this->getDistinctId());
        StockLedger::deleteByDistinctId($this->getDistinctId());

        $this->oDistinctId->undoDeleteVoucher();
        if ($this->isDelete())
        {
            $this->oDistinctId->deleteVoucher();
        }
    }

    function showVoucherDeleteNotification()
    {
        $RowDeleteStatus = $this->oDistinctId->getVoucherDeleteStatus();

        if ($RowDeleteStatus['is_deleted'] == 'Y') { ?>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card  bg-danger text-white">
                        <h3 class="card-title text-white"><i class="mdi mdi-alert-rhombus"></i> Warning</h3>
                        <p>This voucher has been deleted by <span class="font-weight-bold"><?=SecurityShell::getUserNameById($RowDeleteStatus['deleted_by'])?></span>, updating this voucher will undo delete.</p>
                    </div>
                </div>
            </div>
        <? } elseif ($RowDeleteStatus['is_draft'] == 'Y') { ?>
            <div class="row">
                <div class="col-sm-12">
                    <div class="card bg-info text-white">
                        <h3 class="card-title text-white"><i class="mdi mdi-alert-rhombus"></i> Warning</h3>
                        <p>This voucher is in draft by <span class="font-weight-bold"><?=SecurityShell::getUserNameById($RowDeleteStatus['created_by'])?></span>, updating this voucher will approve.</p>
                    </div>
                </div>
            </div>
        <?
        }
    }
    /* ****************** Delete Operations **************** */




    /* ****************** Voucher Buttons **************** */
    function showVoucherButtons(array $AdditionalLinks=[], BasicVoucherAdditionalButton $oBasicVoucherAdditionalButtons=null, $isAdditionalButton=true)
    {
        echo $this->getVoucherActivityNote();

        if ($this->oVoucherApproval->canApproveVouchers())
            $ButtonText = "Save & Approve Voucher";
        elseif ($this->isCopy())
            $ButtonText = "Copy Voucher";
        elseif ($this->isEdit())
            $ButtonText = "Update Voucher";
        else
            $ButtonText = "Save Voucher";

        $VoucherTypeId = $this->getVoucherType();

        ?>
        <div class="row form-group voucher-buttons">
            <? if ($this->isEdit() && !$this->isCopy()) { ?>
                <div class="col-auto mr-auto">
                    <input type="hidden" name="voucher_id" id="voucher_id" value="<?=$this->getVoucherId()?>">
                    <button class="btn button btn-danger pull-left margin-10" name="btn_delete" id="btn_delete" type="submit" onclick="return confirm('Are you sure you want to delete this voucher?')"><i class="mdi mdi-trash-can-outline"></i> Delete Voucher</button>
                </div>
            <? } ?>

            <div class="col-auto ml-auto">
                <? $oBasicVoucherAdditionalButtons?->getButtons() ?>
                <div class="btn-group">
                    <button class="btn button color" name="submit" data-submit-alert="Y" type="submit"><i class="mdi mdi-content-save"></i> <?=$ButtonText?></button>
                    <? if ((!empty($AdditionalLinks) OR isset($_GET['voucher_type_id'])) AND $isAdditionalButton) { ?>
                        <button type="button" class="btn button color btn-sm dropdown-toggle" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false"><i class="mdi mdi-chevron-down"></i></button>
                        <div class="dropdown-menu dropdown-menu-right">
                            <button class="dropdown-item" data-submit-alert="Y" name="btn_close" type="submit"><i class="mdi mdi-file-cancel"></i> Approve & Close Voucher</button>
                            <button class="dropdown-item" data-submit-alert="Y" name="btn_new" type="submit"><i class="mdi mdi-plus-box-multiple"></i> Approve & New Voucher</button>
                            <button class="dropdown-item" data-submit-alert="Y" name="btn_draft" type="submit"><i class="mdi mdi-plus-box"></i> Save & Draft Voucher</button>
                            <? foreach ($AdditionalLinks as $Link => $Text) { ?>
                                <a class="dropdown-item" href="<?=$Link?>"><?=$Text?></a>
                            <? } ?>
                        </div>
                    <? } ?>
                </div>
            </div>
        </div>

        <input type="hidden" name="voucher_type_id" id="voucher_type_id" value="<?=$this->getVoucherType()?>">
        <?
        $SubmitToken = bin2hex(random_bytes(16));
        echo '<input type="hidden" name="__voucher_submit_token" id="__voucher_submit_token" value="'.$SubmitToken.'">';
        $this->disableEditIfNoPermission($this->isEdit());
        $this->displayForViewPurpose();
        $this->checkForVoucherDateField();
    }
    /* ****************** Voucher Buttons **************** */


    /* ****************** Voucher Buttons **************** */
    function showQuoteVoucherButtons()
    {
        if ($this->oVoucherApproval->canApproveVouchers())
            $ButtonText = "Save & Approve Voucher";
        elseif ($this->isEdit())
            $ButtonText = "Update Voucher";
        else
            $ButtonText = "Save Voucher";
        ?>
        <div class="row form-group voucher-buttons">
            <? if ($this->isEdit()) { ?>
                <div class="col-auto mr-auto">
                    <input type="hidden" name="voucher_id" id="voucher_id" value="<?=$this->getVoucherId()?>">
                    <button class="btn button btn-danger pull-left margin-10" name="btn_delete" id="btn_delete" type="submit" onclick="return confirm('Are you sure you want to delete this voucher?')"><i class="mdi mdi-trash-can-outline"></i> Delete Voucher</button>
                </div>
            <? } ?>

            <div class="col-auto ml-auto">
                <? if ($this->isEdit()) { ?>
                    <button class="btn button btn-info pull-left margin-10" name="btn_convert_invoice" id="btn_convert_invoice" type="submit" onclick="return confirm('Are you sure you want to convert this to Invoice?')"><i class="fa fa-file-text"></i> Convert To Invoice</button>
                    <button class="btn button btn-success pull-right margin-10" name="btn_save" id="btn_save" onclick="document.getElementById('voucher_id').value = ''" type="submit"><i class="fa fa-check"></i> Save as New Quotation</button>
                <? } ?>
                <button class="btn button color" name="submit" type="submit"><i class="mdi mdi-content-save"></i> <?=$ButtonText?></button>
            </div>
        </div>

        <input type="hidden" name="voucher_type_id" id="voucher_type_id" value="<?=$this->getVoucherType()?>">
        <?
        $SubmitToken = bin2hex(random_bytes(16));
        echo '<input type="hidden" name="__voucher_submit_token" id="__voucher_submit_token" value="'.$SubmitToken.'">';
        $this->disableEditIfNoPermission($this->isEdit());
        $this->displayForViewPurpose();
        $this->checkForVoucherDateField();
    }
    /* ****************** Voucher Buttons **************** */




    /* ***************** Insert / Update Data ******************* */
    function saveMainTableData($Values)
    {
        if ($this->isDuplicateSubmission)
            return;

        $oDatabaseInsertUpdateHelper = new DatabaseInsertUpdateHelper($this->TableName, $this->getDistinctId());
        $this->setVoucherId($oDatabaseInsertUpdateHelper->saveData($Values, $this->getVoucherId()));

        $this->oDistinctId->updateVoucherId($this->getVoucherId());

        if ((!$this->isDelete() && !$this->isDraft()) and $this->oVoucherApproval->isApprovedForEntries() and $this->isLedgerObjectRequired)
            $this->oLedger->updateVoucherIdByDistinctId($this->getDistinctId(), $this->getVoucherId());

        $SubmitToken = $_POST['__voucher_submit_token'] ?? '';
        if (!empty($SubmitToken)) {
            if (!isset($_SESSION['__processed_voucher_tokens'])) $_SESSION['__processed_voucher_tokens'] = [];
            $_SESSION['__processed_voucher_tokens'][$SubmitToken] = [
                'voucher_id' => $this->getVoucherId(),
                'voucher_number' => $this->getVoucherNumber(),
                'distinct_id' => $this->getDistinctId(),
                'time' => time()
            ];
            if (count($_SESSION['__processed_voucher_tokens']) > 50) {
                $_SESSION['__processed_voucher_tokens'] = array_slice($_SESSION['__processed_voucher_tokens'], -30, null, true);
            }
        }
        $PayloadHash = md5(Company::getLoggedInCompanyId() . '_' . $this->getVoucherType() . '_' . SecurityShell::getLoggedInUserId() . '_' . json_encode($_POST));
        $_SESSION['__last_voucher_submission'] = [
            'hash' => $PayloadHash,
            'voucher_id' => $this->getVoucherId(),
            'voucher_number' => $this->getVoucherNumber(),
            'distinct_id' => $this->getDistinctId(),
            'time' => time()
        ];
    }


    function saveDataTableData($Values, $TableIndex=0)
    {
        if ($this->isDuplicateSubmission)
            return 0;

        $Values = array_merge([$this->getVoucherId()], $Values);

        $oDatabaseInsertUpdateHelper = new DatabaseInsertUpdateHelper($this->DataTableName[$TableIndex], $this->getDistinctId());
        return $oDatabaseInsertUpdateHelper->saveData($Values, 0);
    }


    function updateFileNameMainTable($ColumnName, $FileName)
    {
        if ($this->isDuplicateSubmission)
            return;

        DBQM::query("UPDATE {$this->TableName} SET `$ColumnName` = ".escape($FileName)." WHERE id = ".escape($this->getVoucherId()));
    }
    /* ***************** Insert / Update Data ******************* */



    /* ************** Transactions & Must Implement Functions ***************** */
    function performTransactions()
    {
        if ($this->isDuplicateSubmission)
            return;

        if ((!$this->isDelete AND !$this->isDraft) and $this->oVoucherApproval->isApprovedForEntries())
            $this->saveTransactionEntries();

        if ($this->isDelete and $this instanceof BasicVoucherAdditionalUtils)
            $this->uponVoucherMarkAsDelete();

        if (!$this->isDelete and $this->AutoPrint)
        {
            if (empty($this->PrintLink))
                $this->PrintLink = $this->getPrintLink(get_class($this));
            PopupWindowManager::addURL($this->PrintLink."&id=".$this->getVoucherId()."&voucher_type_id=".$this->getVoucherType());
        }
    }

    abstract protected function saveTransactionEntries();
    /* ************** Transactions & Must Implement Functions ***************** */



    /* ************************** Distinct Id **************************** */
    private function getVoucherDistinctId()
    {
        $Select = DBQM::query("SELECT distinct_id FROM {$this->TableName} WHERE id = ".escape($this->VoucherId));
        return DBQM::result($Select, 0);
    }

    /**
     * @return string
     */
    function getDistinctId()
    {
        return $this->oDistinctId->getDistinctId();
    }
    /* ************************** Distinct Id **************************** */



    /* **************************** Boilerplate Functions *************************** */
    function getDetails()
    {
        return $this->getDetailsByVoucherId($this->getVoucherId());
    }

    function getDetailsByVoucherId($VoucherId)
    {
        $Select = DBQM::query("SELECT t.*, di.voucher_type_id, di.voucher_number, di.voucher_date, di.voucher_total, di.is_updated, di.cost_center_id, di.project_id, di.is_draft, di.created_by FROM {$this->TableName} t, distinct_id di WHERE t.distinct_id = di.id AND t.id = ".escape($VoucherId, 'int'));
        return DBQM::fetch_assoc($Select);
    }

    function getDetailsByDistinctId($DistinctId)
    {
        $Select = DBQM::query("SELECT t.*, di.voucher_type_id, di.voucher_number, di.voucher_date, di.voucher_total, di.is_updated, di.cost_center_id, di.project_id FROM {$this->TableName} t, distinct_id di WHERE t.distinct_id = di.id AND t.distinct_id = ".escape($DistinctId, 'int'));
        return DBQM::fetch_assoc($Select);
    }
    /* **************************** Boilerplate Functions *************************** */



    /* *********************** Flag Questions ******************** */
    private function setAsUpdated()
    {
        $this->oDistinctId->setUpdatedInfo();
    }

    function setAddNewOptionInReport($Value)
    {
        $this->isAddNewOptionByReport = $Value;
    }
    /* *********************** Flag Questions ******************** */




    /* ********************** Voucher Id & Numbers *************************** */
    function getVoucherNumber()
    {
        return $this->VoucherNumber;
    }

    function getVoucherNumberForDisplay()
    {
        $Prefix = $this->oVoucherType->getPrefix();
        return $Prefix."-".($this->isEdit() ? $this->getVoucherNumber() : $this->oDistinctId->getNextVoucherNumber());
    }

    /**
     * @return string|int
     */
    function getVoucherType()
    {
        return $this->oVoucherType->getVoucherType();
    }
    /* ********************** Voucher Id & Numbers *************************** */




    /* ********************** Setting Page Headers *************************** */
    private function setHeader()
    {
        $Where = '';
        if (SecurityShell::isAccess("Master Details", "Own Vouchers Only"))
            $Where .= UtilsQueryMaker::equal("created_by", SecurityShell::getLoggedInUserId());

        $_GET['_page_title'] = $this->getVoucherNumberForDisplay()." <small>(".VoucherType::getName($this->getVoucherType()).") - ".($this->isCopy() ? "Copy " : ($this->isEdit() ? "Edit " : "New "))."</small>";
        //$_GET['_page_title_text'] = ;

        $SelectNextId = null;
        $VoucherTypeId = $this->getVoucherType();
        $CurrentVoucherId = $this->getVoucherId();

        /* ****** Prev Voucher Id ******* */
        if (!$this->isNew())
            $SelectPreviousId = DBQM::query("SELECT voucher_id FROM distinct_id WHERE voucher_type_id = ".escape($VoucherTypeId)." AND voucher_id < ".escape($CurrentVoucherId)." {$Where} ORDER BY id DESC LIMIT 1");
        else
            $SelectPreviousId = DBQM::query("SELECT voucher_id FROM distinct_id WHERE voucher_type_id = ".escape($VoucherTypeId)." {$Where} ORDER BY voucher_id DESC LIMIT 1");
        $PreviousId = DatabaseHelper::isRecordSetEmpty($SelectPreviousId) ? null : DBQM::result($SelectPreviousId, 0);
        /* ****** Prev Voucher Id ******* */


        /* ****** Next Voucher Id ******* */
        if (!$this->isNew())
            $SelectNextId = DBQM::query("SELECT voucher_id FROM distinct_id WHERE voucher_type_id = ".escape($VoucherTypeId)." AND voucher_id > ".escape($CurrentVoucherId)." {$Where} ORDER BY id DESC LIMIT 1");
        $NextId = DatabaseHelper::isRecordSetEmpty($SelectNextId) ? null : DBQM::result($SelectNextId, 0);
        /* ****** Next Voucher Id ******* */


        /* ****** Print Link ******* */
        if (empty($this->PrintLink))
        {
            $this->PrintLink = $this->getPrintLink(get_class($this));
        }
        $PrintLink = $this->PrintLink."&id=".$CurrentVoucherId."&voucher_type_id=".$VoucherTypeId;
        /* ****** Print Link ******* */

        ob_start();
        ?>
        <div class="btn-toolbar voucher-action-button" role="toolbar" aria-label="Voucher Actions">
            <div class="btn-group mr-2" role="group" aria-label="Navigation">

                <? if ($PreviousId) { ?>
                <button type="button" class="btn btn-secondary">
                        <a href="<?=$this->getViewVoucherLink($VoucherTypeId, $PreviousId)?>" class="btn btn-sm btn-white"><i class="mdi mdi-chevron-left"></i></a>
                </button>
                <? } ?>
                <? if ($NextId) { ?>
                <button type="button" class="btn btn-secondary">
                        <a href="<?=$this->getViewVoucherLink($VoucherTypeId, $NextId)?>" class="btn btn-sm btn-white"><i class="mdi mdi-chevron-right"></i></a>
                </button>
                <? } ?>
            </div>
            <div class="btn-group mr-2" role="group" aria-label="Second group">
                <? if (SecurityShell::isVoucherEditAccess($VoucherTypeId) and $this->isView()) { ?>
                    <button type="button" class="btn btn-secondary">
                        <a href="<?=VoucherType::getLink($VoucherTypeId, $CurrentVoucherId)?>" class="btn btn-sm btn-white"><i class="mdi mdi-pencil-outline"></i></a>
                    </button>
                <? } ?>
                <button type="button" class="btn btn-secondary">
                    <a href="<?=VoucherType::getLink($VoucherTypeId)?>" target="_blank" class="btn btn-sm btn-white"><i class="mdi mdi-plus"></i></a>
                </button>
            </div>
            <div class="btn-group" role="group" aria-label="Third group">
                <? if ($this->isEdit()) { ?>
                    <button type="button" class="btn btn-secondary">
                        <a href="<?=$PrintLink?>" target="_blank" class="btn btn-sm btn-white"><i class="mdi mdi-printer"></i></a>
                    </button>
                <? } ?>
            </div>
        </div>
        <?php
        $_GET['_page_title_text'] = ob_get_clean();
    }

    public function setAutoPrint(bool $AutoPrint): void
    {
        $this->AutoPrint = $AutoPrint;
    }

    public function setProject(bool $isProject, bool $isAllProjects=false): void
    {
        $this->HasProject = $isProject;
        $this->AllProjectsAC = $isAllProjects;;
    }
    /* ********************** Setting Page Headers *************************** */




    /* ********************** Objects Pass *************************** */
    function getLedgerObject(): Ledger
    {
        return $this->oLedger;
    }

    function getStockLedgerObject(): StockLedger
    {
        return $this->oStockLedger;
    }
    /* ********************** Objects Pass *************************** */




    /* ************** Voucher Date & Time *************** */
    private function formatDateTime($RawDate)
    {
        return DateEncode($RawDate.$this->oDistinctId->getTime(), 'Y-m-d H:i:s');
    }

    function getDateTime()
    {
        return $this->DateTime;
    }
    /* ************** Voucher Date & Time *************** */




    /* ************** Report & Links *************** */
    function setPrintVoucherLink($PrintLink)
    {
        if (!empty($PrintLink))
            $this->PrintLink = strpos($PrintLink, "?") ? $PrintLink : ($PrintLink."?ab=t");
    }

    /**
     * @param $VoucherTypeId
     * @param $Id
     * @param array $ExtraLinks [Link => Text with icon] <br> [Link => [Title, Text with icon] (for Ajax Popup)]
     */
    function getReportButtons($VoucherTypeId, $Id, array $ExtraLinks=[], $AdditionalQueryParams='')
    {
        if (empty($this->PrintLink))
        {
            $this->PrintLink = $this->getPrintLink(get_class($this));
        }

        ?>
        <div class="btn-group">
            <a class="btn color btn-sm" href="<?=$this->PrintLink."&id=".$Id."&voucher_type_id=".$VoucherTypeId?>" target="_blank"><i class="mdi mdi-printer"></i> Print</a>
            <button class="btn btn-sm color dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="mdi mdi-chevron-down"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <a class="dropdown-item" href="<?=$this->getViewVoucherLink($VoucherTypeId, $Id)?>"><i class="mdi mdi-eye"></i> View</a>
                <? if (!empty($ExtraLinks)) { ?>
                    <div class="dropdown-divider divider-edit"></div>
                <? } ?>
                <?
                foreach ($ExtraLinks as $Link => $Text) {
                    if (!is_array($Text)) { ?>
                        <a class="dropdown-item" href="<?=$Link?>"><?=$Text?></a>
                    <? } else { ?>
                        <a class="dropdown-item" href="javascript:void(0)" <? Utils::AjaxLoadedPopup($Text[0], $Link) ?> title="<?=$Text[0]?>"><?=$Text[1]?></a>
                    <? } ?>
                <? } ?>
                <? if (SecurityShell::isVoucherEditAccess($VoucherTypeId)) { ?>
                    <div class="dropdown-divider divider-edit"></div>
                    <a class="dropdown-item action-edit" href="<?=$this->getEditLink($VoucherTypeId, $Id)?>"><i class="mdi mdi-pencil"></i> Edit</a>
                    <a class="dropdown-item action-edit" <? Utils::AjaxLoadedPopup('Voucher Modification History', ProjectHelper::SiteURL()."_voucher_defaults/ajax_modification_list.php?voucher_class=".get_class($this)."&id=".$Id."&voucher_type_id=".$VoucherTypeId)?>><i class="mdi mdi-clock"></i> History</a>

                    <div class="dropdown-divider divider-edit"></div>
                    <a class="dropdown-item action-copy" href="<?=$this->getCopyVoucherLink($VoucherTypeId, $Id)?>"><i class="mdi mdi-content-copy"></i> Copy</a>
                <? } ?>
            </div>
        </div>
        <?php
    }

    function highlightDeletedRow($Row)
    {
        $this->VoucherStatus = VoucherStatus::setVoucherRowStatus($Row, $this->UnApprovedVouchersList);
    }

    function getVoucherStatus($Row)
    {
        echo "<span class='bg_voucher_status ".VoucherStatus::getColumnClass($this->VoucherStatus)."'>".$this->VoucherStatus."</span>
                  <input type='hidden' name='voucher_status[".$Row['distinct_id']."]' data-js='voucher_status' value='".$this->VoucherStatus."'>";
    }
    /* ************** Report & Links *************** */




    function isDuplicateSubmission(): bool
    {
        return $this->isDuplicateSubmission;
    }

    function isVoucherCreatedSuccessfully()
    {
        if ($this->isDuplicateSubmission)
            return false;

        return !empty($this->getVoucherId());
    }

    /* ************************* Notifications to Customer *********************** */
    function notifyStatusMessage($FormType='')
    {
        if ($this->isDuplicateSubmission)
        {
            $VoucherName = VoucherType::getName($this->getVoucherType());
            SuccessHandler::setMessage($VoucherName." # ".$this->getVoucherNumber()." is already saved (duplicate submission prevented).");
            return;
        }

        if ($this instanceof DefaultVoucherForm)
        {
            $this->updateVoucherTotal($this->getVoucherTotal());
        }

        if ($this->isVoucherCreatedSuccessfully())
            $this->displaySuccessMessages($this->getVoucherNumber());
        else
            $this->displayError();
    }

    protected function displaySuccessMessages($VoucherNumber)
    {
        $VoucherName = VoucherType::getName($this->getVoucherType());

        if (!$this->isEdit() && !$this->isDelete() && !$this->isCopy() && !$this->isDraft())
        {
            SuccessHandler::setMessage($VoucherName." # $VoucherNumber Created Successfully");
        }
        elseif ($this->isCopy())
        {
            Notification::createFromVoucher(Notification::TYPE_VOUCHER_COPY, $this->getDistinctId());
            SuccessHandler::setMessage($VoucherName." # $VoucherNumber Copied Successfully!");
        }
        elseif ($this->isDelete())
        {
            Notification::createFromVoucher(Notification::TYPE_VOUCHER_DELETE, $this->getDistinctId());
            SuccessHandler::setMessage($VoucherName." # $VoucherNumber Deleted Successfully!");
        }
        elseif ($this->isDraft())
        {
            Notification::createFromVoucher(Notification::TYPE_VOUCHER_DELETE, $this->getDistinctId());
            SuccessHandler::setMessage($VoucherName." # $VoucherNumber Saved as a Draft Successfully!");
        }
        else
        {
            Notification::createFromVoucher(Notification::TYPE_VOUCHER_EDIT, $this->getDistinctId());
            SuccessHandler::setMessage($VoucherName." # $VoucherNumber Updated Successfully!");
        }
    }

    protected function displayError($FormType='')
    {
        $VoucherName = VoucherType::getName($this->getVoucherType());

        Notification::create('Error',"Unable to save or update ".$VoucherName, Notification::TYPE_ERROR);
        ErrorHandler::setError("Unable to save or update ".$VoucherName);
    }

    function redirectToStatus($ClassName, $VoucherTypeId)
    {
        if ($this->isOpenNew())
        {
            Utils::redirect(VoucherType::getLink($VoucherTypeId, ''));
        }
        elseif ($this->isDraft())
        {
            if ($VoucherTypeId == VoucherType::BMV OR $VoucherTypeId == VoucherType::SM) {
                Utils::redirect(ProjectHelper::SiteURL() . "Controller/" . VoucherType::getVoucherClass($VoucherTypeId)."?voucher_type_id=".$VoucherTypeId."&id=".$this->getVoucherId());
            }
            else
                Utils::redirect(VoucherType::getLink($VoucherTypeId, $this->getVoucherId()));
        }
        else
        {
            if ($VoucherTypeId == VoucherType::BMV OR $VoucherTypeId == VoucherType::SM)
                Utils::redirect(VoucherType::getDefaultReportLink($VoucherTypeId, $ClassName));
            elseif (empty($ClassName))
            {
                Utils::redirect(VoucherType::getLink($VoucherTypeId, ''));
            }
            else
                Utils::redirect(VoucherType::getDefaultReportLink($VoucherTypeId, $ClassName));
        }

    }

    /* ************************* Notifications to Customer *********************** */




    /* ************************* Misc Operations *********************** */
    function updateVoucherTotal($TotalAmount)
    {
        DBQM::query("UPDATE distinct_id SET voucher_total = ".escape($TotalAmount)." WHERE id = ".escape($this->getDistinctId()));
    }

    private function getVoucherActivityNote()
    {
        if ($this->isNew())
            return '';

        $Output = '<div class="row"><div class="col-12 small pb-3 text-right">';
        $RowDistinctId = $this->oDistinctId->getData();

        $Output .= "<i class='mdi mdi-plus text-success mdi-plus'></i> Created by ".$RowDistinctId['created_by_username']." on ".DateDecode($RowDistinctId['created_date_time'], 'M d, Y @ h:ia');

        if (!empty($RowDistinctId['updated_by_username']))
            $Output .= ", <i class='mdi mdi-pencil text-warning'></i> Updated by ".$RowDistinctId['updated_by_username']." on ".DateDecode($RowDistinctId['updated_date_time'], 'M d, Y @ h:ia');

        if (!empty($RowDistinctId['deleted_by_username']))
            $Output .= ", <i class='mdi mdi-trash-can text-danger'></i> Deleted by ".$RowDistinctId['deleted_by_username']." on ".DateDecode($RowDistinctId['deleted_date_time'], 'M d, Y @ h:ia');

        $Output .= "</div></div>";
        return $Output;
    }

    function showVoucherButtonsWithOptions($VoucherTypes=[], $BulkUpdateAdditionalLinks=[], $VoucherTypesParam=[], $AdditionalVouchersLink=[])
    {
        $VoucherTypesList = empty($VoucherTypes) ? $this->AllowedVoucherTypes : $VoucherTypes;
        $ApproveConfirmationPopUpText = "Are you sure you want to mark these entries as approved?";
        $EmptyAlertOnVoucherSelection = "Please select vouchers then perform any action.";
        ?>
        <style>
            .mab {
                position: relative;
                background: #dddddd;
                display: inline-flex;
            }
            .mab__button {
                background: none;
                border: none;
                display: flex;
                align-items: center;
                justify-content: center;
                padding: 8px 12px;
                color: #333333;
                cursor: pointer;
            }
            .mab__button:hover {
                background: rgba(0,0,0,0.05);
            }
            .mab__button:not(.mab__button--menu) .mab__icon{
                margin-right: 8px;
            }
            .mab__button--secondary{
                width: 100%;
                justify-content: flex-start;
            }
            .mab__text {
                font-family: 'Readex Pro', sans-serif;
            }
            .mab__list {
                position: absolute;
                display: none;
                top: 115%;
                right: 0;
                padding: 5px 0;
                background: #ffffff;
                box-shadow: 0 0 2px rgba(0, 0, 0, 0.2);
                border-radius: 5%;
                width: 200px;
            }
            .mab__list--visible {
                display: initial;
                z-index: 1;
            }
        </style>
        <div class="row mb-3">
            <div class="col-md-12"><h3 class="card-title"> Report</h3></div>
            <div class="col-md-6"><button type="button" name="filter" id="filter" class="btn button bg-success text-white font-weight-bolder w-25 <?=!$this->hasReportFilters ? 'hidden' : ''?>"><i class="fa fa-filter"></i> Filters</button></button></div>
            <div class="col-md-6 text-right">
                <div class="col-auto ml-auto">
                    <div class="mab bg-info">
                        <button type="button" class="mab__button mab__button--primary text-white">
                            <span class="material-icons mab__icon"><i class="fa fa-cog"></i></span>
                            <span class="mab__text">Actions</span>
                        </button>
                        <button type="button" class="mab__button mab__button--menu text-white">
                            <span class="material-icons mab__icon"><i class="fa fa-angle-down"></i></span>
                        </button>
                        <div class="mab__list" style="color: black;">
                            <button formaction="<?=ProjectHelper::SiteURL()?>vouchers/op_approve_voucher.php" type="submit" class="mab__button mab__button--secondary" name="btn_approve" id="btn_approve" value="btn_approve" data-bulk-click="Y" onclick="return confirm('<?=$ApproveConfirmationPopUpText?>');">
                                <span class="mab__text"> Approve all selected Vouchers</span>
                            </button>
                            <? foreach ($BulkUpdateAdditionalLinks as $Button => $AdditionalLink) { ?>
                                <button formaction="<?=ProjectHelper::SiteURL().$AdditionalLink['link']?>" type="submit" class="mab__button mab__button--secondary" name="<?=$Button?>" id="<?=$Button?>" value="<?=$Button?>" data-bulk-click="Y">
                                    <span class="mab__text"><?=$AdditionalLink['text']?></span>
                                </button>
                            <? } ?>
                        </div>
                    </div>
                    <div class="mab <?=!$this->isAddNewOptionByReport ? 'hidden' : ''?>">
                        <button type="button" class="mab__button mab__button--primary color">
                            <span class="material-icons mab__icon"><i class="mdi mdi-plus-circle"></i></span>
                            <span class="mab__text">
                                <a href="<?=VoucherType::getLink($VoucherTypesList[0], '').$VoucherTypesParam[$VoucherTypesList[0]]?>" class="text-white">
                                    Add New
                                </a>
                            </span>
                        </button>
                        <button type="button" class="mab__button mab__button--menu color">
                            <span class="material-icons mab__icon"><i class="fa fa-angle-down"></i></span>
                        </button>
                        <div class="mab__list">
                            <? foreach ($VoucherTypesList as $i => $VoucherTypeId) { if ($i == 0) continue; ?>
                                <a href="<?=VoucherType::getLink($VoucherTypeId, '').$VoucherTypesParam[$VoucherTypeId]?>" class="mab__button mab__button--secondary">
                                    <span class="mab__text"><i class="mdi mdi-plus-circle"></i> <?=VoucherType::getName(str_replace("-New", "", $VoucherTypeId))?></span>
                                </a>
                            <? } ?>
                            <? foreach ($AdditionalVouchersLink as $VouchersLink) { ?>
                                <a href="<?=ProjectHelper::SiteURL().$VouchersLink['page_url']?>" class="mab__button mab__button--secondary">
                                    <span class="mab__text"><i class="mdi mdi-plus-circle"></i> <?=$VouchersLink['page_name']?></span>
                                </a>
                            <? } ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <script>
            $(function (){
                document.querySelectorAll(".mab").forEach(multiAction => {
                    const menuButton = multiAction.querySelector(".mab__button--menu");
                    const list = multiAction.querySelector(".mab__list");

                    menuButton.addEventListener("click", () => {
                        list.classList.toggle("mab__list--visible");
                    });
                });

                document.addEventListener("click", e => {
                    const keepOpen = (
                        e.target.matches(".mab__list")
                        || e.target.matches(".mab__button--menu")
                        || e.target.closest(".mab__button--menu")
                    );
                    if (keepOpen) return;
                    document.querySelectorAll(".mab_list").forEach(list => {
                        list.classList.remove("mab__list--visible");
                    });
                });

                $("button[data-bulk-click='Y']").click(function (e) {
                    var isChecked = $("[data-js='distinct_id']").is(":checked");

                    if (!isChecked) {
                        e.preventDefault();
                        Swal.fire({
                            icon: "warning",
                            text: "<?=$EmptyAlertOnVoucherSelection?>",
                            customClass: {
                                confirmButton: 'color'
                            }
                        });

                        return false;
                    }
                });
            });
        </script>
        <?
    }
    /* ************************* Misc Operations *********************** */


    /* ************************* Additional Text Segment on Voucher ************************ */
    function showTopTextSegment() : void
    {

    }

    function additionalStyle() : void
    {

    }

    function additionalScript() : void
    {

    }
    /* ************************* Additional Text Segment on Voucher ************************ */
}