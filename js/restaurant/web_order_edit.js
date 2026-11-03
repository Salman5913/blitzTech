/**
 * Website order edit - POS style screen (restaurant/web_order_edit.php)
 * Server data comes from window.ORDER_EDIT_CONFIG: catalogs ({branch id: dishes}), cart (order lines).
 * The Branch dropdown picks which branch's menu the dishes grid shows.
 * Saved with a normal form post to op_save_web_order_edit.php: qty[order item id] for the order's lines (0 = removed),
 * add_item[] / add_qty[] for added dishes. Prices are taken again on the server.
 */
$(function () {
    var CONFIG = window.ORDER_EDIT_CONFIG || {};
    var CATALOGS = CONFIG.catalogs || {};
    var CATALOG = [];
    var CART = CONFIG.cart || [];
    var ORIGINAL_LINE_IDS = CART.map(function (l) { return l.line_id; });
    var COLORS = ['#0b3866', '#7a5af8', '#12b76a', '#f79009', '#ee46bc', '#2e90fa', '#f04438', '#15b79e'];
    var activeCategory = '';

    function money(n) { return NumberHelper.thousandSep(NumberHelper.round(n)); }
    function findDish(id) { return CATALOG.find(function (d) { return d.id === id; }); }
    function lineByKey(key) { return CART.find(function (l) { return l.key === key; }); }
    // the line a dish card adds to: same dish, without modifiers / deal
    function lineOfDish(id) { return CART.find(function (l) { return l.menu_id === id && !l.fixed; }); }
    function initials(name) { return (name || '?').substr(0, 2).toUpperCase(); }
    function colorOf(name) { var n = 0; for (var i = 0; i < name.length; i++) n += name.charCodeAt(i); return COLORS[n % COLORS.length]; }
    function deliveryFee() { return Math.max(0, parseFloat($("#delivery_fee").val()) || 0); }

    /* picture of dish: uploaded image > emoji > coloured initials */
    function mediaHtml($box, dish, name) {
        if (dish && dish.image) {
            var $img = $('<img loading="lazy" alt="">').attr("src", dish.image).on("error", function () {
                $(this).replaceWith($('<span class="pos-ph">').css("background", colorOf(name)).text(initials(name)));
            });
            return $box.append($img);
        }
        if (dish && dish.emoji)
            return $box.append($('<span class="pos-emoji">').text(dish.emoji));

        return $box.append($('<span class="pos-ph">').css("background", colorOf(dish ? dish.category : name)).text(initials(name)));
    }

    function stepperHtml(qty, light) {
        return $('<div class="pos-step">').toggleClass("light", !!light)
            .append('<button type="button" data-js="qty-minus" aria-label="Decrease"><i class="mdi mdi-minus"></i></button>')
            .append($('<input type="text" inputmode="numeric" data-js="qty-input" aria-label="Quantity">').val(qty))
            .append('<button type="button" data-js="qty-plus" aria-label="Increase"><i class="mdi mdi-plus"></i></button>');
    }

    /* ************* Dishes grid (category wise) ************* */
    function renderItems() {
        var term = $.trim($("#pos_search").val()).toLowerCase();
        var $wrap = $("#pos_items").empty();
        var groups = {}, order = [];

        CATALOG.forEach(function (dish) {
            if (activeCategory !== '' && dish.category !== activeCategory) return;
            if (term !== '' && dish.name.toLowerCase().indexOf(term) === -1) return;

            if (!groups[dish.category]) { groups[dish.category] = []; order.push(dish.category); }
            groups[dish.category].push(dish);
        });

        if (!order.length) {
            $wrap.append('<div class="pos-empty"><i class="mdi mdi-silverware-fork-knife"></i>No dishes found</div>');
            return;
        }

        order.forEach(function (category) {
            $wrap.append($('<div class="pos-group-title">').text(category));
            var $grid = $('<div class="pos-grid">');

            groups[category].forEach(function (dish) {
                var $card = $('<div class="pos-item" role="button" tabindex="0">').attr("data-dish", dish.id);
                var $body = $('<div class="pos-item-body">')
                    .append($('<div class="pos-item-name">').text(dish.name))
                    .append($('<div class="pos-item-foot">').append($('<span class="pos-item-price">').text(money(dish.price))).append('<span class="pos-item-action"></span>'));

                $grid.append($card.append(mediaHtml($('<div class="pos-item-media">'), dish, dish.name), $body));
            });

            $wrap.append($grid);
        });

        refreshCardActions();
    }

    /* card shows "+" when not in cart, "- qty +" when in cart */
    function refreshCardActions() {
        $(".pos-item").each(function () {
            var line = lineOfDish(parseInt($(this).attr("data-dish"), 10));
            var $action = $(this).find(".pos-item-action").empty();

            $(this).toggleClass("in-cart", !!line);
            if (line)
                $action.append(stepperHtml(line.qty).attr("data-key", line.key));
            else
                $action.append('<button type="button" class="pos-add" aria-label="Add"><i class="mdi mdi-plus"></i></button>');
        });
    }

    /* ************* Cart ************* */
    function openCart() { $("#pos_cart, #pos_cart_backdrop").addClass("open"); }
    function closeCart() { $("#pos_cart, #pos_cart_backdrop").removeClass("open"); }

    function addDish(id) {
        var dish = findDish(id);
        if (!dish) return;

        var line = lineOfDish(id);
        if (line)
            line.qty += 1;
        else
            CART.push({key: 'new-' + id, line_id: null, menu_id: id, name: dish.name, price: dish.price, qty: 1, fixed: false, note: ''});

        renderCart();
        $("#pos_cart_fab").removeClass("bump").width();
        $("#pos_cart_fab").addClass("bump");
    }

    function setQty(key, qty) {
        var line = lineByKey(key);
        if (!line) return;

        qty = Math.min(50, parseInt(qty, 10));
        if (isNaN(qty) || qty <= 0)
            CART = CART.filter(function (l) { return l.key !== key; });
        else
            line.qty = qty;

        renderCart();
    }

    function renderCart() {
        var $lines = $("#pos_cart_lines").empty();
        var total = 0, qty = 0;

        if (!CART.length)
            $lines.append('<div class="pos-empty"><i class="mdi mdi-cart-outline"></i>Cart is empty<br><small>Tap a dish to add it</small></div>');

        CART.forEach(function (line) {
            var lineTotal = line.qty * line.price;
            total += lineTotal;
            qty += line.qty;

            var $info = $('<div class="pos-line-info">')
                .append($('<div class="pos-line-name">').text(line.name))
                .append($('<div class="pos-line-price">').text(money(line.price) + " each" + (line.note ? " · " + line.note : "")));

            $lines.append($('<div class="pos-line">').attr("data-key", line.key)
                .append(mediaHtml($('<div class="pos-line-img">'), findDish(line.menu_id), line.name))
                .append($info)
                .append($('<div class="pos-line-total">').text(money(lineTotal)))
                .append(stepperHtml(line.qty, true))
                .append('<button type="button" class="pos-line-remove" data-js="line-remove" aria-label="Remove"><i class="mdi mdi-trash-can-outline"></i></button>'));
        });

        $("#pos_cart_sub").text(CART.length ? CART.length + (CART.length > 1 ? " items" : " item") : "No items");
        $("#pos_fab_count").text(CART.length);
        $("#pos_cart_qty").text(qty);
        $("#pos_cart_subtotal").text(money(total));
        $("#pos_cart_total, #pos_fab_total").text(money(total + deliveryFee()));
        $("#pos_save").prop("disabled", !CART.length);
        refreshCardActions();
    }

    /* ************* Save ************* */
    function saveOrder() {
        var errors = [];
        if (!$.trim($("#customer_name").val())) errors.push("Please enter <b>Customer</b> name");
        if (!$.trim($("#customer_phone").val())) errors.push("Please enter <b>Mobile Number</b>");
        if (!CART.length) errors.push("Cart is empty. Cancel the order instead.");
        if (errors.length) {
            Swal.fire({icon: "error", title: "Unable to save", html: errors.join("<br>"), customClass: {confirmButton: 'color'}});
            return;
        }

        var $lines = $("#order_edit_lines").empty();
        function hidden(name, value) { $lines.append($('<input type="hidden">').attr("name", name).val(value)); }

        ORIGINAL_LINE_IDS.forEach(function (lineId) {
            var line = CART.find(function (l) { return l.line_id === lineId; });
            hidden("qty[" + lineId + "]", line ? line.qty : 0);
        });
        CART.filter(function (l) { return !l.line_id; }).forEach(function (l) {
            hidden("add_item[]", l.menu_id);
            hidden("add_qty[]", l.qty);
        });

        $("#pos_save").prop("disabled", true);
        $("#order_edit_form").trigger("submit");
    }

    /* ************* Branch: its menu fills the grid and category tabs ************* */
    function loadBranch() {
        CATALOG = CATALOGS[$("#branch_id").val()] || [];
        activeCategory = '';

        var $cats = $("#pos_categories").empty().append('<button type="button" class="pos-cat active" data-cat="">All</button>');
        CATALOG.map(function (d) { return d.category; })
            .filter(function (c, i, all) { return all.indexOf(c) === i; })
            .forEach(function (c) { $cats.append($('<button type="button" class="pos-cat">').attr("data-cat", c).text(c)); });

        renderItems();
        renderCart();
    }

    var currentBranch = $("#branch_id").val();
    $("#branch_id").on("change", function () {
        // dishes added here belong to the old branch's menu; the order's saved lines stay
        var added = CART.filter(function (l) { return !l.line_id; });
        if (added.length && !confirm("Items you added from the other branch's menu will be removed from the cart. Change branch?")) {
            $(this).val(currentBranch);
            return;
        }
        CART = CART.filter(function (l) { return l.line_id; });
        currentBranch = $(this).val();
        loadBranch();
    });

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
        if ($(".pos-item").length === 1) { addDish(parseInt($(".pos-item").attr("data-dish"), 10)); $(this).val(''); renderItems(); }
    });

    // card click adds the dish, stepper on card changes qty (cart does not open)
    $("#pos_items")
        .on("click", ".pos-item", function (e) {
            if ($(e.target).closest(".pos-step").length) return;
            addDish(parseInt($(this).attr("data-dish"), 10));
        })
        .on("keydown", ".pos-item", function (e) {
            if ((e.key === "Enter" || e.key === " ") && e.target === this) { e.preventDefault(); addDish(parseInt($(this).attr("data-dish"), 10)); }
        });

    // qty buttons work same on dish cards and cart lines
    $("#pos_items, #pos_cart_lines")
        .on("click", "[data-js='qty-plus']", function () {
            var key = $(this).closest("[data-key]").attr("data-key");
            setQty(key, lineByKey(key).qty + 1);
        })
        .on("click", "[data-js='qty-minus']", function () {
            var key = $(this).closest("[data-key]").attr("data-key");
            setQty(key, lineByKey(key).qty - 1);
        })
        .on("click", "[data-js='qty-input']", function (e) { e.stopPropagation(); this.select(); })
        .on("change", "[data-js='qty-input']", function () { setQty($(this).closest("[data-key]").attr("data-key"), $(this).val()); })
        .on("keydown", "[data-js='qty-input']", function (e) { if (e.key === "Enter") { e.preventDefault(); $(this).trigger("change"); } });

    $("#pos_cart_lines").on("click", "[data-js='line-remove']", function () {
        setQty($(this).closest("[data-key]").attr("data-key"), 0);
    });

    $("#delivery_fee").on("input change", renderCart);
    $("#order_edit_form").on("keydown", "input", function (e) { if (e.key === "Enter") e.preventDefault(); });
    $("#pos_save").on("click", saveOrder);
    $("#pos_cart_fab").on("click", openCart);
    $("#pos_cart_close, #pos_cart_backdrop").on("click", closeCart);
    $(document).on("keydown", function (e) { if (e.key === "Escape") closeCart(); });

    loadBranch();
});
