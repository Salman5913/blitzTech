<?php


interface DefaultVoucherPrint
{
    /**
     * @return BasicVoucherPrintUtils
     */
    function getVoucherPrintUtilsClass() : BasicVoucherPrintUtils;
}