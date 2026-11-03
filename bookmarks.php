<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if (is_admin()) {
    redirect('/admin/index.php');
}
ensure_srs_tables();
$user = current_user();
$uid = (int) $user['id'];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['update_note'])) {
        $bid = (int) $_POST['bookmark_id'];
        $notes = esc($_POST['notes']);
        db_query("UPDATE bookmarks SET notes = '$notes' WHERE id = $bid AND user_id = $uid");
        flash_set('ok', 'Note updated.');
        redirect('/bookmarks.php');
    }
    if (isset($_POST['remove_bookmark'])) {
        $bid = (int) $_POST['bookmark_id'];
        db_query("DELETE FROM bookmarks WHERE id = $bid AND user_id = $uid");
        flash_set('ok', 'Bookmark removed.');
        redirect('/bookmarks.php');
    }
    if (isset($_POST['update_item_note'])) {
        $ibid = (int) $_POST['item_bookmark_id'];
        $notes = esc($_POST['notes']);
        db_query("UPDATE item_bookmarks SET notes = '$notes' WHERE id = $ibid AND user_id = $uid");
        flash_set('ok', 'Note updated.');
        redirect('/bookmarks.php');
    }
    if (isset($_POST['remove_item_bookmark'])) {
        $ibid = (int) $_POST['item_bookmark_id'];
        db_query("DELETE FROM item_bookmarks WHERE id = $ibid AND user_id = $uid");
        flash_set('ok', 'Bookmark removed.');
        redirect('/bookmarks.php');
    }
}

$rows = db_all("SELECT bookmarks.*, contents.title, contents.thumbnail, categories.name AS category_name, categories.slug AS category_slug FROM bookmarks JOIN contents ON contents.id = bookmarks.content_id JOIN categories ON categories.id = contents.category_id WHERE bookmarks.user_id = $uid ORDER BY bookmarks.created_at DESC");
$char_bookmarks = db_all("SELECT item_bookmarks.*, character_profiles.name AS title, character_profiles.image AS thumbnail, categories.name AS category_name, categories.slug AS category_slug FROM item_bookmarks JOIN character_profiles ON character_profiles.id = item_bookmarks.item_id JOIN categories ON categories.id = character_profiles.category_id WHERE item_bookmarks.user_id = $uid AND item_bookmarks.item_type = 'character' ORDER BY item_bookmarks.created_at DESC");
$merch_bookmarks = db_all("SELECT item_bookmarks.*, merchandise_items.title, merchandise_items.image AS thumbnail, categories.name AS category_name, categories.slug AS category_slug FROM item_bookmarks JOIN merchandise_items ON merchandise_items.id = item_bookmarks.item_id JOIN categories ON categories.id = merchandise_items.category_id WHERE item_bookmarks.user_id = $uid AND item_bookmarks.item_type = 'merch' ORDER BY item_bookmarks.created_at DESC");

if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="fanhub-bookmarks-' . date('Ymd') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Type', 'Fandom Category', 'Title', 'Personal Note', 'Saved At'));
    foreach ($rows as $r) {
        fputcsv($out, array('Content', $r['category_name'], $r['title'], $r['notes'], $r['created_at']));
    }
    foreach ($char_bookmarks as $cb) {
        fputcsv($out, array('Character Profile', $cb['category_name'], $cb['title'], $cb['notes'], $cb['created_at']));
    }
    foreach ($merch_bookmarks as $mb) {
        fputcsv($out, array('Merchandise', $mb['category_name'], $mb['title'], $mb['notes'], $mb['created_at']));
    }
    fclose($out);
    exit;
}

$page_title = 'Bookmarks';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <div class="section-head">
        <div>
            <h1>Bookmarks drawer</h1>
            <p class="meta">Save your favorite Fandom Content, Character Profiles, and Merchandise items with personal notes.</p>
        </div>
        <a class="btn-ghost" href="<?php echo e(site_url('/bookmarks.php?export=csv')); ?>">Export Bookmarks (CSV)</a>
    </div>

    <h2 style="margin-top:22px">Saved Content (<?php echo count($rows); ?>)</h2>
    <div class="grid grid-4">
        <?php foreach ($rows as $b) { ?>
            <article class="card">
                <div class="card-top"><span class="card-badge"><?php echo e($b['category_name']); ?></span></div>
                <a class="cover" href="<?php echo e(site_url('/content.php?id=' . (int) $b['content_id'])); ?>" style="background-image:url('<?php echo e(card_image_with_fallback($b['thumbnail'], $b['category_slug'])); ?>');" aria-label="<?php echo e($b['title']); ?>"></a>
                <div class="card-body">
                    <h3 class="card-title"><a href="<?php echo e(site_url('/content.php?id=' . (int) $b['content_id'])); ?>"><?php echo e($b['title']); ?></a></h3>
                    <form method="post" class="bookmark-note">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="bookmark_id" value="<?php echo (int) $b['id']; ?>">
                        <label class="sr-only" for="note-<?php echo (int) $b['id']; ?>">Note for <?php echo e($b['title']); ?></label>
                        <textarea id="note-<?php echo (int) $b['id']; ?>" name="notes" rows="2" placeholder="Add a note"><?php echo e($b['notes']); ?></textarea>
                        <div style="display:flex;gap:8px">
                            <button class="card-cta" name="update_note" value="1" type="submit">Save note</button>
                            <button class="btn-danger" name="remove_bookmark" value="1" type="submit">Remove</button>
                        </div>
                    </form>
                </div>
            </article>
        <?php } ?>
    </div>
    <?php if (!$rows) { ?><p class="meta">No content bookmarked yet. Visit <a href="<?php echo e(site_url('/explore.php')); ?>">Explore</a> to save articles and media.</p><?php } ?>

    <h2 style="margin-top:32px">Saved Character Profiles (<?php echo count($char_bookmarks); ?>)</h2>
    <div class="grid grid-4">
        <?php foreach ($char_bookmarks as $cb) { ?>
            <article class="card">
                <div class="card-top"><span class="card-badge"><?php echo e($cb['category_name']); ?> · Character</span></div>
                <a class="cover" href="<?php echo e(site_url('/character.php?id=' . (int) $cb['item_id'])); ?>" style="background-image:url('<?php echo e(card_image_with_fallback($cb['thumbnail'], $cb['category_slug'])); ?>');" aria-label="<?php echo e($cb['title']); ?>"></a>
                <div class="card-body">
                    <h3 class="card-title"><a href="<?php echo e(site_url('/character.php?id=' . (int) $cb['item_id'])); ?>"><?php echo e($cb['title']); ?></a></h3>
                    <form method="post" class="bookmark-note">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="item_bookmark_id" value="<?php echo (int) $cb['id']; ?>">
                        <textarea name="notes" rows="2" placeholder="Add a note"><?php echo e($cb['notes']); ?></textarea>
                        <div style="display:flex;gap:8px">
                            <button class="card-cta" name="update_item_note" value="1" type="submit">Save note</button>
                            <button class="btn-danger" name="remove_item_bookmark" value="1" type="submit">Remove</button>
                        </div>
                    </form>
                </div>
            </article>
        <?php } ?>
    </div>
    <?php if (!$char_bookmarks) { ?><p class="meta">No character profiles bookmarked yet. Browse <a href="<?php echo e(site_url('/characters.php')); ?>">Characters</a> to bookmark your favorites.</p><?php } ?>

    <h2 style="margin-top:32px">Saved Merchandise (<?php echo count($merch_bookmarks); ?>)</h2>
    <div class="grid grid-4">
        <?php foreach ($merch_bookmarks as $mb) { ?>
            <article class="card">
                <div class="card-top"><span class="card-badge"><?php echo e($mb['category_name']); ?> · Merch</span></div>
                <a class="cover" href="<?php echo e(site_url('/merch.php?id=' . (int) $mb['item_id'])); ?>" style="background-image:url('<?php echo e(card_image_with_fallback($mb['thumbnail'], $mb['category_slug'])); ?>');" aria-label="<?php echo e($mb['title']); ?>"></a>
                <div class="card-body">
                    <h3 class="card-title"><a href="<?php echo e(site_url('/merch.php?id=' . (int) $mb['item_id'])); ?>"><?php echo e($mb['title']); ?></a></h3>
                    <form method="post" class="bookmark-note">
                        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                        <input type="hidden" name="item_bookmark_id" value="<?php echo (int) $mb['id']; ?>">
                        <textarea name="notes" rows="2" placeholder="Add a note"><?php echo e($mb['notes']); ?></textarea>
                        <div style="display:flex;gap:8px">
                            <button class="card-cta" name="update_item_note" value="1" type="submit">Save note</button>
                            <button class="btn-danger" name="remove_item_bookmark" value="1" type="submit">Remove</button>
                        </div>
                    </form>
                </div>
            </article>
        <?php } ?>
    </div>
    <?php if (!$merch_bookmarks) { ?><p class="meta">No merchandise items bookmarked yet. Browse <a href="<?php echo e(site_url('/merch.php')); ?>">Merchandise</a> to bookmark items.</p><?php } ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
