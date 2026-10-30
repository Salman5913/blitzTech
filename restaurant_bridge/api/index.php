<?php
/**
 * Restaurant website API (restnext <-> ERP)
 *
 * Routes (via ?route=...):
 *   GET   /health
 *   GET   /status                   - website open or closed (ERP: Restaurant > Website On/Off)
 *   GET   /branches                 - branches the website offers (ERP: Restaurant > Branches)
 *   GET   /branches/{id}/menu       - every branch has its own menu,
 *   GET   /branches/{id}/deals      - deals
 *   GET   /branches/{id}/areas      - and delivery areas
 *   POST  /orders                   - body.branch_id: the branch the customer ordered from
 *   GET   /orders/{orderId}
 *
 * Order status and invoicing are handled in the ERP (restaurant/manage_web_orders.php).
 */
$config = require __DIR__ . '/config.php';

// Opening hours are restaurant time, same as the ERP (connection/localhost.php)
date_default_timezone_set('Asia/Karachi');

header('Content-Type: application/json; charset=utf-8');
header('Access-Control-Allow-Origin: ' . $config['cors_origin']);
header('Access-Control-Allow-Headers: Content-Type, Accept, X-API-Key');
header('Access-Control-Allow-Methods: GET, POST, OPTIONS');
if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') { http_response_code(204); exit; }

function respond($data, int $code = 200): void {
    http_response_code($code);
    echo json_encode($data, JSON_UNESCAPED_UNICODE);
    exit;
}

function body(): array {
    $data = json_decode(file_get_contents('php://input'), true);
    return is_array($data) ? $data : [];
}

if ($config['api_key'] !== '' && ($_SERVER['HTTP_X_API_KEY'] ?? '') !== $config['api_key']) {
    respond(['error' => 'Unauthorized'], 401);
}

try {
    $pdo = new PDO(
        "mysql:host={$config['db_host']};dbname={$config['db_name']};charset=utf8mb4",
        $config['db_user'], $config['db_pass'],
        [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION, PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC]
    );
} catch (PDOException $e) {
    respond(['error' => 'Database connection failed'], 500);
}

$method = $_SERVER['REQUEST_METHOD'];
$route  = '/' . trim($_GET['route'] ?? '', '/');
$parts  = explode('/', trim($route, '/'));

function formatOrder(array $o): array {
    return [
        'orderId'   => $o['order_code'],
        'status'    => $o['status'],
        'type'      => $o['order_type'],
        'branch'    => ['id' => (int)$o['branch_id'], 'name' => $o['branch_name'] ?? ''],
        'total'     => (float)$o['total'],
        'customer'  => ['name' => $o['customer_name'], 'phone' => $o['customer_phone'], 'address' => $o['customer_address']],
        'createdAt' => date(DATE_ATOM, strtotime($o['created_at'])),
    ];
}

// Menu rows linked to an ERP stock item (erp_stock_id, plus erp_attribute_id for a variation) take their price
// from the ERP, using the same rule as the ERP's vw_ac_stock (variation price, else item price).
// A link to a deleted stock item or variation makes the dish unavailable. Hidden rows (is_visible = 0) can't be ordered.
const MENU_SQL = "SELECT m.id, m.name, m.description,
                         COALESCE(NULLIF(sa.selling_price, 0), s.sp, m.price) AS price, m.category, m.emoji, m.image, m.tag,
                         (m.available = 1 AND m.is_visible = 1 AND (m.erp_stock_id IS NULL OR (s.id IS NOT NULL
                             AND (m.erp_attribute_id IS NULL OR (sa.id IS NOT NULL AND sa.is_inactive <> 'Y'))))) AS available,
                         m.erp_stock_id, m.erp_attribute_id,
                         m.show_in_hero AS hero,
                         s.name AS stock_name, csa.name AS attribute_name
                  FROM rs_menu_items m
                  LEFT JOIN st_stock s ON s.id = m.erp_stock_id
                  LEFT JOIN st_stock_attribute sa ON sa.stock_id = m.erp_stock_id AND sa.attribute_id = m.erp_attribute_id
                  LEFT JOIN cp_stock_attribute csa ON csa.id = m.erp_attribute_id";

// Stock items in the branch's categories marked "Show on website" (rs_menu_categories) join that branch's menu
// automatically, one dish per active variation. Same query as RestaurantMenu::syncFromStock() in demos3. ? = branch id (twice).
const MENU_SYNC_SQL = "INSERT INTO rs_menu_items (branch_id, name, description, price, category, emoji, available, sort_order, erp_stock_id, erp_attribute_id)
                       SELECT mc.branch_id, CONCAT(s.name, IFNULL(CONCAT(' - ', csa.name), '')), '', COALESCE(NULLIF(sa.selling_price, 0), s.sp),
                              COALESCE(NULLIF(mc.website_name, ''), c.name), '', 1, 1000 + s.id, s.id, sa.attribute_id
                       FROM st_stock s
                       JOIN rs_menu_categories mc ON mc.stock_category_id = s.category_id AND mc.branch_id = ?
                       JOIN st_stock_category c ON c.id = s.category_id
                       LEFT JOIN st_stock_attribute sa ON sa.stock_id = s.id AND sa.is_inactive <> 'Y'
                       LEFT JOIN cp_stock_attribute csa ON csa.id = sa.attribute_id
                       WHERE NOT EXISTS (SELECT 1 FROM rs_menu_items m WHERE m.branch_id = ? AND m.erp_stock_id = s.id AND m.erp_attribute_id <=> sa.attribute_id)";

// Branches the website offers, with the address and phone from the ERP's branch table
const BRANCH_SQL = "SELECT b.id, b.name, b.address, b.phone
                    FROM rs_branches rb JOIN branch b ON b.id = rb.branch_id
                    WHERE rb.show_on_website = 1";

/**
 * Website open or closed right now (rs_website_settings, ERP: Restaurant > Website On/Off). Same rule as the ERP's
 * RestaurantWebsite::getStatus(): Off = closed; On = open, only between the opening and closing time when set
 * (closing before opening = past midnight). No settings table yet = open.
 */
function websiteStatus(PDO $pdo): array {
    try {
        $s = $pdo->query('SELECT * FROM rs_website_settings WHERE id = 1')->fetch();
    } catch (PDOException $e) {
        $s = false;
    }
    if (!$s) return ['open' => true, 'message' => '', 'opensAt' => null];

    $now = date('H:i:s');
    $open = $s['open_time'] ?: '00:00:00';
    $close = $s['close_time'] ?: '24:00:00';
    $isOpen = $s['is_open'] && ($open <= $close ? ($now >= $open && $now < $close) : ($now >= $open || $now < $close));

    $opensAt = $s['open_time'] ? date('g:i A', strtotime($s['open_time'])) : null;
    $message = trim((string)$s['closed_message']) !== '' ? trim($s['closed_message'])
        : ($opensAt ? "We're closed right now. Kindly visit at $opensAt." : "We're closed right now. Kindly visit again later.");

    return ['open' => (bool)$isOpen, 'message' => $isOpen ? '' : $message, 'opensAt' => $opensAt];
}

/** A branch the website offers, or a 404 */
function websiteBranch(PDO $pdo, int $branchId): array {
    $st = $pdo->prepare(BRANCH_SQL . ' AND b.id = ?');
    $st->execute([$branchId]);
    $branch = $st->fetch();
    if (!$branch) respond(['error' => 'Please choose a branch'], 404);
    return $branch;
}

/** Modifier groups of a dish, with their active options and prices from the database */
function modifierGroups(PDO $pdo, int $menuItemId): array {
    $st = $pdo->prepare('SELECT g.id AS group_id, g.name AS group_name, g.type, g.required, g.replaces_price,
                                o.id AS option_id, o.name AS option_name, o.price AS option_price
                         FROM rs_modifier_groups g
                         LEFT JOIN rs_modifier_options o ON o.group_id = g.id AND o.is_active = 1
                         WHERE g.menu_item_id = ?
                         ORDER BY g.sort_order, g.id, o.sort_order, o.id');
    $st->execute([$menuItemId]);
    $groups = [];
    foreach ($st->fetchAll() as $r) {
        $gid = (int)$r['group_id'];
        if (!isset($groups[$gid])) {
            $groups[$gid] = ['id' => $gid, 'name' => $r['group_name'], 'type' => $r['type'], 'required' => (int)$r['required'] === 1,
                             'replacesPrice' => (int)$r['replaces_price'] === 1, 'options' => []];
        }
        if ($r['option_id'] !== null) {
            $groups[$gid]['options'][] = ['id' => (int)$r['option_id'], 'name' => $r['option_name'], 'price' => (float)$r['option_price']];
        }
    }
    return array_values($groups);
}

/**
 * Checks the chosen option ids against the dish's groups.
 * Returns [chosen options, extra price, base price or null]. A size group (replacesPrice) gives the base price
 * instead of adding to it; any other group adds its option price. Anything wrong is answered with 422.
 */
function chooseModifiers(array $groups, array $optionIds, int $menuItemId): array {
    $chosen = []; $extra = 0.0; $perGroup = []; $base = null;
    foreach (array_unique(array_map('intval', $optionIds)) as $optId) {
        $found = null;
        foreach ($groups as $g) foreach ($g['options'] as $o) if ($o['id'] === $optId) { $found = [$g, $o]; break 2; }
        if (!$found) respond(['error' => "Option #$optId is not available for item #$menuItemId"], 422);
        [$g, $o] = $found;
        $perGroup[$g['id']] = ($perGroup[$g['id']] ?? 0) + 1;
        $chosen[] = ['option_id' => $o['id'], 'name' => $o['name'], 'price' => $o['price']];
        if ($g['replacesPrice']) $base = $o['price']; else $extra += $o['price'];
    }
    foreach ($groups as $g) {
        $n = $perGroup[$g['id']] ?? 0;
        if ($g['type'] === 'single' && $n > 1) respond(['error' => "Choose only one option for {$g['name']}"], 422);
        if ($g['required'] && $n === 0) respond(['error' => "Please choose {$g['name']}"], 422);
    }
    return [$chosen, $extra, $base];
}

/**
 * The dishes of a deal as order lines, for $qty deals. Each dish keeps its own stock; the deal price is split
 * across the dishes in proportion to their normal prices (the last dish takes any rounding cent).
 * Lines: [menu id, name, unit price, qty, stock id, attribute id, modifiers, deal id]
 */
function dealLines(PDO $pdo, int $dealId, int $qty, int $branchId): array {
    $st = $pdo->prepare('SELECT id, name, price FROM rs_deals WHERE id = ? AND branch_id = ? AND is_active = 1 AND show_on_website = 1');
    $st->execute([$dealId, $branchId]);
    $deal = $st->fetch();
    if (!$deal) respond(['error' => "Deal #$dealId is not available"], 422);

    // Each component is a stock item (+ attribute); it is sold through the branch's website dish. No website dish = unavailable.
    $st = $pdo->prepare('SELECT x.id, COALESCE(x.name, s.name) AS name, x.price, x.erp_stock_id, x.erp_attribute_id, COALESCE(x.available, 0) AS available
                         FROM rs_deal_items di
                         JOIN st_stock s ON s.id = di.stock_id
                         LEFT JOIN rs_menu_items mi ON mi.erp_stock_id = di.stock_id AND mi.erp_attribute_id <=> di.attribute_id AND mi.is_visible = 1 AND mi.branch_id = ?
                         LEFT JOIN (' . MENU_SQL . ') x ON x.id = mi.id
                         WHERE di.deal_id = ? ORDER BY di.stock_id');
    $st->execute([$branchId, $dealId]);
    $dishes = $st->fetchAll();
    if (!$dishes) respond(['error' => "Deal #$dealId has no dishes"], 422);
    foreach ($dishes as $d) {
        if (!$d['available']) respond(['error' => "{$d['name']} in {$deal['name']} is unavailable"], 422);
    }

    $dealPrice = (float)$deal['price'];
    $normal = array_sum(array_map(fn($d) => (float)$d['price'], $dishes));
    $count = count($dishes);
    $remaining = $dealPrice; $lines = [];
    foreach ($dishes as $k => $d) {
        $share = $normal > 0 ? (float)$d['price'] / $normal : 1 / $count;
        $unit = ($k === $count - 1) ? round($remaining, 2) : round($dealPrice * $share, 2);
        $remaining = round($remaining - $unit, 2);
        $lines[] = [(int)$d['id'], $d['name'], $unit, $qty, $d['erp_stock_id'], $d['erp_attribute_id'], [], (int)$deal['id']];
    }

    return $lines;
}

try {
    // GET /health
    if ($route === '/health' && $method === 'GET') {
        respond(['ok' => true, 'time' => date(DATE_ATOM)]);
    }

    // GET /status  - the website shows a "we're closed" popup while this says closed
    if ($route === '/status' && $method === 'GET') {
        respond(websiteStatus($pdo));
    }

    // GET /branches  - the website asks the customer to pick one of these first
    if ($route === '/branches' && $method === 'GET') {
        $rows = $pdo->query(BRANCH_SQL . ' ORDER BY rb.sort_order, b.name')->fetchAll();
        respond(array_map(fn($r) => ['id' => (int)$r['id'], 'name' => $r['name'], 'address' => (string)$r['address'], 'phone' => (string)$r['phone']], $rows));
    }

    // /branches/{id}/menu|deals|areas  - everything below is per branch
    $branchId = 0;
    if ($parts[0] === 'branches' && isset($parts[1], $parts[2]) && $method === 'GET') {
        $branchId = (int)websiteBranch($pdo, (int)$parts[1])['id'];
        $route = '/' . $parts[2];
    }

    // GET /branches/{id}/menu
    if ($branchId && $route === '/menu') {
        $pdo->prepare(MENU_SYNC_SQL)->execute([$branchId, $branchId]);
        $st = $pdo->prepare(MENU_SQL . ' WHERE m.is_visible = 1 AND m.branch_id = ? ORDER BY m.sort_order, m.id');
        $st->execute([$branchId]);
        $rows = $st->fetchAll();
        foreach ($rows as &$r) {
            // Rows of one ERP stock item (its attributes, e.g. Medium / Full) are grouped into one dish by the website
            $r['stockId'] = $r['erp_stock_id'] !== null ? (int)$r['erp_stock_id'] : null;
            $r['stockName'] = $r['stock_name'];
            $r['variant'] = $r['attribute_name'];
            unset($r['erp_stock_id'], $r['erp_attribute_id'], $r['stock_name'], $r['attribute_name']);
            $r['id'] = (int)$r['id'];
            $r['price'] = (float)$r['price'];
            $r['available'] = (bool)$r['available'];
            $r['modifiers'] = modifierGroups($pdo, $r['id']);
            $r['hero'] = (int)$r['hero'] === 1;
            // Images saved in the ERP are paths on the ERP server; give the website the full address
            if (!empty($r['image']) && $r['image'][0] === '/') $r['image'] = $config['public_url'] . $r['image'];
        }
        respond($rows);
    }

    // GET /branches/{id}/areas  - the branch's delivery areas for the checkout dropdown, with their charge
    if ($branchId && $route === '/areas') {
        $st = $pdo->prepare('SELECT id, name, delivery_charge, free_over FROM rs_delivery_areas WHERE is_active = 1 AND branch_id = ? ORDER BY sort_order, name');
        $st->execute([$branchId]);
        $rows = $st->fetchAll();
        respond(array_map(fn($r) => [
            'id' => (int)$r['id'],
            'name' => $r['name'],
            'charge' => (float)$r['delivery_charge'],
            'freeOver' => $r['free_over'] === null ? null : (float)$r['free_over'],
        ], $rows));
    }

    // GET /branches/{id}/deals  - the branch's website deals: only deals whose dishes can all be sold right now
    if ($branchId && $route === '/deals') {
        // Components that have no website dish on this branch make the deal unavailable (item_id stays empty for them)
        $st = $pdo->prepare('SELECT d.id AS deal_id, d.name AS deal_name, d.description, d.price,
                                    x.id AS item_id, COALESCE(x.name, s.name) AS item_name, x.price AS item_price, COALESCE(x.available, 0) AS available
                             FROM rs_deals d
                             JOIN rs_deal_items di ON di.deal_id = d.id
                             JOIN st_stock s ON s.id = di.stock_id
                             LEFT JOIN rs_menu_items mi ON mi.erp_stock_id = di.stock_id AND mi.erp_attribute_id <=> di.attribute_id AND mi.is_visible = 1 AND mi.branch_id = d.branch_id
                             LEFT JOIN (' . MENU_SQL . ') x ON x.id = mi.id
                             WHERE d.is_active = 1 AND d.show_on_website = 1 AND d.branch_id = ?
                             ORDER BY d.name, item_name');
        $st->execute([$branchId]);
        $rows = $st->fetchAll();
        $deals = [];
        foreach ($rows as $r) {
            $id = (int)$r['deal_id'];
            if (!isset($deals[$id])) {
                $deals[$id] = ['id' => $id, 'name' => $r['deal_name'], 'description' => $r['description'], 'price' => (float)$r['price'],
                               'normalPrice' => 0.0, 'available' => true, 'items' => []];
            }
            $deals[$id]['available'] = $deals[$id]['available'] && (bool)$r['available'];
            if ($r['item_id'] === null) continue;
            $deals[$id]['normalPrice'] += (float)$r['item_price'];
            $deals[$id]['items'][] = ['id' => (int)$r['item_id'], 'name' => $r['item_name'], 'price' => (float)$r['item_price']];
        }
        $out = [];
        foreach ($deals as $d) {
            if (!$d['available']) continue;
            $d['normalPrice'] = round($d['normalPrice'], 2);
            $d['savings'] = round($d['normalPrice'] - $d['price'], 2);
            $out[] = $d;
        }
        respond($out);
    }

    // /orders
    if ($parts[0] === 'orders') {
        $code = $parts[1] ?? null;

        // POST /orders  - website places an order
        if (!$code && $method === 'POST') {
            $status = websiteStatus($pdo);
            if (!$status['open']) respond(['error' => $status['message']], 423);

            $b = body();
            $c = $b['customer'] ?? [];
            $type = ($b['type'] ?? '') === 'pickup' ? 'pickup' : 'delivery';
            $items = $b['items'] ?? [];
            if (empty($c['name']) || empty($c['phone']) || !$items) respond(['error' => 'Name, phone and items are required'], 422);
            if (!preg_match('/^\+92\d{10}$/', (string)$c['phone'])) respond(['error' => 'Phone number must be +92 followed by 10 digits'], 422);
            if ($type === 'delivery' && empty($c['address'])) respond(['error' => 'Delivery address is required'], 422);

            // The order, its dishes, deals and delivery area all belong to the branch the customer picked
            $branch = websiteBranch($pdo, (int)($b['branch_id'] ?? 0));
            $branchId = (int)$branch['id'];

            // Delivery charge comes from the delivery area the customer picked (set in the ERP)
            $area = null;
            if ($type === 'delivery') {
                $st = $pdo->prepare('SELECT id, name, delivery_charge, free_over FROM rs_delivery_areas WHERE id = ? AND is_active = 1 AND branch_id = ?');
                $st->execute([(int)($b['area_id'] ?? 0), $branchId]);
                $area = $st->fetch();
                if (!$area) respond(['error' => 'Please choose your delivery area'], 422);
            }

            // Prices always come from the database, never from the browser
            // Deal entries ({deal_id, qty}) have no dish id; 0 is a placeholder that matches no dish
            $ids = array_map(fn($i) => empty($i['deal_id']) ? (int)$i['id'] : 0, $items);
            $in  = implode(',', array_fill(0, count($ids), '?'));
            $st  = $pdo->prepare(MENU_SQL . " WHERE m.id IN ($in) AND m.branch_id = ?");
            $st->execute([...$ids, $branchId]);
            $prices = [];
            foreach ($st->fetchAll() as $row) if ($row['available']) $prices[(int)$row['id']] = $row;

            $subtotal = 0; $lines = [];
            foreach ($items as $i) {
                $qty = max(1, min(50, (int)$i['qty']));

                // A deal is sold as its dishes, each with its share of the deal price
                if (!empty($i['deal_id'])) {
                    foreach (dealLines($pdo, (int)$i['deal_id'], $qty, $branchId) as $line) {
                        $subtotal += $line[2] * $line[3];
                        $lines[] = $line;
                    }
                    continue;
                }

                $id = (int)$i['id'];
                if (!isset($prices[$id])) respond(['error' => "Item #$id is unavailable"], 422);
                // Modifier prices come from the database too; the line's unit price includes them
                $optionIds = is_array($i['modifiers'] ?? null) ? $i['modifiers'] : [];
                [$mods, $extra, $base] = chooseModifiers(modifierGroups($pdo, $id), $optionIds, $id);
                $unit = ($base ?? $prices[$id]['price']) + $extra;
                $subtotal += $unit * $qty;
                // line: [menu id, name, unit price, qty, stock id, attribute id, modifiers, deal id]
                $lines[] = [$id, $prices[$id]['name'], $unit, $qty, $prices[$id]['erp_stock_id'], $prices[$id]['erp_attribute_id'], $mods, null];
            }
            // Free above the area's threshold (free_over NULL = never free); pickup has no charge
            $fee = 0.0;
            if ($area && ($area['free_over'] === null || $subtotal < (float)$area['free_over'])) $fee = (float)$area['delivery_charge'];
            $total = $subtotal + $fee;

            $pdo->beginTransaction();
            $pdo->prepare('INSERT INTO rs_orders (branch_id, order_code, order_type, customer_name, customer_phone, customer_address, delivery_area, notes, subtotal, delivery_fee, total, status, source)
                           VALUES (?, "", ?, ?, ?, ?, ?, ?, ?, ?, ?, "received", "website")')
                ->execute([$branchId, $type, $c['name'], $c['phone'], $c['address'] ?? '', $area['name'] ?? null, $b['notes'] ?? '', $subtotal, $fee, $total]);
            $orderId = (int)$pdo->lastInsertId();
            $orderCode = 'ORD-' . (1000 + $orderId);
            $pdo->prepare('UPDATE rs_orders SET order_code = ? WHERE id = ?')->execute([$orderCode, $orderId]);
            $ins = $pdo->prepare('INSERT INTO rs_order_items (order_id, menu_item_id, item_name, unit_price, qty, erp_stock_id, erp_attribute_id, deal_id) VALUES (?, ?, ?, ?, ?, ?, ?, ?)');
            $insMod = $pdo->prepare('INSERT INTO rs_order_item_modifiers (order_item_id, option_id, name, price) VALUES (?, ?, ?, ?)');
            foreach ($lines as $l) {
                $ins->execute([$orderId, $l[0], $l[1], $l[2], $l[3], $l[4], $l[5], $l[7]]);
                $lineId = (int)$pdo->lastInsertId();
                foreach ($l[6] as $m) $insMod->execute([$lineId, $m['option_id'], $m['name'], $m['price']]);
            }
            $pdo->commit();

            respond(['orderId' => $orderCode, 'status' => 'received', 'type' => $type, 'area' => $area['name'] ?? null,
                     'branch' => ['id' => $branchId, 'name' => $branch['name']],
                     'deliveryFee' => $fee, 'total' => $total, 'createdAt' => date(DATE_ATOM)], 201);
        }

        if ($code) {
            $st = $pdo->prepare('SELECT o.*, b.name AS branch_name FROM rs_orders o LEFT JOIN branch b ON b.id = o.branch_id WHERE o.order_code = ?');
            $st->execute([strtoupper($code)]);
            $o = $st->fetch();
            if (!$o) respond(['error' => 'Order not found'], 404);

            // GET /orders/{id}  - customer tracking
            if ($method === 'GET') respond(formatOrder($o));
        }
    }

    respond(['error' => 'Not found'], 404);
} catch (Throwable $e) {
    if ($pdo->inTransaction()) $pdo->rollBack();
    respond(['error' => 'Server error'], 500);
}
