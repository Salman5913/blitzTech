<?php
/**
 * Created by PhpStorm.
 * User: Mustafa
 * Date: 27/12/2014
 * Time: 9:22 AM
 */

class StockBatchTransition
{
    function saveRecord($BatchId, $BranchId, $StockId, $AttributeId, $DistinctId, $BatchNo, $QtyIn, $QtyOut)
    {
        $AttributeId = $AttributeId == 0 ? NULL : $AttributeId;
        DBQM::query("INSERT INTO st_stock_batch_transition VALUES (
                                                              0,
                                                              ".escape(Year::getYearId(), 'int').",
                                                              ".escape($BatchId, 'int').",
                                                              ".escape($BranchId, 'int').",
                                                              ".escape($StockId, 'int').",
                                                              ".escape($AttributeId, 'int', true).",
                                                              ".escape($DistinctId, 'int').",
                                                              ".escape($BatchNo, 'int').",
                                                              ".escape($QtyIn, 'double').",
                                                              ".escape($QtyOut, 'double')."
        )");

        return DatabaseHelper::getLastInsertId();
    }

    function getBatchTransitionByDistinctId($DistinctId, $StockId, $AttributeId)
    {
        $Where = UtilsQueryMaker::equalOrNull("attribute_id", $AttributeId);
        $Select = DBQM::query("SELECT * FROM st_stock_batch_transition WHERE distinct_id = ".escape($DistinctId, 'int')." AND stock_id = ".escape($StockId)." ".$Where." ORDER BY id ASC limit 1");

        if (DatabaseHelper::isRecordSetEmpty($Select))
            $Select = DBQM::query("SELECT * FROM st_stock_batch_transition WHERE stock_id = ".escape($StockId)." ".$Where);

        return DBQM::fetch_assoc($Select);
    }

    static function getBatchIId($BatchTransitionId)
    {
        $Select = DBQM::query("SELECT batch_id FROM st_stock_batch_transition WHERE id = ".escape($BatchTransitionId));
        return DBQM::result($Select, 0);
    }

    function delete($BatchTransitionId)
    {
        DBQM::query("DELETE FROM st_stock_batch_transition WHERE id = ".escape($BatchTransitionId));
    }
}