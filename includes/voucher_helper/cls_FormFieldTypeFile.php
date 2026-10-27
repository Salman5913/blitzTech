<?php

class FormFieldTypeFile extends FormFieldType
{
    const TYPE_IMAGE = 'image/*';
    const TYPE_PDF = 'application/pdf';
    const TYPE_CSV = '.csv';

    public function __construct(UploadPath $oUploadPath, array $AcceptFileType = [])
    {
        $this->AdditionalDetails['upload_path'] = $oUploadPath;
        $this->AdditionalDetails['accept_type'] = implode(",", $AcceptFileType);
    }

    function getFieldName(): null|string
    {
        return $this->oFormFieldDesigner->getTableIndex() === false ?
                ("main_file[".$this->oFormFieldDesigner->getColumnName()."]") :
                ("data_file[".$this->oFormFieldDesigner->getTableIndex()."][".$this->oFormFieldDesigner->getColumnName()."][]");
    }

    function getFieldDesign($Value, $CommonAttributes): void
    {
        $oUploadPath = $this->getAdditionalDetails()['upload_path'];
        $Path = $oUploadPath->getPath();
        $AcceptType = $this->getAdditionalDetails()['accept_type'];
        ?>
        <div class="row">
            <div class="col-12">
                <div class="<?php if (!empty($Value)) { ?>input-group<? } ?>">
                    <input
                        type="file"
                        <?=$CommonAttributes?>
                        <? if (!empty($AcceptType)) { ?>accept="<?=$AcceptType?>"<? } ?>
                    >
                    <div class="input-group-append">
                        <?php if (!empty($Value)) { ?>
                            <a href="<?=$Path.$Value?>" class="btn btn-orange text-white" target="_blank"><i class="mdi mdi-eye"></i></a>
                        <?php } ?>
                    </div>
                </div>
                <? if ($this->oFormFieldDesigner->getTableIndex() === false) { ?>
                    <input type="hidden" name="_old_<?=$this->oFormFieldDesigner->getColumnName()?>" value="<?=$Value?>">
                <? } else { ?>
                    <input type="hidden" name="_old[<?=$this->oFormFieldDesigner->getTableIndex()?>][<?=$this->oFormFieldDesigner->getColumnName()?>][]" value="<?=$Value?>">
                <? } ?>
            </div>
        </div>
        <?php
    }
}