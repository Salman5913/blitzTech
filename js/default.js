class ProjectAutocomplete extends AutoCompleteHandler {
    attachProjectAutocomplete() {
        /* ******************* Auto Complete Attachment ****************** */

        // Search account
        $("input[data-type='search_account']").autocomplete({
            source: `${SiteURL}chart_of_account/ac_search_account.php`,
            minLength: 0,
            select: (e, ui) => this.handleAccountSelect(e, ui),
        }).change(this.handleInputChange)
            .blur((e) => {
                this.handleAccountBlur($(e.currentTarget));
            });

        // Search account by COA
        $("input[data-type='search_account_coa']").each((_, element) => {
            const $input = $(element);
            $input.autocomplete({
                source: (request, response) => {
                    $.getJSON(`${SiteURL}chart_of_account/ac_search_account_by_coa_id.php`, {
                        term: $input.val(),
                        coa_id: $input.attr("data-coa-id"),
                    }, response);
                },
                minLength: 0,
                select: (e, ui) => this.handleSelectWithTrigger(e, ui, "accountSelected"),
            }).change(this.handleInputChange)
                .blur(() => this.handleBlur($input, "chart_of_account/ajax_get_account_id_by_name", "accountSelected"));
        });

        // Search COA
        $("input[data-type='search_coa']")
            .autocomplete({
                source: `${SiteURL}chart_of_account/ac_search_category.php`,
                minLength: 0,
                select: (e, ui) => this.handleSelectWithTrigger(e, ui, "coaSelected"),
            })
            .change(this.handleInputChange)
            .blur((e) => {
                this.validateInputOnBlur($(e.currentTarget), `${SiteURL}chart_of_account/ajax_get_coa_id_by_name.php`, "coaSelected");
            });


        // Stock
        $("input[data-type='search_stock']")
            .autocomplete({
                source: `${SiteURL}stock/ac_stock_search.php`,
                minLength: 0,
                select: (e, ui) => this.handleSelectWithTrigger(e, ui, "stockSelected"),
            })
            .change(this.handleInputChange)
            .blur((e) => {
                this.validateInputOnBlur($(e.currentTarget),`${SiteURL}chart_of_account/ajax_get_coa_id_by_name.php`, "stockSelected")
            });


        // Search stock by track
        $("input[data-type='search_stock_track']").each((_, element) => {
            const $input = $(element);
            $input.autocomplete({
                source: (request, response) => {
                    $.getJSON(`${SiteURL}stock/ac_search_stock_by_track.php`, {
                        term: $input.val(),
                        track: $input.attr("data-track"),
                    }, response);
                },
                minLength: 0,
                select: (e, ui) => this.handleSelectWithTrigger(e, ui, "stockSelected"),
            }).change(this.handleInputChange)
                .blur(() => this.handleBlur($input, "stock/ajax_get_stock_id_by_name.php", "stockSelected"));
        });
        /* ******************* Auto Complete Attachment ****************** */
    }

    handleAccountSelect(e, ui) {
        AutoCompleteHandler.setAutoCompleteOnSelectValues(e.target, ui);
        $(e.target).attr("data-ac-id", ui.item.id).trigger("accountSelected").focus();
        $(e.target).removeClass("not-allowed");

        if (FormHelper.isAttr($(e.target).attr("data-current-balance-column-name"))) {
            //ChartOfAccount.getAccountBalance($(e.target), ui.item.id, $(e.target).attr("data-current-balance-column-name"));
            //ChartOfAccount.getCurrencyRate($(e.target), ui.item.id, $(e.target).attr("data-currency_rate_column_name"));


            const $input = $(e.target);
            const $currencyRateColumnName = $input.attr("data-currency_rate_column_name");
            const $currentBalanceColumnName = $input.attr("data-current-balance-column-name");

            ChartOfAccount.getAccountBalance($input.closest("tr").find('[data-table-column="'+$currentBalanceColumnName+'"]', $input), ui.item.id, false);
            ChartOfAccount.getCurrencyRate($input.closest("tr").find('[data-table-column="'+$currencyRateColumnName+'"]', $input), ui.item.id);
        }
    }

    handleAccountBlur($input) {
        this.validateInputOnBlur($input, `${SiteURL}chart_of_account/ajax_get_account_id_by_name.php`, "accountSelected");
    }
}