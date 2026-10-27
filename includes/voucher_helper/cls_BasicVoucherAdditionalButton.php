<?php

class BasicVoucherAdditionalButton
{
    protected array $ButtonName = [];
    protected array $ButtonValueText = [];
    protected array $isConfirmationRequired = [];
    protected array $AdditionalCSSClass = [];

    function add(string $ButtonName, string $ButtonValueText, bool $isConfirmationRequired, string $AdditionalCSSClass)
    {
        $this->ButtonName[] = $ButtonName;
        $this->ButtonValueText[] = $ButtonValueText;
        $this->isConfirmationRequired[] = $isConfirmationRequired;
        $this->AdditionalCSSClass[] = $AdditionalCSSClass;
    }

    function getButtons()
    {
        for($i=0; $i<count($this->ButtonName); $i++)
        {
            ?>
            <button class="btn button <?=$this->AdditionalCSSClass[$i]?>" name="<?=$this->ButtonName[$i]?>" id="<?=$this->ButtonName[$i]?>" type="submit"
                    <? if ($this->isConfirmationRequired[$i]) { ?>onclick="return confirm('Are you sure?')"<? } ?> >
                <?=$this->ButtonValueText[$i]?>
            </button>
            <?php
        }
    }
}