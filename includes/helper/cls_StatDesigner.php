<?php

class StatDesigner
{
    static function highlighted($BGColor, $Title, $Counter, $Link='', $ColumnClass='', $PercentageValue=false)
    {
        ?>
        <div class="<?=empty($ColumnClass) ? 'col-sm-6 col-lg-2' : $ColumnClass?> highlighted-stats">
            <? self::getHTML($BGColor, $Title, $Counter, $Link, $PercentageValue); ?>
        </div>
        <?php
    }

    static function soft($Color, $Title, $Counter, $Link='', $ColumnClass='', $PercentageValue=false, $AjaxLink='')
    {
        ?>
        <div class="<?=empty($ColumnClass) ? 'col-sm-6 col-lg-2' : $ColumnClass?> <?=$Color?> soft-stats">
            <? self::getHTML('', $Title, $Counter, $Link, $PercentageValue, $AjaxLink); ?>
        </div>
        <?php
    }

    /**
     * @param $BGColor
     * @param $Title
     * @param $Counter
     * @param $Link
     * @param $PercentageValue
     * @param $AjaxLink
     * @return void
     */
    private static function getHTML($BGColor, $Title, $Counter, $Link, $PercentageValue, $AjaxLink='')
    {
        ?>
        <div class="card <?=$BGColor?>">
            <? if (!empty($Link)) { ?>
                <a href="<?=$Link?>" >
                    <h3 class="card-title"><?=$Title?></h3>
                    <div class="counter"><?=$Counter > 0 ? $Counter : '-'?><?=$PercentageValue ? "<small class='font-size-13'>%</small>" : ''?></div>
                </a>
            <? } elseif (!empty($AjaxLink)) { ?>
                <a <? Utils::AjaxLoadedPopup($Title, $AjaxLink) ?> >
                    <h3 class="card-title"><?=$Title?></h3>
                    <div class="counter"><?=$Counter > 0 ? $Counter : '-'?><?=$PercentageValue ? "<small class='font-size-13'>%</small>" : ''?></div>
                </a>
            <? } else { ?>
                <a href="javascript:void(0)" >
                    <h3 class="card-title"><?=$Title?></h3>
                    <div class="counter"><?=$Counter > 0 ? $Counter : '-'?><?=$PercentageValue ? "<small class='font-size-13'>%</small>" : ''?></div>
                </a>
            <? } ?>
        </div>
        <?php
    }
}