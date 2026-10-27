<?php

interface BasicVoucherAdditionalUtils
{
    function beforeDeleteData() : void;

    function performOtherActionBeforeTransaction() : void;

    function uponVoucherMarkAsDelete() : void;
}