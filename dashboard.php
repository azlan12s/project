<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if (is_admin()) {
    redirect('/admin/index.php');
}
ensure_srs_tables();
$user = current_user();
$uid = (int) $user['id'];
$hour = (int) date('G');
$greet = 'Hello';
if ($hour < 12) { $greet = 'Good morning'; }
elseif ($hour < 18) { $greet = 'Good afternoon'; }
else { $greet = 'Good evening'; }

$fav = favorite_slugs($user);
$fan_profile = user_fan_profile($uid);
$recommended = get_recommended_contents($user, 4);
$recently_viewed = get_recently_viewed(4);
$rsvps = db_all("SELECT event_rsvps.*, events.title, events.city, events.event_date, categories.name AS category_name FROM event_rsvps JOIN events ON events.id = event_rsvps.event_id JOIN categories ON categories.id = events.category_id WHERE event_rsvps.user_id = $uid ORDER BY events.event_date ASC");

$bookmarks = db_all("SELECT bookmarks.*, contents.title, contents.thumbnail, categories.name AS category_name FROM bookmarks JOIN contents ON contents.id = bookmarks.content_id JOIN categories ON categories.id = contents.category_id WHERE bookmarks.user_id = $uid ORDER BY bookmarks.created_at DESC");
$notes = db_all("SELECT * FROM personal_notes WHERE user_id = $uid ORDER BY created_at DESC");
$ratings = db_all("SELECT media_ratings.*, contents.title FROM media_ratings JOIN contents ON contents.id = media_ratings.content_id WHERE media_ratings.user_id = $uid ORDER BY media_ratings.created_at DESC LIMIT 8");
$activity = db_all("SELECT * FROM activity_logs WHERE user_id = $uid ORDER BY created_at DESC LIMIT 8");
$pending = db_all("SELECT id, title, status FROM contents WHERE user_id = $uid ORDER BY created_at DESC");
$orders = db_all("SELECT * FROM merch_orders WHERE user_id = $uid ORDER BY created_at DESC");

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['note_title'])) {
        $title = esc(trim($_POST['note_title']));
        $body = esc(trim($_POST['note_body']));
        db_query("INSERT INTO personal_notes (user_id, title, body, created_at) VALUES ($uid, '$title', '$body', NOW())");
        flash_set('ok', 'Note saved.');
        redirect('/dashboard.php');
    }
    if (isset($_POST['delete_note_id'])) {
        $nid = (int) $_POST['delete_note_id'];
        db_query("DELETE FROM personal_notes WHERE id = $nid AND user_id = $uid");
        flash_set('ok', 'Note removed.');
        redirect('/dashboard.php');
    }
}

$page_title = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap dash">
    <aside class="side">
        <?php if ($user['avatar'] !== '') { ?><img class="avatar" src="<?php echo e(site_url($user['avatar'])); ?>" alt=""><?php } ?>
        <h3><?php echo e($user['name']); ?></h3>
        <p class="meta"><?php echo e($user['role']); ?> · <span class="chip"><?php echo e($fan_profile['rank']); ?></span></p>
        <a href="<?php echo e(site_url('/profile.php')); ?>">Profile &amp; preferences</a>
        <a href="#my-orders">My Merch Orders (<?php echo count($orders); ?>)</a>
        <a href="<?php echo e(site_url('/bookmarks.php')); ?>">Bookmarks drawer</a>
        <a href="<?php echo e(site_url('/submit-article.php')); ?>">Submit article</a>
        <?php if ($user['role'] === 'admin') { ?><a href="<?php echo e(site_url('/admin/index.php')); ?>">Admin panel</a><?php } ?>
        <a href="<?php echo e(site_url('/logout.php')); ?>">Logout</a>
    </aside>
    <div>
        <h1><?php echo e($greet); ?>, <?php echo e($user['name']); ?></h1>
        <p class="lede">Your fandom shortcuts, rank, tailored recommendations, saved items, orders, and notes live here.</p>

        <div class="grid grid-2" style="margin: 18px 0 26px;">
            <div class="stat card card-body">
                <span class="meta">Fan Rank</span>
                <b><?php echo e($fan_profile['rank']); ?></b>
            </div>
            <div class="stat card card-body">
                <span class="meta">Saved Bookmarks</span>
                <b><?php echo (int) $fan_profile['bookmarks_count']; ?></b>
            </div>
        </div>

        <h2>Favorite fandoms</h2>
        <p>
            <?php foreach ($fav as $slug) { ?>
                <a class="chip" href="<?php echo e(category_url($slug)); ?>"><?php echo e($slug); ?></a>
            <?php } ?>
            <?php if (!$fav) { ?><span class="meta">Pick favorites in <a href="<?php echo e(site_url('/profile.php')); ?>">Profile</a>.</span><?php } ?>
        </p>

        <div style="display:flex; justify-content:space-between; align-items:center; margin:28px 0 14px;">
            <h2 id="my-orders" style="margin:0;">My Merchandise Orders</h2>
            <a href="<?php echo e(site_url('/merch.php')); ?>" class="btn-ghost" style="font-size:0.82rem; padding:6px 14px;">Explore Store &rarr;</a>
        </div>
        <?php if ($orders) { ?>
            <div style="display:flex; flex-direction:column; gap:14px; margin-bottom:28px;">
                <?php foreach ($orders as $ord) {
                    $order_items = db_all("SELECT * FROM merch_order_items WHERE order_id = " . (int) $ord['id']);
                    $status_cls = 'badge-order-' . $ord['status'];
                ?>
                <div class="card card-body" style="border:1px solid var(--line); border-radius:14px;">
                    <div style="display:flex; justify-content:space-between; align-items:flex-start; flex-wrap:wrap; gap:10px; border-bottom:1px solid var(--line); padding-bottom:10px; margin-bottom:12px;">
                        <div>
                            <strong style="font-size:1.05rem; letter-spacing:0.02em;"><?php echo e($ord['order_number']); ?></strong>
                            <p class="meta" style="margin:2px 0 0;"><?php echo e($ord['created_at']); ?> &middot; Method: <strong><?php echo e($ord['payment_method']); ?></strong></p>
                        </div>
                        <div style="text-align:right;">
                            <span class="badge <?php echo $status_cls; ?>" style="text-transform:uppercase; font-size:0.75rem; letter-spacing:0.05em;"><?php echo e($ord['status']); ?></span>
                            <p style="margin:4px 0 0; font-weight:800; color:var(--accent-2); font-size:1.15rem;">Total: $<?php echo number_format((float) $ord['total_amount'], 2); ?></p>
                        </div>
                    </div>

                    <div style="font-size:0.85rem; color:var(--muted); margin-bottom:12px;">
                        <span>Deliver to: <strong><?php echo e($ord['shipping_name']); ?></strong> (<?php echo e($ord['shipping_phone']); ?>)</span> &middot;
                        <span>Address: <strong><?php echo e($ord['shipping_address']); ?>, <?php echo e($ord['shipping_city']); ?></strong></span>
                    </div>

                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(260px, 1fr)); gap:10px;">
                        <?php foreach ($order_items as $item) { ?>
                            <div style="display:flex; gap:10px; align-items:center; padding:8px; background:var(--bg-2); border-radius:8px; border:1px solid var(--line);">
                                <?php if ($item['image']) { ?>
                                    <div style="width:48px; height:48px; border-radius:6px; background:var(--card) url('<?php echo e($item['image']); ?>') center/cover; flex:0 0 48px;"></div>
                                <?php } ?>
                                <div style="min-width:0; flex:1;">
                                    <h4 style="margin:0 0 2px; font-size:0.86rem; white-space:nowrap; overflow:hidden; text-overflow:ellipsis;"><?php echo e($item['title']); ?></h4>
                                    <small class="meta">Qty: <?php echo (int) $item['quantity']; ?> &times; $<?php echo number_format((float) $item['price'], 2); ?></small>
                                </div>
                            </div>
                        <?php } ?>
                    </div>
                </div>
                <?php } ?>
            </div>
        <?php } else { ?>
            <p class="meta" style="margin-bottom:28px;">You haven't placed any merchandise orders yet. Browse our <a href="<?php echo e(site_url('/merch.php')); ?>">Merchandise Store</a> to order with Cash on Delivery!</p>
        <?php } ?>

        <?php if ($recommended) { ?>
        <h2>Recommended for you</h2>
        <div class="grid grid-4">
            <?php foreach ($recommended as $rec) {
                card(array(
                    'href'          => '/content.php?id=' . (int) $rec['id'],
                    'image'         => $rec['thumbnail'],
                    'category_slug' => $rec['category_slug'],
                    'title'         => $rec['title'],
                    'desc'          => $rec['body'],
                    'badge'         => $rec['category_name'],
                    'meta'          => $rec['type'] . ' · ★ ' . (int) $rec['popularity_score'],
                    'cta'           => 'Read more',
                ));
            } ?>
        </div>
        <?php } ?>

        <?php if ($recently_viewed) { ?>
        <h2>Recently viewed</h2>
        <div class="grid grid-4">
            <?php foreach ($recently_viewed as $rv) {
                card(array(
                    'href'          => '/content.php?id=' . (int) $rv['id'],
                    'image'         => $rv['thumbnail'],
                    'category_slug' => $rv['category_slug'],
                    'title'         => $rv['title'],
                    'desc'          => $rv['body'],
                    'badge'         => 'Recently Viewed',
                    'meta'          => $rv['category_name'],
                    'cta'           => 'Open again',
                ));
            } ?>
        </div>
        <?php } ?>

        <h2>Your Event RSVPs</h2>
        <?php if ($rsvps) { ?>
            <?php foreach ($rsvps as $ev_r) { ?>
                <div class="review" style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:10px;">
                    <div>
                        <strong><a href="<?php echo e(site_url('/event.php?id=' . (int) $ev_r['event_id'])); ?>"><?php echo e($ev_r['title']); ?></a></strong>
                        <p class="meta" style="margin:4px 0 0;"><?php echo e($ev_r['category_name']); ?> · <?php echo e($ev_r['city']); ?> · <?php echo e($ev_r['event_date']); ?> · <span class="chip"><?php echo e(ucfirst($ev_r['status'])); ?></span></p>
                    </div>
                    <a class="btn-ghost" href="<?php echo e(site_url('/event.php?id=' . (int) $ev_r['event_id'] . '&ical=1')); ?>">Download .ics</a>
                </div>
            <?php } ?>
        <?php } else { ?>
            <p class="meta">No event RSVPs yet. Browse <a href="<?php echo e(site_url('/events.php')); ?>">Upcoming Events</a> to RSVP.</p>
        <?php } ?>

        <h2>Bookmarks</h2>
        <div class="grid grid-4">
            <?php foreach ($bookmarks as $b) {
                card(array(
                    'href'  => '/content.php?id=' . (int) $b['content_id'],
                    'image' => $b['thumbnail'],
                    'title' => $b['title'],
                    'desc'  => $b['notes'],
                    'badge' => 'Bookmarked',
                    'meta'  => $b['category_name'],
                    'cta'   => 'Open',
                ));
            } ?>
        </div>

        <h2>Personal notes</h2>
        <form method="post" class="card card-body">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <div class="form-row"><input name="note_title" placeholder="Note title" required></div>
            <div class="form-row"><textarea name="note_body" rows="3" placeholder="Tracker text"></textarea></div>
            <button class="btn" type="submit">Add note</button>
        </form>
        <?php foreach ($notes as $n) { ?>
            <div class="review" style="display:flex; justify-content:space-between; align-items:flex-start; gap:12px;">
                <div>
                    <strong><?php echo e($n['title']); ?></strong>
                    <p><?php echo nl2br(e($n['body'])); ?></p>
                </div>
                <form method="post" style="margin:0;">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="delete_note_id" value="<?php echo (int) $n['id']; ?>">
                    <button class="btn-danger" type="submit">Delete</button>
                </form>
            </div>
        <?php } ?>

        <h2>Recent ratings</h2>
        <?php foreach ($ratings as $r) { ?>
            <p><?php echo e($r['title']); ?> — <?php echo (int) $r['rating']; ?>/5</p>
        <?php } ?>

        <h2>Your submissions</h2>
        <?php foreach ($pending as $p) { ?>
            <p><?php echo e($p['title']); ?> <span class="chip"><?php echo e($p['status']); ?></span></p>
        <?php } ?>

        <h2>Activity</h2>
        <?php foreach ($activity as $a) { ?>
            <p class="meta"><?php echo e($a['created_at']); ?> · <?php echo e($a['action']); ?> · <?php echo e($a['detail']); ?></p>
        <?php } ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

