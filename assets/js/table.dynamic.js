class DynamicTable {
    constructor(selector) {
        this.selector = selector;
        this.table = $(selector);
        this.d_sno = 1;

        this.init();
    }

    init() {
        this.setupTable();
        this.bindEvents();
        this.attachSumInput();
    }

    setupTable() {
        this.table.each((_, table) => {
            const $table = $(table);
            this.setupTableRows($table);
            this.setupTableHeadersAndFooters($table);
            this.toggleDeleteButtonVisibility($table);
            this.attachTableManager($table);
        });
    }

    setupTableRows($table) {
        $table.find("> tbody > tr").each((_, row) => {
            const $row = $(row);
            $row.append(this.getDeleteButtonHTML());
            $row.prepend(this.getAddButtonHTML() + this.getSnoHTML());
        });
    }

    setupTableHeadersAndFooters($table) {
        $table.find("> thead > tr").each((_, row) => {
            const $row = $(row);
            $row.append('<th class="action">&nbsp;</th>');
            $row.prepend('<th class="action">&nbsp;</th><th class="dt_sno">&nbsp;</th>');
        });

        $table.find("> tfoot > tr").each((_, row) => {
            const $row = $(row);
            $row.append('<td class="action">&nbsp;</td>');
            $row.prepend('<td class="action">&nbsp;</td><td class="dt_sno">&nbsp;</td>');
        });
    }

    toggleDeleteButtonVisibility($table) {
        const hasMultipleRows = $table.find("tbody tr").length > 1;
        $table.find("tbody tr button.dt-del").toggle(hasMultipleRows);
    }

    attachTableManager($table) {
        $table.on("blur", "input, textarea", () => this.manageTable($table));
    }

    bindEvents() {
        this.table.on('click', '[data-js="dt-add-row"]', (e) => this.addRow($(e.target)));
        this.table.on('click', '[data-js="dt-del-row"]', (e) => this.deleteRow($(e.target)));
    }

    addRow($button) {
        const $table = $button.closest("table.dynamic");
        const $row = this.createEmptyRow($table);
        $button.closest("tr").after($row);

        this.updateRowNumbers($table);
        this.reinitializePlugins();
        this.toggleDeleteButtonVisibility($table);
        $table.trigger("rowUpdated");
    }

    deleteRow($button) {
        const $table = $button.closest("table.dynamic");
        if (confirm('Are you sure you want to delete?')) {
            $button.closest("tr").remove();
            this.updateRowNumbers($table);
            this.toggleDeleteButtonVisibility($table);
            $table.trigger("rowUpdated");
        }
    }

    manageTable($table) {
        const $lastRow = $table.find("tbody > tr:last");
        if (!this.isLastRowEmpty($lastRow)) {
            $table.append(this.createEmptyRow($table));
            this.updateRowNumbers($table);
            this.reinitializePlugins();
            this.toggleDeleteButtonVisibility($table);
            $table.trigger("rowUpdated");
        }
    }

    isLastRowEmpty($row) {
        let isEmpty = true;
        $row.find("input:text:not([data-type='date']):visible, textarea:visible").each((_, input) => {
            const $input = $(input);
            const value = $input.val();
            if (
                (['int', 'float'].includes($input.data("type")) && value !== '0' && value !== '0.00' && value !== '') ||
                (!$input.data("type") && value !== '')
            ) {
                isEmpty = false;
            }
        });
        return isEmpty;
    }

    createEmptyRow($table) {
        const sampleRowHTML = $table.find("> tbody > tr:first-child").html();
        const $newRow = $(`<tr>${sampleRowHTML}</tr>`);

        $newRow.find("input[data-ac-id]").removeAttr("data-ac-id");
        $newRow.find("input, select, textarea").val('').show().removeClass('ui-autocomplete-input');
        $newRow.find(".editable-hidden-element-value").remove();
        $newRow.find("input[data-type='date']").removeAttr("id").removeClass('hasDatepicker');
        $newRow.find('[data-display="full-name"], tbody > tr:last [data-js="qty-available"]').html('');
        $newRow.find(".editable-hidden-element-value").remove();
        $newRow.find("input").removeClass('ui-autocomplete-input');
        $newRow.find("textarea").removeClass('ui-autocomplete-input');
        $newRow.find(".text").html('');
        return $newRow;
    }

    updateRowNumbers($table) {
        let sno = 1;
        $table.find("> tbody > tr").each((_, row) => {
            $(row).find(".dt_sno").html(sno++);
        });
    }

    reinitializePlugins() {
        App.reinitializeComponents();
    }

    attachSumInput() {
        $("[data-input-sum]").each((_, element) => {
            const $element = $(element);
            const inputSelector = $element.attr("data-input-sum");
            const elementType = $element.attr("data-input-sum-element-type");
            const elementName = $element.attr("data-input-element-name");

            if (elementType === 'html') {
                var targetNode = document.querySelectorAll(inputSelector);
                var observer = new MutationObserver( () => this.sumInputs(inputSelector, $element, elementName));

                for (var i=0; i<targetNode.length; i++)
                {
                    observer.observe(targetNode[i], { childList: true, subtree: true });
                }

                setTimeout(() => {
                    if ($(inputSelector).length) {
                        this.sumInputs(inputSelector, $element, elementName);
                    }
                }, 500);

                //$(inputSelector).on('DOMSubtreeModified', () => this.sumInputs(inputSelector, $element, elementName));
            } else {
                $('body').on('change', inputSelector, () => this.sumInputs(inputSelector, $element, elementName));
            }
        });
    }

    sumInputs(inputSelector, $footerElement, elementName) {
        let sum = 0;

        $(inputSelector).each((_, input) => {
            const $input = $(input);

            if ($input.is("input")) {
                sum += NumberHelper.parseInput($input);
            }
            else {
                var value = AutoNumeric.unformat($input[0]);
                sum += (value === '' ? 0 : parseFloat(value));
            }
        });

        $footerElement.html(NumberHelper.thousandSep(sum));
        this.updateOverviewTotals(elementName, sum);
    }

    updateOverviewTotals(elementName, sum) {
        const $overviewTotal = $(`.total_overview_${elementName}`);
        if ($overviewTotal.length) {
            $overviewTotal.html(NumberHelper.thousandSep(sum)).attr("data-overview-totals-unformatted", sum);

            let voucherTotal = 0;
            $("[data-overview-totals]").each((_, element) => {
                const total = parseFloat($(element).attr("data-overview-totals-unformatted")) || 0;
                voucherTotal += total;
            });

            $(".total_overview_voucher_total").html(NumberHelper.thousandSep(voucherTotal));
        }
    }

    getAddButtonHTML() {
        return `
            <td class="action" width="20">
                <button type="button" class="dt-action-btn dt-add" data-js="dt-add-row">
                    <i class="fa fa-plus"></i>
                </button>
            </td>
        `;
    }

    getDeleteButtonHTML() {
        return `
            <td class="action" width="20">
                <button type="button" class="dt-action-btn dt-del" data-js="dt-del-row">
                    <i class="fa fa-times"></i>
                </button>
            </td>
        `;
    }

    getSnoHTML() {
        return `<td class="dt_sno text-center" width="15">${this.d_sno++}</td>`;
    }
}

// Initialize the DynamicTable class
$(function() {
    new DynamicTable("table.dynamic:not(.non-editable)");
});