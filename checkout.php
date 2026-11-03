<?php
require_once __DIR__ . '/includes/functions.php';
ensure_srs_tables();

$user = current_user();

// Handle AJAX or POST order placement
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $is_ajax = (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) === 'xmlhttprequest')
        || (isset($_SERVER['CONTENT_TYPE']) && strpos($_SERVER['CONTENT_TYPE'], 'application/json') !== false)
        || isset($_POST['ajax']);

    if (!$user) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(401);
            echo json_encode(array('ok' => false, 'error' => 'auth_required', 'message' => 'Please log in or create an account to complete your checkout.'));
            exit;
        }
        flash_set('error', 'Please log in or register before checking out.');
        redirect('/login.php?next=' . rawurlencode('/checkout.php'));
    }

    csrf_check();

    $uid = (int) $user['id'];
    $raw_items = isset($_POST['items']) ? $_POST['items'] : '';
    $items = array();
    if (is_array($raw_items)) {
        $items = $raw_items;
    } elseif (is_string($raw_items) && $raw_items !== '') {
        $decoded = json_decode($raw_items, true);
        if (is_array($decoded)) {
            $items = $decoded;
        }
    }

    $name = trim(isset($_POST['shipping_name']) ? $_POST['shipping_name'] : $user['name']);
    $phone = trim(isset($_POST['shipping_phone']) ? $_POST['shipping_phone'] : '');
    $address = trim(isset($_POST['shipping_address']) ? $_POST['shipping_address'] : '');
    $city = trim(isset($_POST['shipping_city']) ? $_POST['shipping_city'] : '');
    $notes = trim(isset($_POST['order_notes']) ? $_POST['order_notes'] : '');

    if (empty($items)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(array('ok' => false, 'error' => 'empty_cart', 'message' => 'Your cart is empty. Please add items before checking out.'));
            exit;
        }
        flash_set('error', 'Your cart is empty.');
        redirect('/merch.php');
    }

    if (strlen($name) < 2 || strlen($phone) < 7 || strlen($address) < 5 || strlen($city) < 2) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(422);
            echo json_encode(array('ok' => false, 'error' => 'validation_failed', 'message' => 'Please provide complete delivery details (Name, Phone, Address, City).'));
            exit;
        }
        flash_set('error', 'Please provide complete delivery details.');
        redirect('/checkout.php');
    }

    // Filter out any upcoming items from checkout
    $valid_items = array();
    $total = 0.00;
    foreach ($items as $it) {
        $m_id = (int) (isset($it['id']) ? $it['id'] : 0);
        if ($m_id > 0) {
            $m_row = db_one("SELECT id, tag FROM merchandise_items WHERE id = $m_id LIMIT 1");
            if ($m_row) {
                $tag = isset($m_row['tag']) ? $m_row['tag'] : '';
                if (stripos($tag, 'Pre-Order') !== false || stripos($tag, 'Limited') !== false || stripos($tag, 'Upcoming') !== false) {
                    continue; // Upcoming showcase drop
                }
            }
        }
        $qty = max(1, (int) (isset($it['qty']) ? $it['qty'] : 1));
        $price = (float) (isset($it['price']) ? $it['price'] : 0);
        $total += ($price * $qty);
        $valid_items[] = $it;
    }
    $items = $valid_items;

    if (empty($items)) {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(400);
            echo json_encode(array('ok' => false, 'error' => 'invalid_items', 'message' => 'Upcoming showcase items cannot be ordered. Your cart contains no available items for checkout.'));
            exit;
        }
        flash_set('error', 'Upcoming showcase items cannot be checked out.');
        redirect('/merch.php');
    }

    $order_num = 'FHP-COD-' . strtoupper(substr(uniqid(), -6));
    $name_sql = esc($name);
    $phone_sql = esc($phone);
    $address_sql = esc($address);
    $city_sql = esc($city);
    $notes_sql = esc($notes);
    $total_sql = (float) $total;

    $order_inserted = db_query("INSERT INTO merch_orders 
        (user_id, order_number, total_amount, payment_method, status, shipping_name, shipping_phone, shipping_address, shipping_city, order_notes, created_at, updated_at) 
        VALUES 
        ($uid, '$order_num', $total_sql, 'Cash on Delivery', 'pending', '$name_sql', '$phone_sql', '$address_sql', '$city_sql', '$notes_sql', NOW(), NOW())");

    if ($order_inserted) {
        $order_id = db_insert_id();

        foreach ($items as $it) {
            $m_id = (int) (isset($it['id']) ? $it['id'] : 0);
            $m_title = esc(isset($it['title']) ? $it['title'] : 'Merchandise Item');
            $m_price = (float) (isset($it['price']) ? $it['price'] : 0);
            $m_qty = max(1, (int) (isset($it['qty']) ? $it['qty'] : 1));
            $m_img = esc(isset($it['image']) ? $it['image'] : '');

            db_query("INSERT INTO merch_order_items (order_id, merch_id, title, price, quantity, image) 
                VALUES ($order_id, $m_id, '$m_title', $m_price, $m_qty, '$m_img')");
        }

        log_activity($uid, 'merch_order', "Placed COD order $order_num ($" . number_format($total, 2) . ")");

        if ($is_ajax) {
            header('Content-Type: application/json');
            echo json_encode(array(
                'ok' => true,
                'order_number' => $order_num,
                'total' => number_format($total, 2),
                'message' => "Order #$order_num placed successfully via Cash on Delivery! You can track it in your Account Dashboard.",
                'redirect' => site_url('/dashboard.php#my-orders')
            ));
            exit;
        }

        flash_set('ok', "Your order #$order_num has been placed successfully via Cash on Delivery!");
        redirect('/dashboard.php#my-orders');
    } else {
        if ($is_ajax) {
            header('Content-Type: application/json');
            http_response_code(500);
            echo json_encode(array('ok' => false, 'error' => 'server_error', 'message' => 'Could not save your order. Please try again.'));
            exit;
        }
        flash_set('error', 'Could not save your order. Please try again.');
        redirect('/merch.php');
    }
}

// GET view: Checkout Page
if (!$user) {
    flash_set('error', 'Please log in or create an account to checkout.');
    redirect('/login.php?next=' . rawurlencode('/checkout.php'));
}

$page_title = 'Checkout · Cash on Delivery';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section" style="max-width:880px;">
    <div class="section-head">
        <div>
            <h1>Checkout (Cash on Delivery)</h1>
            <p class="lede">Pay in cash when your official fandom merchandise package arrives at your doorstep.</p>
        </div>
        <a class="btn-ghost" href="<?php echo e(site_url('/merch.php')); ?>">&larr; Back to Merch</a>
    </div>

    <div class="grid grid-2" style="gap:24px; align-items:start;">
        <div class="card card-body">
            <h3>Delivery Details</h3>
            <form method="post" id="pageCheckoutForm">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="items" id="pageCheckoutItems" value="[]">

                <div class="form-row">
                    <label for="ship-name">Recipient full name</label>
                    <input id="ship-name" name="shipping_name" value="<?php echo e($user['name']); ?>" required minlength="2">
                </div>
                <div class="form-row">
                    <label for="ship-phone">Contact phone number</label>
                    <input id="ship-phone" name="shipping_phone" placeholder="e.g. +92 300 1234567" required minlength="7">
                </div>
                <div class="form-row">
                    <label for="ship-city">City</label>
                    <input id="ship-city" name="shipping_city" placeholder="e.g. Karachi / Lahore / Islamabad" required minlength="2">
                </div>
                <div class="form-row">
                    <label for="ship-address">Street address &amp; delivery location</label>
                    <textarea id="ship-address" name="shipping_address" rows="3" placeholder="House/Flat number, Street, Landmark..." required minlength="5"></textarea>
                </div>
                <div class="form-row">
                    <label for="order-notes">Special delivery instructions <span class="meta">(Optional)</span></label>
                    <input id="order-notes" name="order_notes" placeholder="e.g. Call before arrival">
                </div>

                <div class="cart-cod-pill" style="margin-top:10px;">
                    <span>💵</span>
                    <span>Payment Method: <strong>Cash on Delivery (COD) Only</strong></span>
                </div>

                <button class="btn btn-checkout" type="submit" id="pageCheckoutSubmit" style="margin-top:14px;">Confirm Cash on Delivery Order</button>
            </form>
        </div>

        <div class="card card-body">
            <h3>Order Summary</h3>
            <div id="pageCheckoutList" style="display:flex; flex-direction:column; gap:10px; margin:14px 0;">
                <p class="meta">Loading your cart items...</p>
            </div>
            <div class="side-cart-summary" style="margin-top:14px; border-top:1px solid var(--line); padding-top:12px;">
                <div class="cart-summary-row"><span>Subtotal:</span><strong id="pageSubtotal">$0.00</strong></div>
                <div class="cart-summary-row"><span>Delivery:</span><span class="text-free-ship">FREE</span></div>
                <div class="cart-summary-row cart-summary-total"><span>Total to Pay:</span><strong id="pageTotal">$0.00</strong></div>
            </div>
        </div>
    </div>
</section>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var cart = JSON.parse(localStorage.getItem('fhp_user_cart_v1') || '[]');
    var hiddenInput = document.getElementById('pageCheckoutItems');
    var listContainer = document.getElementById('pageCheckoutList');
    var subtotalEl = document.getElementById('pageSubtotal');
    var totalEl = document.getElementById('pageTotal');
    var submitBtn = document.getElementById('pageCheckoutSubmit');

    if (!cart || cart.length === 0) {
        listContainer.innerHTML = '<p class="meta">Your cart is currently empty. <a href="<?php echo e(site_url('/merch.php')); ?>">Browse Merchandise</a></p>';
        submitBtn.disabled = true;
        return;
    }

    hiddenInput.value = JSON.stringify(cart);
    var subtotal = 0;
    var html = '';
    cart.forEach(function(item) {
        var qty = item.qty || 1;
        var itemTotal = (parseFloat(item.price) || 0) * qty;
        subtotal += itemTotal;
        html += '<div style="display:flex; gap:10px; align-items:center; border-bottom:1px solid var(--line); padding-bottom:8px;">' +
            '<div style="width:48px; height:48px; border-radius:6px; background:var(--bg-2) url(\'' + (item.image || '') + '\') center/cover; flex:0 0 48px;"></div>' +
            '<div style="flex:1; min-width:0;">' +
                '<h4 style="margin:0; font-size:0.88rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;">' + item.title + '</h4>' +
                '<small class="meta">Qty: ' + qty + ' &times; $' + item.price + '</small>' +
            '</div>' +
            '<strong>$' + itemTotal.toFixed(2) + '</strong>' +
        '</div>';
    });
    listContainer.innerHTML = html;
    subtotalEl.textContent = '$' + subtotal.toFixed(2);
    totalEl.textContent = '$' + subtotal.toFixed(2);

    document.getElementById('pageCheckoutForm').addEventListener('submit', function() {
        localStorage.removeItem('fhp_user_cart_v1');
    });
});
</script>
<?php require __DIR__ . '/includes/footer.php'; ?>

