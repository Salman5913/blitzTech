<?php


interface DefaultVoucherForm
{
    function getVoucherFormUtils() : BasicVoucherFormUtils;

    function getVoucherTotal() : float;
}