<?php

class Quotation extends BasicVoucher implements DefaultVoucherForm, DefaultVoucherReport, DefaultVoucherReportAdditionalUtils
{
    const IND_DATA = 'DATA';
    const IND_INVENTORY = 'INVENTORY';
    private $VoucherTotal = 0;
    private $Terms;
    public function __construct()
    {
        parent::__construct("qt_quote",
            [
                self::IND_DATA => "qt_quote_data",
                self::IND_INVENTORY => "qt_quote_inventory"
            ], 'quote_id', VoucherType::QTV, false);

        $this->setAutoPrint(true);
        $this->setProject(true, true);
        $this->getDefaultTerms();
    }

    protected function saveTransactionEntries()
    {
        $RowDetails = $this->getDetails();
        $QuotationTotal = 0;

        $SelectData = $this->getData(self::IND_DATA);
        $SelectInventory = $this->getData(self::IND_INVENTORY);

        while ($RowData = DBQM::fetch_assoc($SelectData))
            $QuotationTotal += $RowData['qty'] * $RowData['rate'];

        while ($RowInventory = DBQM::fetch_assoc($SelectInventory))
            $QuotationTotal += $RowInventory['inventory_qty'] * $RowInventory['inventory_rate'];

        $this->VoucherTotal = $QuotationTotal;
    }

    function getVoucherFormUtils(): BasicVoucherFormUtils
    {
        $this->setTermsOnNew();
        
        $oFormUtils = new BasicVoucherFormUtils();
        $oFormUtils->attachJS(ProjectHelper::SiteURL()."js_live/project/quote.min.js");

        $oFormUtils->setHiddenFields(['calc_discount', 'calc_tax', 'tax_percentage', 'attribute_id']);
        $oFormUtils->setRequiredFields(['subject', 'quote_version']);
        $oFormUtils->setColumnDisplayType('terms', new FormFieldTypeEditor());
        $oFormUtils->setColumnDisplayType("image_name", new FormFieldTypeFile(new UploadPath(get_class())));
        $oFormUtils->setAutocomplete("stock_id", Autocomplete::TYPE_STOCK);

        $oFormUtils->addDefaultGroup('col-md-4', 'col-md-12');
        $oFormUtils->addElementGroup('Customer Details', 'col-md-4', 'col-md-12', [
            'customer_name',
            'company_name',
            'mobile'
        ]);
        $oFormUtils->addElementGroup('Quote Status', 'col-md-4', 'col-md-12', ['subject', 'is_approved', 'image_name']);
        $oFormUtils->addElementGroup('Terms & Condition', 'col-md-8', 'col-md-12', ['terms']);

        $oFormUtils->setColumnWidth([
            "item_description" => "50%",
            "qty" => "17%",
            "rate" => "17%",
            "total" => "15%",
            "stock_id" => "50%",
            "inventory_qty" => "17%",
            "inventory_rate" => "17%",
            "inventory_total" => "15%"
        ]);
        $oFormUtils->addAdditionalColumns(self::IND_DATA, "total", "float", "last_column", "text-right");
        $oFormUtils->addAdditionalColumns(self::IND_INVENTORY, "inventory_total", "float", "last_column", "text-right");
        $oFormUtils->showColumnTotal(['qty', 'inventory_qty', 'total', 'inventory_total']);

        return $oFormUtils;
    }

    function getVoucherTotal(): float
    {
        return $this->VoucherTotal;
    }

    function getReportFilters(): BasicVoucherReportFilters
    {
        $oReportFilter = new BasicVoucherReportFilters();
        $oReportFilter->setFilter('voucher_date', 'Date', 'date');
        $oReportFilter->setFilter('voucher_number', 'Voucher #', '');
        $oReportFilter->setFilter('project_id', 'Project', '', '',Autocomplete::TYPE_PROJECT);
        $oReportFilter->setFilter('customer_name', 'Customer Name', '', '', Autocomplete::TYPE_QUOTATION_CUSTOMER);
        $oReportFilter->setFilter('mobile', 'Mobile', '');
        $oReportFilter->setFilter('description', 'Description', '');

        return $oReportFilter;
    }

    function getSearchQuery(): mysqli_result
    {
        $Where = "";
        if (empty($_GET['voucher_number']))
        {
            $Where .= UtilsQueryMaker::equal("project_id", $_GET['project_id']);
            $Where .= UtilsQueryMaker::like("q.mobile", $_GET['mobile']);
            $Where .= UtilsQueryMaker::like("customer_name", $_GET['customer_name']);
            $Where .= UtilsQueryMaker::like("description", $_GET['description']);
            $Where .= UtilsQueryMaker::date("d.voucher_date", $_GET['date_from'], $_GET['date_till']);
        }
        else
        {
            $Where = UtilsQueryMaker::equal("voucher_number", $_GET['voucher_number']);
        }

        $Query = "
            SELECT
                d.voucher_date,
                d.voucher_number,
                d.voucher_type_id,
                d.voucher_total,
                d.is_deleted,
                d.is_draft,
                q.*,
                a.`name` AS project_name,
                CONCAT(q.company_name, '<br>', q.customer_name, '<br>', q.mobile) AS customer_info,
                q.subject AS subject_info,
                q.mobile AS mobile
            FROM
                qt_quote q,
                distinct_id d
                LEFT JOIN prj_project a ON d.project_id = a.id
            WHERE
                d.id = q.distinct_id
                {$Where}
            GROUP BY
                q.id 
            ORDER BY 
                d.voucher_date DESC
        ";

        return DBQM::query($Query);
    }

    function getReportTableColumn(): BasicVoucherReportTableColumn
    {
        $oTableColumn = new BasicVoucherReportTableColumn();
        $oTableColumn->addColumn('voucher_date', 'Date', BasicVoucherReportTableColumn::TYPE_DATE);
        $oTableColumn->addColumn('voucher_number', 'Voucher #');
        $oTableColumn->addColumn('customer_info', 'Customer');
        $oTableColumn->addColumn('project_name', 'Project');
        $oTableColumn->addColumn('subject_info', 'Subject');
        $oTableColumn->addColumn('description', 'Description');
        $oTableColumn->addColumn('voucher_total', 'Total', BasicVoucherReportTableColumn::TYPE_FLOAT);

        return $oTableColumn;
    }

    function additionalReportLinks($Row): array
    {
        $AdditionalLink = [];
        $AdditionalLink[VoucherType::getLink(VoucherType::QTEV, $Row['estimate_id'])."&quote_id=".$Row['id']] = '<i class="mdi mdi-file-document-box-check"></i> Estimate';

        return $AdditionalLink;
    }

    function highlightDeletedRow($Row)
    {
        parent::highlightDeletedRow($Row); // TODO: Change the autogenerated stub

        if ($Row['is_approved'] == 'Y')
            echo " bg-soft-success ";
        else
            echo "";
    }

    function setTermsOnNew()
    {
        if ($this->isNew())
            $_GET['terms'] = $this->Terms;
    }

    static function getQuoteVoucherNumber($QuoteId)
    {
        $Select = DBQM::query("SELECT d.voucher_number, pr.ac_project FROM qt_quote q, distinct_id d LEFT JOIN vw_ac_project pr ON pr.id = d.project_id WHERE q.distinct_id = d.id AND q.id = ".escape($QuoteId));
        return array(DBQM::result($Select, 0), DBQM::result($Select, 0, 1));
    }

    function getDefaultTerms()
    {
        $this->Terms = "Payment: 
70% advance with the order.
20% time of delivery.
10% before fly proofing mesh (netting) delivery.								
Any sort of dismantling is not included in the said quotation.								
Local paint is of abc matt paint.								
All windows & glass will be measured and invoiced at 6\" standard sizing policy.								
\"in casement windows, if grill provision is required along with fly proofing mesh, an additional white powder coated aluminum frame would be installed, which will be charges at rs. 375/r.ft\"								
Door closer is not included in the said quotation and will be charged separately if required.								
Scaffolding & ladder will be provided by the client, if necessary.								
All windows will be fabricated 4mm-5mm short from actual size, set for tolerance margin for expansion & contraction.
Client will be responsible for filling of more than 5mm of gap, between concrete & window/door.
Millwala is not responsible for cleaning of windows, doors and glass, hence, it will be charged separately if requested.	
Delivery of material: 30 days.								
\"The above mention quotation is based on the sizes provided by the client. any variation in size error etc. will be liable to adjustment +/- as per actual.\"	
All door & window material is of, chinese origin and european upvc standard & types.							
\"All window & door profiles are iso:9001, iso: 14001 & ohsas:18001 certified, according to eacc, iaf & cms management systems, and certified by cpd compliance, \"\"auger testing certificate\"\".\"					
Prices are valid for 7 days.								
Any sort of taxes are not included in the above said quotation.";
    }

    function additionalReportBulkOptionLinks(): array
    {
        return [];
    }

    function additionalReportVoucherVoucherTypesForOpenNew(): array
    {
        return [];
    }

    function additionalReportAddVoucherTypesQueryParam(): array
    {
        return [];
    }

    function additionalReportAddOtherPagesLink(): array
    {
        return [];
    }
}