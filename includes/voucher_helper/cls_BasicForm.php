<?php


abstract class BasicForm extends BasicVoucherUtils
{
    protected $TableName;

    /**
     * @var array
     */
    protected $DataTableName = [];
    protected $DataIdColumnName;

    protected $VoucherId;

    protected $isDelete;

    protected $isDraft;

    protected $isOpenNew;

    protected $isClose;

    private $FormType;
    private $MainAccessArea;
    private $SubAccessArea;

    /**
     * @var bool
     */
    private $DeleteDataUponSave = true;

    /**
     * BasicVoucher constructor.
     * @param $TableName
     * @param string|array $DataTableName
     * @param $DataIdColumnName
     * @param $FormType
     */
    function __construct($TableName, $DataTableName, $DataIdColumnName, $FormType)
    {
        $this->TableName = $TableName;

        if (!empty($DataTableName))
            $this->DataTableName = !is_array($DataTableName) ? [0 => $DataTableName] : $DataTableName;

        $this->DataIdColumnName = $DataIdColumnName;
        $this->FormType = $FormType;
    }

    function initForSave()
    {
        $this->VoucherId = $_POST['voucher_id'];
        $this->isDelete = isset($_POST['btn_delete']);

        if (!empty($this->VoucherId))
        {
            self::deleteLinkedData();
        }
    }

    function initForEdit()
    {
        if (!empty($this->SubAccessArea) AND !empty($this->MainAccessArea))
        {
            if (!SecurityShell::isAccess($this->MainAccessArea, $this->SubAccessArea))
                ErrorHandler::UnauthorizedAccess();
        }

        $this->VoucherId = $_GET['id'];
        $this->setHeader();
    }

    function initForView()
    {
        self::initForEdit();
    }

    function setDeleteDataUponSave($Flag=true)
    {
        $this->DeleteDataUponSave = $Flag;
    }

    function setAccessAreas($Main, $Sub)
    {
        $this->MainAccessArea = $Main;
        $this->SubAccessArea = $Sub;
    }

    protected function deleteLinkedData()
    {
        if ($this->DeleteDataUponSave)
        {
            if ($this instanceof BasicVoucherAdditionalUtils)
                $this->beforeDeleteData();

            $this->deleteData();
        }
    }


    function showVoucherButtons()
    {
        if ($this->isEdit())
            $ButtonText = "Update ".$this->FormType;
        else
            $ButtonText = "Save ".$this->FormType;
        ?>
        <div class="row form-group voucher-buttons">
            <? if ($this->isEdit()) { ?>
                <div class="col-auto mr-auto">
                    <input type="hidden" name="voucher_id" id="voucher_id" value="<?=$this->getVoucherId()?>">
                    <button class="btn button btn-danger pull-left margin-10" name="btn_delete" id="btn_delete" type="submit" onclick="return confirm('Are you sure you want to delete this?')"><i class="mdi mdi-trash-can-outline"></i> Delete <?=$this->FormType?></button>
                </div>
            <? } ?>

            <div class="col-auto ml-auto">
                <div class="btn-group">
                    <button class="btn button color" name="submit" type="submit"><i class="mdi mdi-content-save"></i> <?=$ButtonText?></button>
                </div>
            </div>
        </div>

        <?
        $this->disableEditIfNoPermission($this->isEdit());
        $this->displayForViewPurpose();
    }




    /* ************** Report & Links *************** */
    function getReportButtons($Id, $FileName, array $ExtraLinks=[], $AdditionalQueryParams='')
    {
        ?>
        <div class="btn-group">
            <a class="btn color btn-sm" href="<?=$FileName?>?id=<?=$Id?>&view=true" target="_blank"><i class="mdi mdi-eye"></i> View</a>
            <button class="btn btn-sm color dropdown-toggle dropdown-toggle-split" data-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                <i class="mdi mdi-chevron-down"></i>
            </button>
            <div class="dropdown-menu dropdown-menu-right">
                <?
                foreach ($ExtraLinks as $Link => $Text) {
                    if (!is_array($Text)) { ?>
                        <a class="dropdown-item" href="<?=$Link?>"><?=$Text?></a>
                    <? } else { ?>
                        <a class="dropdown-item" href="javascript:void(0)" <? Utils::AjaxLoadedPopup($Text[0], $Link) ?> title="<?=$Text[0]?>"><?=$Text[1]?></a>
                    <? } ?>
                <? } ?>
                <a class="dropdown-item action-edit" href="<?=$FileName?>?id=<?=$Id?><?=$AdditionalQueryParams?>"><i class="mdi mdi-pencil"></i> Edit</a>
            </div>
        </div>
        <?php
    }
    /* ************** Report & Links *************** */




    /* ***************** Insert / Update Data ******************* */
    function saveMainTableData($Values)
    {
        $oDatabaseInsertUpdateHelper = new DatabaseInsertUpdateHelper($this->TableName, 0);
        $this->setVoucherId($oDatabaseInsertUpdateHelper->saveData($Values, $this->getVoucherId()));
    }

    function saveDataTableData($Values, $TableIndex=0)
    {
        $Values = array_merge([$this->getVoucherId()], $Values);

        $oDatabaseInsertUpdateHelper = new DatabaseInsertUpdateHelper($this->DataTableName[$TableIndex], 0);
        $oDatabaseInsertUpdateHelper->saveData($Values, 0);
    }

    function deleteFormData()
    {
        DBQM::query("DELETE FROM `{$this->TableName}` WHERE id = ".escape($this->getVoucherId()));
    }
    /* ***************** Insert / Update Data ******************* */



    /* **************************** Boilerplate Functions *************************** */
    function getDetails()
    {
        $Select = DBQM::query("SELECT * FROM {$this->TableName} WHERE id = ".escape($this->VoucherId, 'int'));
        return DBQM::fetch_assoc($Select);
    }

    protected function deleteData()
    {
        foreach ($this->DataTableName as $TableName)
            if (!empty($TableName))
                DBQM::query("DELETE FROM {$TableName} WHERE {$this->DataIdColumnName} = ".escape($this->VoucherId));
    }

    function getData($TableIndex=0, array $Ids=[])
    {
        $Ids = implode(", ", array_map("escape", array_merge($Ids, [$this->VoucherId])));
        return DBQM::query("SELECT * FROM {$this->DataTableName[$TableIndex]} WHERE {$this->DataIdColumnName} IN ($Ids)");
    }

    function getVoucherIdByVoucherNumber($VoucherTypeId, $VoucherNumber)
    {
        $Select = DBQM::query("SELECT t.id FROM `{$this->TableName}` t, distinct_id di WHERE di.id = t.distinct_id AND voucher_type_id = ".escape($VoucherTypeId, 'int')." AND voucher_number = ".escape($VoucherNumber, 'int'));
        return DBQM::result($Select, 0);
    }

    function getVoucherNumberById($VoucherId)
    {
        $Select = DBQM::query("SELECT di.voucher_number FROM `{$this->TableName}` t, distinct_id di WHERE di.id = t.distinct_id AND t.id = ".escape($VoucherId, 'int'));
        return DBQM::result($Select, 0);
    }
    /* **************************** Boilerplate Functions *************************** */




    /* **************************** Uniqueness Check *************************** */
    function checkForUnique($ColumnName, $Value)
    {
        UniquenessChecker::checkForUnique($this->TableName, $ColumnName, $Value);
    }
    /* **************************** Uniqueness Check *************************** */




    /* *********************** Flag Questions ******************** */
    function isEdit()
    {
        return (!empty($_POST['voucher_id']) or !empty($_GET['id']));
    }

    function isCopy()
    {
        return isset($_GET['copy']);
    }

    function isDelete()
    {
        return $this->isDelete;
    }

    function isDraft()
    {
        return $this->isDraft;
    }

    function isOpenNew()
    {
        return $this->isOpenNew;
    }

    function isClose()
    {
        return $this->isClose;
    }

    function isNew()
    {
        return (!$this->isEdit() and !$this->isDelete());
    }

    function isView()
    {
        return isset($_GET['view']);
    }

    function isVoucherCreatedSuccessfully()
    {
        return !empty($this->getVoucherId());
    }

    function isValidFormSubmission()
    {
        return isset($_POST["submit"]) or isset($_POST['btn_delete']) or isset($_POST['btn_new']) or isset($_POST['btn_close']) or isset($_POST['btn_draft']);
    }

    function getFormType()
    {
        return $this->FormType;
    }
    /* *********************** Flag Questions ******************** */



    /* ********************** Voucher Id & Numbers *************************** */
    function setVoucherId($VoucherId)
    {
        $this->VoucherId = $VoucherId;
    }

    function getVoucherId()
    {
        return $this->VoucherId;
    }

    /**
     * Called when voucher is copied
     * @return void
     */
    function unsetEdit()
    {
        unset($_GET['id']);
    }
    /* ********************** Voucher Id & Numbers *************************** */


    /* ********************** Setting Page Headers *************************** */
    private function setHeader()
    {
        $_GET['_page_title'] = $this->FormType;
        $_GET['_page_title_text'] = ($this->isCopy() ? "Copy " : ($this->isEdit() ? "Edit " : "New ")).$this->FormType;
    }
    /* ********************** Setting Page Headers *************************** */



    /* ************************* Notifications to Customer *********************** */
    function notifyStatusMessage()
    {
        if ($this->isVoucherCreatedSuccessfully())
            $this->displaySuccessMessages($this->VoucherId ?? '');
        else
            $this->displayError();
    }

    protected function displaySuccessMessages($VoucherNumber)
    {
        if (!$this->isEdit() && !$this->isDelete())
        {
            Notification::create($this->FormType.' Created', $this->FormType." Created Successfully", Notification::TYPE_VOUCHER_SAVE);

            SuccessHandler::setMessage($this->FormType." Created Successfully");
        }
        elseif ($this->isDelete())
        {
            if (!empty($this->getDetails()['id']))
            {
                ErrorHandler::setError("Unable to delete ".$this->FormType.", this data might have been in use somewhere");
            }
            else
            {
                Notification::create($this->FormType.' Deleted',$this->FormType." Deleted Successfully", Notification::TYPE_VOUCHER_DELETE);

                SuccessHandler::setMessage($this->FormType." Deleted Successfully");
            }
        }
        else
        {
            Notification::create($this->FormType.' Updated',$this->FormType." Updated Successfully", Notification::TYPE_VOUCHER_EDIT);

            SuccessHandler::setMessage($this->FormType." Updated Successfully");
        }
    }

    protected function displayError()
    {
        Notification::create('Error',"Unable to save or update ".$this->FormType, Notification::TYPE_ERROR);
        ErrorHandler::setError("Unable to save or update ".$this->FormType);
    }

    function showVoucherButtonsWithOptions($FormTypes=[], $BulkUpdateAdditionalLinks=[], $hasFilters=true) { ?>
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
            <div class="col-md-6"><button type="button" name="filter" id="filter" class="btn button bg-success text-white font-weight-bolder w-25 <?=!$hasFilters ? 'hidden' : ''?>"><i class="fa fa-filter"></i> Filters</button></div>
            <div class="col-md-6 text-right">
                <div class="col-auto ml-auto">
                    <div class="mab">
                        <button type="button" class="mab__button mab__button--primary color">
                            <span class="material-icons mab__icon"><i class="mdi mdi-plus-circle"></i></span>
                            <span class="mab__text">
                                <? $ClassZeroKey = empty($FormTypes[0]["form_class"]) ? get_class($this) : $FormTypes[0]["form_class"]; ?>
                            <a href="<?=FormType::getLink($FormTypes[0]["form_type"], $ClassZeroKey).$FormTypes[0]['additional_param']?>" class="text-white">Add New</a>
                            </span>
                        </button>
                        <button type="button" class="mab__button mab__button--menu color">
                            <span class="material-icons mab__icon"><i class="fa fa-angle-down"></i></span>
                        </button>
                        <div class="mab__list">
                            <? foreach ($FormTypes as $i => $FormType) { if ($i == 0) continue; $Class = empty($FormType["form_class"]) ? get_class($this) : $FormType["form_class"]; ?>
                                <a href="<?=FormType::getLink($FormType["form_type"], $Class).$FormType['additional_param']?>" class="mab__button mab__button--secondary">
                                    <span class="mab__text"><i class="mdi mdi-plus-circle"></i> <?=$FormType["form_type"]?></span>
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
            });
        </script>
        <?
    }
    /* ************************* Notifications to Customer *********************** */

    /**
     * @return mixed
     */
    function getTableName()
    {
        return $this->TableName;
    }

    /**
     * @return array
     */
    public function getDataTableName()
    {
        return $this->DataTableName;
    }

    /**
     * @return mixed
     */
    public function getDataIdColumnName()
    {
        return $this->DataIdColumnName;
    }
}