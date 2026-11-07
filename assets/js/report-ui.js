/**
 * Report pages (any page with table.data-table-report / table.printable):
 * - active filters (from the page address) shown as chips above the results; x removes one, "Edit filters" opens the panel
 * - after a search the "Filter Options" card folds up to the chips bar
 * - totals strip: row count and the table footer totals, kept in step with the table search
 * Works with both filter layouts: voucher reports (.filter panel opened by #filter) and "Filter Options" cards.
 */
$(function () {
    var $table = $("table.data-table-report, table.printable").first();
    if (!$table.length)
        return;

    var params = new URLSearchParams(window.location.search);
    var DATE_PAIRS = [["date_from", "date_till"], ["from_date", "till_date"]];

    /* ************* Filter panel of this page ************* */
    var $voucherPanel = $(".filter.premium-filter-wrapper, form .filter").first();
    var $filterCard = $voucherPanel.length ? $() : $("h3.card-title, h4.card-title").filter(function () {
        return /filter/i.test($(this).text());
    }).first().closest(".card");
    var $panel = $voucherPanel.length ? $voucherPanel : $filterCard;
    if (!$panel.length)
        return;

    function fieldOf(name) {
        return $panel.find("[name='" + name + "']").not("[type=hidden], [type=submit], button").first();
    }

    function labelOf($field, name) {
        var id = $field.attr("id");
        var text = id ? $panel.find("label[for='" + id + "']").first().text() : "";
        if (!text)
            text = $field.closest(".form-group").find("label").first().text();
        text = $.trim(text || name.replace(/_id$/, "").replace(/_/g, " "));
        return text.charAt(0).toUpperCase() + text.slice(1).toLowerCase();
    }

    function displayOf($field, value) {
        if ($field.is("select"))
            return $.trim($field.find("option:selected").text()) || value;
        if ($field.is(":checkbox"))
            return $field.is(":checked") ? "Yes" : "";
        return $.trim($field.val()) || value;
    }

    /* ************* Chips from the page address ************* */
    var chips = [], used = {};
    DATE_PAIRS.forEach(function (pair) {
        var from = params.get(pair[0]) || "", till = params.get(pair[1]) || "";
        if (!fieldOf(pair[0]).length && !fieldOf(pair[1]).length)
            return;
        used[pair[0]] = used[pair[1]] = true;
        if (from || till)
            chips.push({label: "Date", text: (displayOf(fieldOf(pair[0]), from) || "…") + " – " + (displayOf(fieldOf(pair[1]), till) || "…"), keys: pair});
    });
    params.forEach(function (value, name) {
        if (used[name] || value === "" || /^(search|submit|action_|_)/.test(name))
            return;
        var $field = fieldOf(name);
        if (!$field.length)
            return;
        used[name] = true;
        var text = displayOf($field, value);
        if (text)
            chips.push({label: labelOf($field, name), text: text, keys: [name]});
    });

    var isSearched = params.has("search") || chips.length > 0;

    function openPanel() {
        if ($voucherPanel.length)
            $("#filter").trigger("click");
        else
            $filterCard.toggleClass("rpt-collapsed");
    }

    if (isSearched) {
        var $bar = $('<div class="rpt-chips" role="group" aria-label="Active filters"></div>');
        chips.forEach(function (chip) {
            var url = new URLSearchParams(window.location.search);
            chip.keys.forEach(function (key) { url.delete(key); });
            if (!url.has("search"))
                url.set("search", "");
            $('<span class="rpt-chip"></span>')
                .append($("<span class='rpt-chip-label'></span>").text(chip.label + ": "))
                .append($("<b></b>").text(chip.text))
                .append($('<a class="rpt-chip-x" aria-label="Remove filter"><i class="mdi mdi-close"></i></a>').attr("href", "?" + url.toString()))
                .appendTo($bar);
        });
        if (!chips.length)
            $bar.append('<span class="rpt-chip-none">No filters</span>');
        $('<button type="button" class="rpt-edit"><i class="mdi mdi-tune-variant"></i> Edit filters</button>').on("click", openPanel).appendTo($bar);

        if ($voucherPanel.length)
            $voucherPanel.before($bar);
        else
            $filterCard.addClass("rpt-filter-card rpt-collapsed").find(".card-title").first().after($bar);
    }

    /* ************* Totals strip from the table footer ************* */
    if ($(".sales-metric-card").length)
        return;   // page has its own summary cards

    var $strip = $('<div class="rpt-totals"></div>');
    $table.closest(".card").before($strip);

    function headerLabels() {
        var labels = [];
        $table.find("thead tr").last().children("th, td").each(function () {
            var span = parseInt($(this).attr("colspan"), 10) || 1;
            for (var i = 0; i < span; i++)
                labels.push($.trim($(this).text()));
        });
        return labels;
    }

    function parseNumber(text) {
        text = $.trim(text).replace(/,/g, "");
        if (!/^\(?-?\d+(\.\d+)?\)?%?$/.test(text))
            return null;
        return text.charAt(0) === "(" ? -parseFloat(text.replace(/[()]/g, "")) : parseFloat(text);
    }

    function rowCount() {
        try {
            return $table.dataTable().fnSettings().fnRecordsDisplay();
        } catch (e) {
            return $table.find("tbody tr").not(":has(.dataTables_empty)").length;
        }
    }

    function renderTotals() {
        var labels = headerLabels(), tiles = [{label: "Rows", value: rowCount().toLocaleString()}];
        var column = 0;
        $table.find("tfoot tr").last().children("td, th").each(function () {
            var span = parseInt($(this).attr("colspan"), 10) || 1, text = $.trim($(this).text());
            if (tiles.length < 5 && parseNumber(text) !== null && labels[column + span - 1])
                tiles.push({label: labels[column + span - 1], value: text});
            column += span;
        });

        $strip.empty().toggle(tiles.length > 1);   // only when the table has footer totals
        tiles.forEach(function (tile) {
            $('<div class="rpt-total"></div>')
                .append($("<span></span>").text(tile.label))
                .append($("<b></b>").text(tile.value))
                .appendTo($strip);
        });
    }

    renderTotals();
    $table.on("draw.dt", function () { setTimeout(renderTotals, 150); });   // footer sums update 100ms after a search
});
