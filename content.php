<?php
require_once __DIR__ . '/includes/functions.php';

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$item = db_one("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.id = $id AND contents.status = 'published' LIMIT 1");
if (!$item) {
    http_response_code(404);
    $page_title = 'Not found';
    require __DIR__ . '/includes/header.php';
    echo '<section class="wrap section"><h1>Content not found</h1><p><a href="' . e(site_url('/explore.php')) . '">Back to explorer</a></p></section>';
    require __DIR__ . '/includes/footer.php';
    exit;
}

bump_views('contents', $id);
track_recently_viewed($id);
$item['views'] = (int) $item['views'] + 1;
$rating_info = avg_rating($id);
$reviews = db_all("SELECT media_ratings.*, users.name FROM media_ratings JOIN users ON users.id = media_ratings.user_id WHERE content_id = $id ORDER BY media_ratings.created_at DESC");
$user = current_user();
$bookmarked = $user ? is_bookmarked((int) $user['id'], $id) : false;
$mine = $user ? db_one("SELECT * FROM media_ratings WHERE user_id = " . (int) $user['id'] . " AND content_id = $id LIMIT 1") : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user) {
    csrf_check();
    if (isset($_POST['bookmark'])) {
        if ($bookmarked) {
            $uid = (int) $user['id'];
            db_query("DELETE FROM bookmarks WHERE user_id = $uid AND content_id = $id");
            flash_set('ok', 'Bookmark removed.');
        } else {
            $uid = (int) $user['id'];
            $note = esc(isset($_POST['notes']) ? $_POST['notes'] : '');
            db_query("INSERT INTO bookmarks (user_id, content_id, notes, created_at) VALUES ($uid, $id, '$note', NOW())");
            log_activity($uid, 'bookmark', $item['title']);
            flash_set('ok', 'Saved to bookmarks.');
        }
        redirect('/content.php?id=' . $id);
    }
    if (isset($_POST['rating'])) {
        $stars = (int) $_POST['rating'];
        if ($stars < 1 || $stars > 5) {
            flash_set('error', 'Rating must be between 1 and 5.');
        } else {
            $review = esc(isset($_POST['review']) ? $_POST['review'] : '');
            $uid = (int) $user['id'];
            if ($mine) {
                db_query("UPDATE media_ratings SET rating = $stars, review = '$review' WHERE user_id = $uid AND content_id = $id");
            } else {
                db_query("INSERT INTO media_ratings (user_id, content_id, rating, review, created_at) VALUES ($uid, $id, $stars, '$review', NOW())");
            }
            log_activity($uid, 'rating', $item['title']);
            flash_set('ok', 'Review saved.');
        }
        redirect('/content.php?id=' . $id);
    }
}

$page_title = $item['title'];
require __DIR__ . '/includes/header.php';
?>
<style>.detail-hero { display: grid; grid-template-columns: 1.1fr 0.9fr; gap: 24px; padding: 28px 0; align-items: start; }
.detail-media { border-radius: var(--radius); overflow: hidden; background: #000; border: 1px solid var(--line); align-self: start; }
.detail-media iframe, .detail-media video { width: 100%; aspect-ratio: 16 / 9; display: block; border: 0; min-height: 0; }
.detail-media img, .detail-media .cover { width: 100%; height: 460px; max-height: 65vh; object-fit: cover; object-position: top center; background-size: cover; background-position: center; display: block; }
@media (max-width: 1120px) {
    .detail-hero { grid-template-columns: 1fr; }
    .detail-media img, .detail-media .cover { height: 380px; }
}
</style>
<article class="wrap detail-hero">
    <div class="detail-media">
        <?php if ($item['type'] === 'video' || $item['type'] === 'explainer') { ?>
            <?php if ($item['media_url'] !== '') { ?>
                <?php if (preg_match('~^uploads/videos/[a-f0-9]{32}\\.(mp4|webm)$~', $item['media_url'])) { ?>
                    <video controls playsinline preload="metadata" poster="<?php echo e(card_image_with_fallback($item['thumbnail'], $item['category_slug'])); ?>">
                        <source src="<?php echo e(site_url($item['media_url'])); ?>">
                    </video>
                <?php } else { ?>
                    <iframe src="<?php echo e($item['media_url']); ?>" title="<?php echo e($item['title']); ?>" allowfullscreen></iframe>
                <?php } ?>
            <?php } ?>
        <?php } elseif ($item['type'] === 'audio') { ?>
            <div class="card-body">
                <p>Soundtrack / podcast</p>
                <audio controls src="<?php echo e($item['media_url']); ?>"></audio>
            </div>
        <?php } else { ?>
            <div class="cover" style="width:100%;height:460px;max-height:65vh;background-image:url('<?php echo e(card_image_with_fallback($item['thumbnail'], $item['category_slug'])); ?>')"></div>
        <?php } ?>
    </div>
    <div>
        <a class="chip" href="<?php echo e(category_url($item['category_slug'])); ?>"><?php echo e($item['category_name']); ?></a>
        <span class="chip"><?php echo e($item['type']); ?></span>
        <h1><?php echo e($item['title']); ?></h1>
        <p class="meta"><?php echo e($item['genre']); ?> · <?php echo (int) $item['release_year']; ?> · <?php echo (int) $item['views']; ?> views · popularity <?php echo (int) $item['popularity_score']; ?></p>
        <p class="stars">Average <?php echo e($rating_info['avg']); ?> / 5 (<?php echo (int) $rating_info['total']; ?> reviews)</p>
        <style>.detail-body {
            display: -webkit-box;
            -webkit-line-clamp: 6;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }
</style>
    <p class="detail-body"><?php echo nl2br(e($item['body'])); ?></p>
        <p class="detail-body-extra">This content provides additional insight and background, enriching the viewer's experience and offering deeper context about the subject.</p>
        <?php render_share_bar($item['title']); ?>

        <?php if ($user) { ?>
            <form method="post" style="margin:16px 0">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="bookmark" value="1">
                <?php if (!$bookmarked) { ?>
                    <div class="form-row"><label>Bookmark note</label><input name="notes" placeholder="Optional note"></div>
                <?php } ?>
                <button class="btn" type="submit"><?php echo $bookmarked ? 'Remove bookmark' : 'Bookmark'; ?></button>
            </form>
            <form method="post">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <div class="form-row">
                    <label>Your 5-star rating</label>
                    <select name="rating" required>
                        <?php for ($s = 1; $s <= 5; $s++) { ?>
                            <option value="<?php echo $s; ?>" <?php echo ($mine && (int) $mine['rating'] === $s) ? 'selected' : ''; ?>><?php echo $s; ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-row"><label>Review</label><textarea name="review" rows="4"><?php echo $mine ? e($mine['review']) : ''; ?></textarea></div>
                <button class="btn" type="submit">Save review</button>
            </form>
        <?php } else { ?>
            <p><a class="btn" href="<?php echo e(site_url('/login.php')); ?>">Log in to bookmark or review</a></p>
        <?php } ?>
    </div>
</article>
<section class="wrap section">
    <h2>Reviews</h2>
    <?php foreach ($reviews as $r) { ?>
        <div class="review">
            <strong><?php echo e($r['name']); ?></strong>
            <span class="stars"><?php echo str_repeat('★', (int) $r['rating']); ?></span>
            <p><?php echo nl2br(e($r['review'])); ?></p>
        </div>
    <?php } ?>
    <?php if (!$reviews) { ?><p class="meta">No reviews yet.</p><?php } ?>
</section>
<?php
$similar_items = db_all("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' AND contents.id <> $id AND contents.category_id = " . (int) $item['category_id'] . " ORDER BY contents.popularity_score DESC, contents.views DESC LIMIT 4");
if (count($similar_items) < 4) {
    $similar_items = get_recommended_contents($user, 4, $id);
}
if ($similar_items) {
?>
<section class="wrap section">
    <div class="section-head">
        <h2>Recommended in <?php echo e($item['category_name']); ?> &amp; Beyond</h2>
        <a href="<?php echo e(site_url('/explore.php?category=' . rawurlencode($item['category_slug']))); ?>">Explore more</a>
    </div>
    <div class="grid grid-4">
        <?php foreach ($similar_items as $sim) {
            card(array(
                'href'          => '/content.php?id=' . (int) $sim['id'],
                'image'         => $sim['thumbnail'],
                'category_slug' => $sim['category_slug'],
                'title'         => $sim['title'],
                'desc'          => $sim['body'],
                'badge'         => $sim['category_name'],
                'meta'          => $sim['type'] . ' · ★ ' . (int) $sim['popularity_score'],
                'cta'           => 'Read more',
            ));
        } ?>
    </div>
</section>
<?php } ?>
<?php require __DIR__ . '/includes/footer.php'; ?>

