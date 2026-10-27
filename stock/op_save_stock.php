<?php
include_once("../connection/localhost.php");

$oStock = new Stock();

if ($oStock->isValidFormSubmission())
{
    $oStock->initForSave();

    $oStock->saveMainTableData([
        $_POST['category_id'],
        $_POST['item_name'],
        $_POST['item_name'],
        $_POST['brand'],
        $_POST['reorder'],
        $_POST['main_barcode'],
        $_POST['sp'],
        $_POST['is_track'] ?? 'N',
        $_POST['stock_unit']
    ]);


    if ($oStock->isVoucherCreatedSuccessfully())
    {
        for ($i=0; $i<count($_POST['attribute_id']); $i++)
        {
            if (!empty($_POST['attribute_id'][$i]))
            {
                $oStock->saveDataTableData([
                    $_POST['attribute_id'][$i],
                    $_POST['barcode'][$i],
                    $_POST['selling_price'][$i],
                    $_POST['reorder_level'][$i],
                    $_POST['is_inactive'][$i],
                    $_POST['attr_unit'][$i]
                ], Stock::TABLE_ATTRIBUTE);
            }
        }
    }

    /* ************************* Restaurant website menu ***************************** */
    if ($oStock->isVoucherCreatedSuccessfully() || $oStock->isDelete())
    {
        RestaurantMenu::saveFromStockForm(
            $oStock->getVoucherId(),
            !$oStock->isDelete() && ($_POST['show_on_website'] ?? '') == 'Y',
            $_POST['website_category'] ?? ''
        );
        RestaurantMenu::saveHeroFromStockForm(
            $oStock->getVoucherId(),
            !$oStock->isDelete() && ($_POST['show_on_hero'] ?? '') == 'Y'
        );
    }

    if ($oStock->isDelete())
        $oStock->deleteFormData();

    $oStock->notifyStatusMessage();
    Utils::redirectToReferer();
}
else
    ErrorHandler::UnauthorizedAccess();