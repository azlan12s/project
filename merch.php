<?php
require_once __DIR__ . '/includes/functions.php';

$is_merchandise_page = true;
$active = 'merch';

function is_upcoming_merch_item($item) {
    if (!$item) {
        return false;
    }
    $tag = isset($item['tag']) ? $item['tag'] : '';
    return (stripos($tag, 'Pre-Order') !== false || stripos($tag, 'Limited') !== false || stripos($tag, 'Upcoming') !== false);
}

function get_merch_info($item) {
    $id = (int) $item['id'];
    $prices = array(1 => 29.99, 2 => 49.99, 3 => 34.99, 4 => 19.99, 5 => 39.99, 6 => 24.99, 7 => 54.99, 8 => 44.99);
    $price = isset($prices[$id]) ? $prices[$id] : (24.99 + (($id * 7) % 35));
    $tag = isset($item['tag']) ? $item['tag'] : '';
    $is_preorder = (stripos($tag, 'Pre-Order') !== false);
    $is_limited = (stripos($tag, 'Limited') !== false);
    $stock = $is_preorder ? 'Pre-Order · Ships Next Month' : ($is_limited ? 'Limited Edition · 12 Units Left' : 'In Stock · Ships in 24h');
    $rating = number_format(4.7 + (($id % 3) * 0.1), 1);
    $reviews = 48 + ($id * 16);
    $cat_prefix = strtoupper(substr(preg_replace('/[^a-zA-Z]/', '', isset($item['category_name']) ? $item['category_name'] : 'MCH'), 0, 3));
    $sku = 'FHP-' . $cat_prefix . '-' . str_pad($id, 4, '0', STR_PAD_LEFT);
    $material = (stripos($item['title'], 'Print') !== false || stripos($item['title'], 'Art') !== false)
        ? '300gsm Archival Matte Cotton Paper'
        : ((stripos($item['title'], 'Shirt') !== false || stripos($item['title'], 'Hoodie') !== false)
            ? '100% Combed Heavyweight Cotton'
            : 'Collector Vinyl / Hand-painted PVC');

    return array(
        'price' => number_format($price, 2),
        'price_num' => $price,
        'stock' => $stock,
        'rating' => $rating,
        'reviews' => $reviews,
        'sku' => $sku,
        'material' => $material,
    );
}

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $item = db_one("SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id WHERE merchandise_items.id = $id LIMIT 1");
    if ($item) {
        if (is_upcoming_merch_item($item)) {
            flash_set('info', 'Upcoming drops are showcase previews only and cannot be viewed in detail or purchased yet.');
            redirect('/merch.php');
        }
        $user = current_user();
        $bookmarked = $user ? is_item_bookmarked((int) $user['id'], 'merch', $id) : false;
        $info = get_merch_info($item);
        $img = $item['title'] === 'Controller Skin Art Print' ? site_url('/assets/images/games.webp') : card_image_with_fallback($item['image'], $item['category_slug']);

        if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && isset($_POST['bookmark_merch'])) {
            csrf_check();
            ensure_srs_tables();
            $uid = (int) $user['id'];
            if ($bookmarked) {
                db_query("DELETE FROM item_bookmarks WHERE user_id = $uid AND item_type = 'merch' AND item_id = $id");
                flash_set('ok', 'Merchandise removed from bookmarks.');
            } else {
                $note = esc(isset($_POST['notes']) ? $_POST['notes'] : '');
                db_query("INSERT INTO item_bookmarks (user_id, item_type, item_id, notes, created_at) VALUES ($uid, 'merch', $id, '$note', NOW())");
                log_activity($uid, 'bookmark_merch', $item['title']);
                flash_set('ok', 'Merchandise saved to bookmarks.');
            }
            redirect('/merch.php?id=' . $id);
        }

        bump_views('merchandise_items', $id);
        $item['views'] = (int) $item['views'] + 1;
        $page_title = $item['title'];
        require __DIR__ . '/includes/header.php';
        ?>
        <section class="wrap detail-hero merch-detail-hero">
            <div class="cover" style="min-height:420px;border-radius:18px;background-image:url('<?php echo e($img); ?>')"></div>
            <div>
                <div class="merch-detail-header-badges">
                    <span class="badge"><?php echo e($item['tag']); ?></span>
                    <a class="chip" href="<?php echo e(category_url($item['category_slug'])); ?>"><?php echo e($item['category_name']); ?></a>
                    <span class="merch-rating-pill">★ <?php echo e($info['rating']); ?> / 5.0 (<?php echo e($info['reviews']); ?> reviews)</span>
                </div>
                <h1 style="margin-top:6px;"><?php echo e($item['title']); ?></h1>

                <div class="merch-price-box">
                    <span class="merch-price-val">$<?php echo e($info['price']); ?></span>
                    <span class="merch-stock-badge"><span class="merch-stock-dot"></span><?php echo e($info['stock']); ?></span>
                </div>

                <p style="line-height:1.6;color:var(--text);"><?php echo nl2br(e($item['description'])); ?></p>

                <div class="merch-specs-grid">
                    <div class="spec-item"><span class="spec-label">SKU ID</span><strong><?php echo e($info['sku']); ?></strong></div>
                    <div class="spec-item"><span class="spec-label">Material</span><strong><?php echo e($info['material']); ?></strong></div>
                    <div class="spec-item"><span class="spec-label">Authenticity</span><strong>Official Licensed Merch</strong></div>
                    <div class="spec-item"><span class="spec-label">Shipping</span><strong>Free Express Shipping</strong></div>
                </div>

                <div class="merch-buy-row">
                    <div class="merch-qty-picker">
                        <button type="button" class="qty-btn" id="qtyMinus" aria-label="Decrease quantity">-</button>
                        <input type="number" id="detailQty" value="1" min="1" max="99" aria-label="Quantity">
                        <button type="button" class="qty-btn" id="qtyPlus" aria-label="Increase quantity">+</button>
                    </div>
                    <button type="button" class="btn btn-add-cart-lg" id="detailAddToCart" data-id="<?php echo (int) $item['id']; ?>" data-title="<?php echo e($item['title']); ?>" data-price="<?php echo e($info['price']); ?>" data-image="<?php echo e($img); ?>" data-cat="<?php echo e($item['category_name']); ?>">
                        <svg viewBox="0 0 24 24" width="20" height="20" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L6 6ZM6 6 4.5 3H2m7 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
                        <span>Add to Cart</span>
                    </button>
                </div>

                <p class="meta"><?php echo (int) $item['views']; ?> views · Official Fandom Drop</p>
                <?php render_share_bar($item['title']); ?>
                <?php if ($user) { ?>
                    <form method="post" style="margin-top:16px">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="bookmark_merch" value="1">
                        <?php if (!$bookmarked) { ?>
                            <div class="form-row"><label>Bookmark note</label><input name="notes" placeholder="Optional note for this merch"></div>
                        <?php } ?>
                        <button class="btn-ghost" type="submit"><?php echo $bookmarked ? 'Remove bookmark' : 'Bookmark Merch'; ?></button>
                    </form>
                <?php } else { ?>
                    <p style="margin-top:16px"><a class="btn-ghost" href="<?php echo e(site_url('/login.php?next=' . rawurlencode($_SERVER['REQUEST_URI']))); ?>">Log in to bookmark merch</a></p>
                <?php } ?>
            </div>
        </section>
        <?php
        require __DIR__ . '/includes/footer.php';
        exit;
    }
}

$cat = isset($_GET['category']) ? trim($_GET['category']) : '';
$where = '1=1';
if ($cat !== '') {
    $where .= " AND categories.slug = '" . esc($cat) . "'";
} else {
    $where .= " AND NOT (merchandise_items.tag LIKE '%Pre-Order%' OR merchandise_items.tag LIKE '%Limited%' OR merchandise_items.tag LIKE '%Upcoming%')";
}
$items = db_all("SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id WHERE $where ORDER BY merchandise_items.created_at DESC, merchandise_items.id DESC");
$upcoming_merch = db_all("SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id WHERE (merchandise_items.tag LIKE '%Pre-Order%' OR merchandise_items.tag LIKE '%Limited%' OR merchandise_items.tag LIKE '%Upcoming%') ORDER BY merchandise_items.id DESC LIMIT 4");
$cats = categories();
$page_title = 'Merchandise';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <div class="section-head">
        <div>
            <h1>Merchandise Store &amp; Drops</h1>
            <p class="lede">Discover official fandom collectibles, figures, apparel, and limited-run collector drops.</p>
        </div>
        <div>
            <button type="button" class="btn btn-ghost open-cart-btn" onclick="if(window.openSideCart){window.openSideCart();}">
                <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:text-bottom;margin-right:4px;"><path d="M6 6h15l-1.5 9h-12L6 6ZM6 6 4.5 3H2m7 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
                <span>View Cart (<b class="page-cart-count">0</b>)</span>
            </button>
        </div>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:22px">
        <a class="chip <?php echo $cat === '' ? 'active' : ''; ?>" href="<?php echo e(site_url('/merch.php')); ?>">All Fandoms</a>
        <?php foreach ($cats as $c) { ?>
            <a class="chip <?php echo $cat === $c['slug'] ? 'active' : ''; ?>" href="<?php echo e(site_url('/merch.php?category=' . rawurlencode($c['slug']))); ?>"><?php echo e($c['name']); ?></a>
        <?php } ?>
    </div>
    <div class="grid grid-4">
        <?php foreach ($items as $m) {
            $info = get_merch_info($m);
            $img = $m['title'] === 'Controller Skin Art Print' ? site_url('/assets/images/games.webp') : card_image_with_fallback($m['image'], $m['category_slug']);
            $is_up = is_upcoming_merch_item($m);
        ?>
            <article class="card merch-card">
                <div class="card-top">
                    <span class="card-badge"><?php echo e($m['tag']); ?></span>
                    <span class="merch-card-price">$<?php echo e($info['price']); ?></span>
                </div>
                <?php if (!$is_up) { ?>
                    <a class="cover" href="<?php echo e(site_url('/merch.php?id=' . (int) $m['id'])); ?>" style="background-image:url('<?php echo e($img); ?>')" aria-label="<?php echo e($m['title']); ?>"></a>
                <?php } else { ?>
                    <div class="cover" style="background-image:url('<?php echo e($img); ?>')" aria-label="<?php echo e($m['title']); ?>"></div>
                <?php } ?>
                <div class="card-body">
                    <div class="merch-meta-row">
                        <span class="chip"><?php echo e($m['category_name']); ?></span>
                        <span class="merch-rating-chip">★ <?php echo e($info['rating']); ?></span>
                    </div>
                    <h3 class="card-title">
                        <?php if (!$is_up) { ?>
                            <a href="<?php echo e(site_url('/merch.php?id=' . (int) $m['id'])); ?>"><?php echo e($m['title']); ?></a>
                        <?php } else { ?>
                            <span><?php echo e($m['title']); ?></span>
                        <?php } ?>
                    </h3>
                    <p class="card-desc"><?php echo e($m['description']); ?></p>
                    <div class="merch-specs-snippet">
                        <small><b>Specs:</b> <?php echo e($info['material']); ?></small>
                        <small><span class="merch-stock-dot"></span><?php echo e($info['stock']); ?></small>
                    </div>
                    <div class="merch-card-footer">
                        <?php if (!$is_up) { ?>
                            <button type="button" class="card-cta merch-add-btn" data-id="<?php echo (int) $m['id']; ?>" data-title="<?php echo e($m['title']); ?>" data-price="<?php echo e($info['price']); ?>" data-image="<?php echo e($img); ?>" data-cat="<?php echo e($m['category_name']); ?>">
                                <svg viewBox="0 0 24 24" width="16" height="16" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><path d="M6 6h15l-1.5 9h-12L6 6ZM6 6 4.5 3H2m7 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
                                <span>Add to Cart</span>
                            </button>
                            <a class="card-cta" href="<?php echo e(site_url('/merch.php?id=' . (int) $m['id'])); ?>">Details &rarr;</a>
                        <?php } else { ?>
                            <span class="card-cta is-soon" style="width:100%; text-align:center;">
                                <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                                <span>Upcoming Drop · Showcase Only</span>
                            </span>
                        <?php } ?>
                    </div>
                </div>
            </article>
        <?php } ?>
    </div>
    <?php if (!$items) { ?><p class="meta">No merchandise items found for this fandom filter.</p><?php } ?>
</section>

<?php if ($upcoming_merch) { ?>
<section class="wrap section">
    <div class="section-head">
        <div>
            <h2>Upcoming releases &amp; Limited Drops</h2>
            <p class="lede">Showcase &amp; collector previews. Not available for cart or checkout yet.</p>
        </div>
        <span class="chip">Pre-Order &amp; Collector Showcase</span>
    </div>
    <div class="grid grid-4">
        <?php foreach ($upcoming_merch as $um) {
            $info = get_merch_info($um);
            $img = $um['title'] === 'Controller Skin Art Print' ? site_url('/assets/images/games.webp') : card_image_with_fallback($um['image'], $um['category_slug']);
        ?>
            <article class="card merch-card">
                <div class="card-top">
                    <span class="card-badge"><?php echo e($um['tag']); ?></span>
                    <span class="merch-card-price">$<?php echo e($info['price']); ?></span>
                </div>
                <div class="cover" style="background-image:url('<?php echo e($img); ?>')" aria-label="<?php echo e($um['title']); ?>"></div>
                <div class="card-body">
                    <div class="merch-meta-row">
                        <span class="chip"><?php echo e($um['category_name']); ?></span>
                        <span class="merch-rating-chip">★ <?php echo e($info['rating']); ?></span>
                    </div>
                    <h3 class="card-title"><?php echo e($um['title']); ?></h3>
                    <p class="card-desc"><?php echo e($um['description']); ?></p>
                    <div class="merch-specs-snippet">
                        <small><b>Specs:</b> <?php echo e($info['material']); ?></small>
                        <small><span class="merch-stock-dot"></span><?php echo e($info['stock']); ?></small>
                    </div>
                    <div class="merch-card-footer">
                        <span class="card-cta is-soon" style="width:100%; text-align:center;">
                            <svg viewBox="0 0 24 24" width="15" height="15" fill="none" stroke="currentColor" stroke-width="2" style="vertical-align:middle;margin-right:4px;"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                            <span>Upcoming Drop · Showcase Only</span>
                        </span>
                    </div>
                </div>
            </article>
        <?php } ?>
    </div>
</section>
<?php } ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

