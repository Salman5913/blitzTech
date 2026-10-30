/**
 * POS - Order Invoice Voucher screen (pos/pos_voucher.php)
 * Server data comes from window.POS_CONFIG: catalog, cart, isView, posUrl
 */
$(function () {
    var CONFIG = window.POS_CONFIG || {};
    var CATALOG = CONFIG.catalog || [];
    var CART = CONFIG.cart || [];
    var IS_VIEW = !!CONFIG.isView;
    var POS_URL = CONFIG.posUrl;
    var COLORS = ['#0b3866', '#7a5af8', '#12b76a', '#f79009', '#ee46bc', '#2e90fa', '#f04438', '#15b79e'];
    var menuStatus = {};   // Restaurant > Website Menu (POS) of selected warehouse: sold out / hidden items
    var hiddenCategories = {};
    var activeCategory = '';

    function money(n) { return NumberHelper.thousandSep(NumberHelper.round(n)); }
    function statusOf(id) { return menuStatus[id] || {available: true, visible: true}; }
    function findItem(id) { return CATALOG.find(function (i) { return i.id === id; }); }
    function cartLine(id) { return CART.find(function (l) { return l.id === id; }); }
    function initials(name) { return (name || '?').substr(0, 2).toUpperCase(); }
    function colorOf(item) { return COLORS[Math.abs(item ? item.category_id : 0) % COLORS.length]; }

    /* picture of item: uploaded image > emoji > coloured initials */
    function mediaHtml($box, item, name) {
        if (item && item.image) {
            var $img = $('<img loading="lazy" alt="">').attr("src", item.image).on("error", function () {
                $(this).replaceWith($('<span class="pos-ph">').css("background", colorOf(item)).text(initials(name)));
            });
            return $box.append($img);
        }
        if (item && item.emoji)
            return $box.append($('<span class="pos-emoji">').text(item.emoji));

        return $box.append($('<span class="pos-ph">').css("background", colorOf(item)).text(initials(name)));
    }

    function stepperHtml(qty, light) {
        return $('<div class="pos-step">').toggleClass("light", !!light)
            .append('<button type="button" data-js="qty-minus" aria-label="Decrease"><i class="mdi mdi-minus"></i></button>')
            .append($('<input type="text" inputmode="decimal" data-js="qty-input" aria-label="Quantity">').val(qty))
            .append('<button type="button" data-js="qty-plus" aria-label="Increase"><i class="mdi mdi-plus"></i></button>');
    }

    /* ************* Items grid (category wise) ************* */
    function renderItems() {
        var term = $.trim($("#pos_search").val()).toLowerCase();
        var $wrap = $("#pos_items").empty();
        var groups = {};
        var order = [];

        CATALOG.forEach(function (item) {
            if (activeCategory !== '' && String(item.category_id) !== String(activeCategory))
                return;
            if (term !== '' && item.name.toLowerCase().indexOf(term) === -1 && item.barcode.toLowerCase() !== term)
                return;
            if (!statusOf(item.id).visible || hiddenCategories[item.category_id])
                return;

            if (!groups[item.category]) { groups[item.category] = []; order.push(item.category); }
            groups[item.category].push(item);
        });

        if (!order.length) {
            $wrap.append('<div class="pos-empty"><i class="mdi mdi-package-variant-closed"></i>No items found</div>');
            return;
        }

        order.forEach(function (category) {
            $wrap.append($('<div class="pos-group-title">').text(category));
            var $grid = $('<div class="pos-grid">');

            groups[category].forEach(function (item) {
                var isAvailable = statusOf(item.id).available;
                var $card = $('<div class="pos-item" role="button" tabindex="0">').attr("data-id", item.id).toggleClass("sold-out", !isAvailable);
                var $media = mediaHtml($('<div class="pos-item-media">'), item, item.name);

                if (!isAvailable)
                    $card.append('<span class="pos-item-soldout">Not Available</span>');

                var $body = $('<div class="pos-item-body">')
                    .append($('<div class="pos-item-name">').text(item.name))
                    .append($('<div class="pos-item-foot">').append($('<span class="pos-item-price">').text(money(item.price))).append('<span class="pos-item-action"></span>'));

                $grid.append($card.append($media, $body));
            });

            $wrap.append($grid);
        });

        refreshCardActions();
    }

    /* card shows "+" when not in cart, "- qty +" when in cart */
    function refreshCardActions() {
        $(".pos-item").each(function () {
            var line = cartLine($(this).attr("data-id"));
            var $action = $(this).find(".pos-item-action").empty();

            $(this).toggleClass("in-cart", !!line);

            if (IS_VIEW || ($(this).hasClass("sold-out") && !line))
                return;

            if (line)
                $action.append(stepperHtml(line.qty));
            else
                $action.append('<button type="button" class="pos-add" data-js="qty-plus" aria-label="Add"><i class="mdi mdi-plus"></i></button>');
        });
    }

    /* ************* Cart ************* */
    function openCart() { $("#pos_cart, #pos_cart_backdrop").addClass("open"); }
    function closeCart() { $("#pos_cart, #pos_cart_backdrop").removeClass("open"); }

    function addToCart(id) {
        if (IS_VIEW) return;
        var item = findItem(id);
        if (!item || !statusOf(id).available) return;

        var line = cartLine(id);
        if (line)
            line.qty = parseFloat(line.qty) + 1;
        else
            CART.push({id: item.id, name: item.name, price: item.price, qty: 1});

        renderCart();
        $("#pos_cart_fab").removeClass("bump").width();
        $("#pos_cart_fab").addClass("bump");
    }

    function renderCart() {
        var $lines = $("#pos_cart_lines").empty();
        var total = 0, qty = 0;

        if (!CART.length)
            $lines.append('<div class="pos-empty"><i class="mdi mdi-cart-outline"></i>Cart is empty<br><small>Tap an item to add it</small></div>');

        CART.forEach(function (line) {
            var lineTotal = line.qty * line.price;
            total += lineTotal;
            qty += parseFloat(line.qty);

            var item = findItem(line.id);
            var $line = $('<div class="pos-line">').attr("data-id", line.id);

            $line.append(mediaHtml($('<div class="pos-line-img">'), item, line.name));
            $line.append($('<div class="pos-line-info">')
                .append($('<div class="pos-line-name">').text(line.name))
                .append($('<div class="pos-line-price">').text(money(line.price) + " each")));
            $line.append($('<div class="pos-line-total">').text(money(lineTotal)));

            if (!IS_VIEW) {
                $line.append(stepperHtml(line.qty, true));
                $line.append('<button type="button" class="pos-line-remove" data-js="line-remove" aria-label="Remove"><i class="mdi mdi-trash-can-outline"></i></button>');
            }
            else
                $line.append($('<div class="pos-line-price">').text("Qty " + line.qty));

            $lines.append($line);
        });

        $("#pos_cart_sub").text(CART.length ? CART.length + (CART.length > 1 ? " items" : " item") : "No items");
        $("#pos_fab_count").text(CART.length);
        $("#pos_cart_qty").text(qty);
        $("#pos_cart_subtotal, #pos_cart_total, #pos_fab_total").text(money(total));
        $("#pos_save").prop("disabled", !CART.length);
        refreshCardActions();
    }

    function setQty(id, qty) {
        var line = cartLine(id);
        if (!line) return;

        qty = parseFloat(qty);
        if (isNaN(qty) || qty <= 0)
            CART = CART.filter(function (l) { return l.id !== id; });
        else
            line.qty = qty;

        renderCart();
    }

    /* ************* Available / hidden items and categories of selected warehouse (Restaurant > Website Menu > POS) ************* */
    function applyHiddenCategories() {
        $("#pos_categories .pos-cat").each(function () {
            var cat = $(this).attr("data-cat");
            $(this).toggle(cat === '' || !hiddenCategories[cat]);
        });

        if (activeCategory !== '' && hiddenCategories[activeCategory]) {
            activeCategory = '';
            $(".pos-cat").removeClass("active").filter('[data-cat=""]').addClass("active");
        }
    }

    function setMenuStatus(data) {
        menuStatus = (data && data.items) || {};
        hiddenCategories = {};
        ((data && data.hidden_categories) || []).forEach(function (id) { hiddenCategories[id] = true; });
        applyHiddenCategories();
        renderItems();
    }

    /* warehouse field gets its id from autocomplete after page load, so the page's own warehouse is the fallback */
    function currentBranchId() { return $("#branch_id").attr("data-ac-id") || CONFIG.branchId || ''; }

    function loadMenuStatus(branchId) {
        if (!branchId) { setMenuStatus(null); return; }
        $.getJSON("ajax_get_menu_status.php", {branch_id: branchId, _: Date.now()})
            .done(setMenuStatus)
            .fail(function () { renderItems(); });
    }

    /* ************* Save ************* */
    function collectPayload(extra) {
        var data = $("#pos_form").serializeArray().filter(function (f) { return f.name !== 'customer_id' && f.name !== 'branch_id' && f.name !== 'cost_center_id'; });
        var $costCenter = $("#cost_center_id");

        data.push({name: 'customer_id', value: $("#customer_id").attr("data-ac-id") || ''});
        data.push({name: 'branch_id', value: $("#branch_id").attr("data-ac-id") || ''});
        // autocomplete keeps the id in data-ac-id; a user with one cost center has it in a hidden field
        if ($costCenter.length)
            data.push({name: 'cost_center_id', value: $costCenter.attr("type") === 'hidden' ? $costCenter.val() : ($costCenter.attr("data-ac-id") || '')});
        data.push({name: 'ajax', value: 'Y'});

        CART.forEach(function (line) {
            data.push({name: 'stock_id[]', value: line.id});
            data.push({name: 'qty[]', value: line.qty});
            data.push({name: 'selling_price[]', value: line.price});
        });

        return data.concat(extra || []);
    }

    function showError(message) {
        Swal.fire({icon: "error", title: "Unable to save", html: message, customClass: {confirmButton: 'color'}});
    }

    function validateCart() {
        var errors = [];
        if (!$("#customer_id").attr("data-ac-id")) errors.push("Please select <b>Customer</b> from list");
        if (!$("#branch_id").attr("data-ac-id")) errors.push("Please select <b>Warehouse</b> from list");
        if (!CART.length) errors.push("Cart is empty");

        if (errors.length) { showError(errors.join("<br>")); return false; }
        return true;
    }

    function receiptHtml(res) {
        var rows = CART.map(function (l) {
            return '<tr><td>' + $('<span>').text(l.name).html() + ' <small class="text-muted">x ' + l.qty + '</small></td><td class="text-right">' + money(l.qty * l.price) + '</td></tr>';
        }).join('');

        return '<div class="pos-receipt">' +
            '<div class="mb-2"><b>' + $('<span>').text(res.voucher_name).html() + '</b> &middot; ' + $('<span>').text($("#customer_id").val()).html() + '</div>' +
            '<table>' + rows + '<tr class="tot"><td>Total</td><td class="text-right">' + money(res.total) + '</td></tr></table></div>';
    }

    function afterReceipt(res) {
        if (res.is_edit) {
            window.location.href = POS_URL;
            return;
        }

        CART = [];
        $("#mobile_number").val('');
        $("#__voucher_submit_token").val(res.next_token);
        renderCart();
        closeCart();
        loadMenuStatus(currentBranchId());
    }

    function saveOrder() {
        if (!validateCart()) return;

        var $btn = $("#pos_save").prop("disabled", true);

        $.post("op_save_pos.php", collectPayload([{name: 'submit', value: 'Y'}]), null, "json")
            .done(function (res) {
                if (!res || !res.ok) {
                    showError(res && res.error ? res.error : "Unable to save order");
                    return;
                }

                Swal.fire({
                    icon: "success",
                    title: "Order Saved",
                    html: receiptHtml(res),
                    showCancelButton: true,
                    confirmButtonText: '<i class="mdi mdi-printer"></i> Print Receipt',
                    cancelButtonText: "Cancel",
                    reverseButtons: true,
                    allowOutsideClick: false,
                    customClass: {confirmButton: 'color'}
                }).then(function (result) {
                    if (result.isConfirmed || result.value)
                        window.open(res.print_url + "&autoprint=Y", "pos_receipt", "width=420,height=640");

                    afterReceipt(res);
                });
            })
            .fail(function () { showError("Server error, order not saved. Please try again."); })
            .always(function () { $btn.prop("disabled", !CART.length); });
    }

    function deleteOrder() {
        Swal.fire({
            icon: "warning",
            title: "Delete this order?",
            showCancelButton: true,
            confirmButtonText: "Delete",
            customClass: {confirmButton: 'btn-danger'}
        }).then(function (result) {
            if (!(result.isConfirmed || result.value)) return;

            $.post("op_save_pos.php", collectPayload([{name: 'btn_delete', value: 'Y'}]), null, "json")
                .done(function (res) {
                    if (!res || !res.ok) { showError(res && res.error ? res.error : "Unable to delete order"); return; }
                    window.location.href = POS_URL;
                })
                .fail(function () { showError("Server error, order not deleted."); });
        });
    }

    /* ************* Events ************* */
    $("#pos_categories").on("click", ".pos-cat", function () {
        $(".pos-cat").removeClass("active");
        $(this).addClass("active");
        activeCategory = $(this).attr("data-cat");
        renderItems();
    });

    $("#pos_search").on("input", renderItems).on("keydown", function (e) {
        if (e.key !== "Enter") return;
        e.preventDefault();

        // barcode scanner / exact match adds directly
        var term = $.trim($(this).val()).toLowerCase();
        var match = CATALOG.find(function (i) { return i.barcode.toLowerCase() === term || i.name.toLowerCase() === term; });
        if (!match && $(".pos-item").length === 1)
            match = findItem($(".pos-item").attr("data-id"));

        if (match) { addToCart(match.id); $(this).val(''); renderItems(); }
    });

    // card click adds item, stepper on card changes qty (cart does not open)
    $("#pos_items")
        .on("click", ".pos-item", function (e) {
            if ($(e.target).closest(".pos-step, .pos-add").length) return;
            addToCart($(this).attr("data-id"));
        })
        .on("keydown", ".pos-item", function (e) {
            if ((e.key === "Enter" || e.key === " ") && e.target === this) { e.preventDefault(); addToCart($(this).attr("data-id")); }
        });

    // qty buttons work same on item cards and cart lines
    $("#pos_items, #pos_cart_lines")
        .on("click", "[data-js='qty-plus']", function () {
            var id = $(this).closest("[data-id]").attr("data-id");
            var line = cartLine(id);
            line ? setQty(id, parseFloat(line.qty) + 1) : addToCart(id);
        })
        .on("click", "[data-js='qty-minus']", function () {
            var id = $(this).closest("[data-id]").attr("data-id");
            setQty(id, parseFloat(cartLine(id).qty) - 1);
        })
        .on("click", "[data-js='qty-input']", function (e) { e.stopPropagation(); this.select(); })
        .on("change", "[data-js='qty-input']", function () {
            setQty($(this).closest("[data-id]").attr("data-id"), $(this).val());
        })
        .on("keydown", "[data-js='qty-input']", function (e) { if (e.key === "Enter") { e.preventDefault(); $(this).trigger("change"); } });

    $("#pos_cart_lines").on("click", "[data-js='line-remove']", function () {
        setQty($(this).closest("[data-id]").attr("data-id"), 0);
    });

    $("#pos_cart_fab").on("click", openCart);
    $("#pos_cart_close, #pos_cart_backdrop").on("click", closeCart);
    $(document).on("keydown", function (e) { if (e.key === "Escape") closeCart(); });

    $("#pos_save").on("click", saveOrder);
    $("#pos_delete").on("click", deleteOrder);
    $("#pos_clear").on("click", function () {
        if (!CART.length) return;
        if (confirm("Remove all items from cart?")) { CART = []; renderCart(); }
    });

    $("#branch_id").on("autocompleteselect", function (e, ui) { loadMenuStatus(ui.item.id); });

    if (IS_VIEW)
        $("#pos_form input").prop("disabled", true);

    setMenuStatus(CONFIG.menuStatus);   // POS menu of the page's warehouse, sent with the page
    renderCart();
});
