<?php

interface DefaultVoucherReportAdditionalUtils
{
    /**
     * @param $Row
     * @return array [Link => Text with icon] <br> [Link => [Title, Text with icon] (for Ajax Popup)]
     */
    function additionalReportLinks($Row) : array;

    /**
     * @return array [Button => Button Element Name] <br> [Text (text key) => Button Label, Operational Page Link (link key) => redirect page url with folder name]
     */
    function additionalReportBulkOptionLinks() : array;

    /**
     * @return array [Add manual Voucher types]
     */
    function additionalReportVoucherVoucherTypesForOpenNew() : array;

    /**
     * @return array [Voucher Type Id => "& query string with & operator"]
     */
    function additionalReportAddVoucherTypesQueryParam() : array;

    /**
     * @return array [page_url => "Complete URl with folder name", "page_name" => "Page label"]
     */
    function additionalReportAddOtherPagesLink() : array;
}