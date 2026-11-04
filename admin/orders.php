<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
ensure_srs_tables();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $order_id = isset($_POST['order_id']) ? (int) $_POST['order_id'] : 0;
    $status = isset($_POST['status']) ? trim($_POST['status']) : '';
    $allowed = array('pending', 'successful', 'cancelled');

    if ($order_id > 0 && in_array($status, $allowed, true)) {
        $status_safe = esc($status);
        db_query("UPDATE merch_orders SET status = '$status_safe', updated_at = NOW() WHERE id = $order_id");
        $ord = db_one("SELECT order_number FROM merch_orders WHERE id = $order_id");
        $num = $ord ? $ord['order_number'] : ('#' . $order_id);
        $admin_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
        log_activity($admin_id, 'order_update', "Order $num marked as $status");
        flash_set('ok', "Order {$num} status changed to " . ucfirst($status) . ".");
    } else {
        flash_set('err', 'Invalid status update request.');
    }
    redirect('/admin/orders.php');
}

$filter = isset($_GET['status']) ? trim($_GET['status']) : 'all';
$allowed_filters = array('pending', 'successful', 'cancelled');
$where = '';
if (in_array($filter, $allowed_filters, true)) {
    $where = "WHERE merch_orders.status = '" . esc($filter) . "'";
}

$counts = array(
    'all'        => db_one("SELECT COUNT(*) AS c, COALESCE(SUM(total_amount), 0) AS total_sum FROM merch_orders"),
    'pending'    => db_one("SELECT COUNT(*) AS c FROM merch_orders WHERE status = 'pending'"),
    'successful' => db_one("SELECT COUNT(*) AS c, COALESCE(SUM(total_amount), 0) AS total_sum FROM merch_orders WHERE status = 'successful'"),
    'cancelled'  => db_one("SELECT COUNT(*) AS c FROM merch_orders WHERE status = 'cancelled'"),
);

$orders = db_all("
    SELECT merch_orders.*, users.name AS account_name, users.email AS account_email
    FROM merch_orders
    LEFT JOIN users ON users.id = merch_orders.user_id
    $where
    ORDER BY merch_orders.created_at DESC
");

$order_ids = array();
foreach ($orders as $o) {
    $order_ids[] = (int) $o['id'];
}
$order_items = array();
if (!empty($order_ids)) {
    $id_str = implode(',', $order_ids);
    $items = db_all("SELECT * FROM merch_order_items WHERE order_id IN ($id_str) ORDER BY id ASC");
    foreach ($items as $it) {
        $order_items[(int) $it['order_id']][] = $it;
    }
}

$page_title = 'Merchandise Orders';
$active = 'orders';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="section-head" style="margin-top:0">
    <div>
        <h2 style="margin-top:0">Merchandise Orders Management</h2>
        <p class="lede">Manage fan merchandise orders, verify Cash on Delivery shipping details, and update fulfillment status.</p>
    </div>
</div>

<div class="grid grid-4" style="margin-bottom:24px">
    <div class="stat card card-body">
        <b><?php echo (int) $counts['all']['c']; ?></b>
        <span>Total Orders</span>
    </div>
    <div class="stat card card-body" style="border-left: 4px solid #f59e0b;">
        <b style="color:#f59e0b"><?php echo (int) $counts['pending']['c']; ?></b>
        <span>Pending Orders</span>
    </div>
    <div class="stat card card-body" style="border-left: 4px solid #10b981;">
        <b style="color:#10b981"><?php echo (int) $counts['successful']['c']; ?></b>
        <span>Successful Orders ($<?php echo number_format((float) $counts['successful']['total_sum'], 2); ?>)</span>
    </div>
    <div class="stat card card-body" style="border-left: 4px solid #ef4444;">
        <b style="color:#ef4444"><?php echo (int) $counts['cancelled']['c']; ?></b>
        <span>Cancelled Orders</span>
    </div>
</div>

<div class="card card-body" style="margin-bottom:24px; padding:12px 18px;">
    <div style="display:flex; align-items:center; justify-content:space-between; flex-wrap:wrap; gap:12px;">
        <div style="display:flex; align-items:center; gap:8px;">
            <span style="font-weight:700; font-size:0.9rem;">Filter by Status:</span>
            <a href="orders.php" class="chip <?php echo $filter === 'all' ? 'chip-primary' : ''; ?>" style="text-decoration:none;">All (<?php echo (int) $counts['all']['c']; ?>)</a>
            <a href="orders.php?status=pending" class="chip <?php echo $filter === 'pending' ? 'chip-primary' : ''; ?>" style="text-decoration:none;">Pending (<?php echo (int) $counts['pending']['c']; ?>)</a>
            <a href="orders.php?status=successful" class="chip <?php echo $filter === 'successful' ? 'chip-primary' : ''; ?>" style="text-decoration:none;">Successful (<?php echo (int) $counts['successful']['c']; ?>)</a>
            <a href="orders.php?status=cancelled" class="chip <?php echo $filter === 'cancelled' ? 'chip-primary' : ''; ?>" style="text-decoration:none;">Cancelled (<?php echo (int) $counts['cancelled']['c']; ?>)</a>
        </div>
        <span class="meta">Showing <?php echo count($orders); ?> order(s)</span>
    </div>
</div>

<?php if (empty($orders)) { ?>
    <div class="card card-body text-center" style="padding:48px 24px; color:var(--muted)">
        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" style="width:48px; height:48px; margin:0 auto 12px; display:block; opacity:0.6"><circle cx="9" cy="21" r="1"/><circle cx="20" cy="21" r="1"/><path d="M1 1h4l2.68 13.39a2 2 0 0 0 2 1.61h9.72a2 2 0 0 0 2-1.61L23 6H6"/></svg>
        <h3 style="margin:0 0 6px;">No orders found</h3>
        <p style="margin:0;">There are currently no orders under "<?php echo e(ucfirst($filter)); ?>" status.</p>
    </div>
<?php } else { ?>
    <div style="display:flex; flex-direction:column; gap:18px;">
        <?php foreach ($orders as $order) {
            $items = isset($order_items[(int) $order['id']]) ? $order_items[(int) $order['id']] : array();
            $status_class = 'badge-order-pending';
            if ($order['status'] === 'successful') {
                $status_class = 'badge-order-successful';
            } elseif ($order['status'] === 'cancelled') {
                $status_class = 'badge-order-cancelled';
            }
        ?>
            <article class="card card-body" style="border:1px solid var(--line);">
                <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:12px; border-bottom:1px solid var(--line); padding-bottom:12px; margin-bottom:14px;">
                    <div>
                        <div style="display:flex; align-items:center; gap:10px; margin-bottom:4px;">
                            <strong style="font-size:1.1rem; color:var(--text);"><?php echo e($order['order_number']); ?></strong>
                            <span class="badge <?php echo $status_class; ?>" style="font-size:0.75rem; text-transform:uppercase;"><?php echo e($order['status']); ?></span>
                            <span class="cart-cod-pill" style="margin:0; padding:3px 8px; font-size:0.75rem;">Cash on Delivery</span>
                        </div>
                        <div class="meta" style="font-size:0.83rem;">
                            Placed on <?php echo date('M d, Y · h:i A', strtotime($order['created_at'])); ?>
                            <?php if ($order['account_name']) { ?>
                                · Registered User: <strong><?php echo e($order['account_name']); ?></strong> (<?php echo e($order['account_email']); ?>)
                            <?php } ?>
                        </div>
                    </div>
                    <div style="text-align:right;">
                        <span class="meta" style="font-size:0.8rem; display:block;">Total Amount</span>
                        <strong style="font-size:1.25rem; color:var(--accent-2);">$<?php echo number_format((float) $order['total_amount'], 2); ?></strong>
                    </div>
                </div>

                <div class="grid grid-2" style="gap:20px; margin-bottom:14px;">
                    <div style="background:rgba(255,255,255,0.02); border:1px solid var(--line); border-radius:10px; padding:12px 14px;">
                        <h4 style="margin:0 0 8px; font-size:0.92rem; color:var(--accent-2); display:flex; align-items:center; gap:6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"/><circle cx="12" cy="10" r="3"/></svg>
                            Delivery Details (COD)
                        </h4>
                        <div style="font-size:0.87rem; line-height:1.5;">
                            <div><strong>Recipient:</strong> <?php echo e($order['shipping_name']); ?></div>
                            <div><strong>Phone:</strong> <?php echo e($order['shipping_phone']); ?></div>
                            <div><strong>Address:</strong> <?php echo e($order['shipping_address']); ?>, <?php echo e($order['shipping_city']); ?></div>
                            <?php if (!empty($order['order_notes'])) { ?>
                                <div style="margin-top:6px; padding-top:6px; border-top:1px dashed var(--line); color:var(--muted);">
                                    <strong>Notes:</strong> <?php echo nl2br(e($order['order_notes'])); ?>
                                </div>
                            <?php } ?>
                        </div>
                    </div>

                    <div style="background:rgba(255,255,255,0.02); border:1px solid var(--line); border-radius:10px; padding:12px 14px;">
                        <h4 style="margin:0 0 8px; font-size:0.92rem; color:var(--accent-2); display:flex; align-items:center; gap:6px;">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:16px;height:16px"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"/><path d="M18.5 2.5a2.121 2.121 0 0 1 3 3L12 15l-4 1 1-4 9.5-9.5z"/></svg>
                            Update Order Status
                        </h4>
                        <form method="post" style="display:flex; flex-direction:column; gap:10px;">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="order_id" value="<?php echo (int) $order['id']; ?>">
                            <div style="display:flex; gap:8px;">
                                <select name="status" style="flex:1; padding:8px 12px; border-radius:8px; border:1px solid var(--line); background:var(--card); color:var(--text); font-weight:600;">
                                    <option value="pending" <?php echo $order['status'] === 'pending' ? 'selected' : ''; ?>>Pending (Order placed, awaiting processing)</option>
                                    <option value="successful" <?php echo $order['status'] === 'successful' ? 'selected' : ''; ?>>Successful (Delivered & Payment Collected)</option>
                                    <option value="cancelled" <?php echo $order['status'] === 'cancelled' ? 'selected' : ''; ?>>Cancelled (Rejected or returned)</option>
                                </select>
                                <button type="submit" class="btn btn-primary" style="padding:8px 16px; white-space:nowrap;">Save</button>
                            </div>
                            <span class="meta" style="font-size:0.78rem;">
                                Last updated: <?php echo $order['updated_at'] ? date('M d, Y · h:i A', strtotime($order['updated_at'])) : 'Not updated yet'; ?>
                            </span>
                        </form>
                    </div>
                </div>

                <div style="border-top:1px solid var(--line); padding-top:12px;">
                    <div style="font-weight:700; font-size:0.88rem; margin-bottom:8px; color:var(--text);">Purchased Items (<?php echo count($items); ?>):</div>
                    <div style="display:flex; flex-direction:column; gap:8px;">
                        <?php foreach ($items as $item) { ?>
                            <div style="display:flex; align-items:center; justify-content:space-between; background:var(--bg); border:1px solid var(--line); border-radius:8px; padding:8px 12px; font-size:0.88rem;">
                                <div style="display:flex; align-items:center; gap:10px;">
                                    <?php if (!empty($item['image'])) { ?>
                                        <img src="<?php echo e(site_url($item['image'])); ?>" alt="<?php echo e($item['title']); ?>" style="width:36px; height:36px; object-fit:cover; border-radius:6px; border:1px solid var(--line);">
                                    <?php } ?>
                                    <div>
                                        <strong><?php echo e($item['title']); ?></strong>
                                        <div class="meta" style="font-size:0.8rem;">Qty: <?php echo (int) $item['quantity']; ?> × $<?php echo number_format((float) $item['price'], 2); ?></div>
                                    </div>
                                </div>
                                <div style="font-weight:700; color:var(--text);">
                                    $<?php echo number_format((float) $item['price'] * (int) $item['quantity'], 2); ?>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
            </article>
        <?php } ?>
    </div>
<?php } ?>

<?php require __DIR__ . '/../includes/admin_footer.php'; ?>


