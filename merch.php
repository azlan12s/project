<?php
require_once __DIR__ . '/includes/functions.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
if ($id > 0) {
    $item = db_one("SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id WHERE merchandise_items.id = $id LIMIT 1");
    if ($item) {
        $user = current_user();
        $bookmarked = $user ? is_item_bookmarked((int) $user['id'], 'merch', $id) : false;

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
        <section class="wrap detail-hero">
            <div class="cover" style="min-height:360px;border-radius:18px;background-image:url('<?php echo e(card_image_with_fallback($item['image'], $item['category_slug'])); ?>')"></div>
            <div>
                <span class="badge"><?php echo e($item['tag']); ?></span>
                <a class="chip" href="<?php echo e(category_url($item['category_slug'])); ?>"><?php echo e($item['category_name']); ?></a>
                <h1><?php echo e($item['title']); ?></h1>
                <p class="meta"><?php echo (int) $item['views']; ?> views · display-only showcase</p>
                <p><?php echo nl2br(e($item['description'])); ?></p>
                <p class="meta">No cart and no payment. This item is listed for fans to browse.</p>
                <?php render_share_bar($item['title']); ?>
                <?php if ($user) { ?>
                    <form method="post" style="margin-top:16px">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="bookmark_merch" value="1">
                        <?php if (!$bookmarked) { ?>
                            <div class="form-row"><label>Bookmark note</label><input name="notes" placeholder="Optional note for this merch"></div>
                        <?php } ?>
                        <button class="btn" type="submit"><?php echo $bookmarked ? 'Remove bookmark' : 'Bookmark Merch'; ?></button>
                    </form>
                <?php } else { ?>
                    <p style="margin-top:16px"><a class="btn-ghost" href="<?php echo e(site_url('/login.php')); ?>">Log in to bookmark merch</a></p>
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
}
$items = db_all("SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id WHERE $where ORDER BY merchandise_items.created_at DESC, merchandise_items.id DESC");
$upcoming_merch = db_all("SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id WHERE (merchandise_items.tag LIKE '%Pre-Order%' OR merchandise_items.tag LIKE '%Limited%' OR merchandise_items.tag LIKE '%Upcoming%') ORDER BY merchandise_items.id DESC LIMIT 4");
$cats = categories();
$page_title = 'Merchandise';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <h1>Merchandise showcase</h1>
    <p class="lede">Browse fandom collectibles, Limited Edition drops, and Pre-Order releases. Display-only showcase (no checkout).</p>
    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:22px">
        <a class="chip <?php echo $cat === '' ? 'active' : ''; ?>" href="<?php echo e(site_url('/merch.php')); ?>">All Fandoms</a>
        <?php foreach ($cats as $c) { ?>
            <a class="chip <?php echo $cat === $c['slug'] ? 'active' : ''; ?>" href="<?php echo e(site_url('/merch.php?category=' . rawurlencode($c['slug']))); ?>"><?php echo e($c['name']); ?></a>
        <?php } ?>
    </div>
    <div class="grid grid-4">
        <?php foreach ($items as $m) {
            card(array(
                'href'  => '/merch.php?id=' . (int) $m['id'],
                'image' => $m['title'] === 'Controller Skin Art Print' ? '/assets/images/games.webp' : $m['image'],
                'category_slug' => $m['category_slug'],
                'title' => $m['title'],
                'desc'  => $m['description'],
                'badge' => $m['tag'],
                'meta'  => $m['category_name'] . ' · ' . (int) $m['views'] . ' views',
                'cta'   => 'View item',
            ));
        } ?>
    </div>
    <?php if (!$items) { ?><p class="meta">No merchandise items found for this fandom filter.</p><?php } ?>
</section>
<?php if ($upcoming_merch) { ?>
<section class="wrap section">
    <div class="section-head">
        <h2>Upcoming releases &amp; Limited Drops</h2>
        <span class="chip">Pre-Order &amp; Collector Showcase</span>
    </div>
    <div class="grid grid-4">
        <?php foreach ($upcoming_merch as $um) {
            card(array(
                'href'  => '/merch.php?id=' . (int) $um['id'],
                'image' => $um['title'] === 'Controller Skin Art Print' ? '/assets/images/games.webp' : $um['image'],
                'category_slug' => $um['category_slug'],
                'title' => $um['title'],
                'desc'  => $um['description'],
                'badge' => $um['tag'],
                'meta'  => $um['category_name'] . ' · Upcoming Showcase',
                'cta'   => 'Preview release',
            ));
        } ?>
    </div>
</section>
<?php } ?>
<?php require __DIR__ . '/includes/footer.php'; ?>
