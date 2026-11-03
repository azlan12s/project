<?php
require_once __DIR__ . '/includes/functions.php';
$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ch = db_one("SELECT character_profiles.*, categories.name AS category_name, categories.slug AS category_slug FROM character_profiles JOIN categories ON categories.id = character_profiles.category_id WHERE character_profiles.id = $id LIMIT 1");
if (!$ch) {
    redirect('/characters.php');
}
$user = current_user();
$bookmarked = $user ? is_item_bookmarked((int) $user['id'], 'character', $id) : false;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && isset($_POST['bookmark_char'])) {
    csrf_check();
    ensure_srs_tables();
    $uid = (int) $user['id'];
    if ($bookmarked) {
        db_query("DELETE FROM item_bookmarks WHERE user_id = $uid AND item_type = 'character' AND item_id = $id");
        flash_set('ok', 'Character bookmark removed.');
    } else {
        $note = esc(isset($_POST['notes']) ? $_POST['notes'] : '');
        db_query("INSERT INTO item_bookmarks (user_id, item_type, item_id, notes, created_at) VALUES ($uid, 'character', $id, '$note', NOW())");
        log_activity($uid, 'bookmark_character', $ch['name']);
        flash_set('ok', 'Character saved to bookmarks.');
    }
    redirect('/character.php?id=' . $id);
}

$related = array();
if ($ch['related_content_ids'] !== '') {
    $ids = array();
    foreach (explode(',', $ch['related_content_ids']) as $rid) {
        $ids[] = (int) $rid;
    }
    $idlist = implode(',', $ids);
    if ($idlist !== '') {
          /* The card renderer shows a type badge, a description preview and a
             genre line, so the query has to bring those columns along. */
          $related = db_all("SELECT contents.id, contents.title, contents.thumbnail, contents.body, contents.type, contents.genre, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.id IN ($idlist) AND contents.status = 'published'");
    }
}
$page_title = $ch['name'];
require __DIR__ . '/includes/header.php';
?>
<style>
.character-hero {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 40px;
    padding: 28px 0;
    align-items: start;
}
.character-media {
    order: 1;
    border-radius: var(--radius);
    overflow: hidden;
    background: #000;
    border: 1px solid var(--line);
    width: 100%;
    min-height: 620px;
    aspect-ratio: 2 / 3;
    position: relative;
    box-shadow: var(--shadow-card);
    transition: transform var(--t-base) var(--ease-out), border-color var(--t-base) var(--ease), box-shadow var(--t-base) var(--ease-out);
}
.character-media:hover {
    transform: scale(1.015);
    border-color: var(--accent);
    box-shadow: 0 26px 60px rgba(0, 0, 0, 0.5);
}
.character-media img {
    width: 100%;
    height: 100%;
    object-fit: cover;
    object-position: center top;
    display: block;
}
.character-info {
    order: 2;
    display: flex;
    flex-direction: column;
    justify-content: flex-start;
}
.character-info h1 {
    margin: 12px 0 10px;
    font-size: clamp(2rem, 3.2vw, 2.7rem);
    line-height: 1.15;
    font-weight: 800;
    letter-spacing: -0.02em;
}
.character-info .meta {
    margin-bottom: 20px;
    color: var(--muted);
    font-size: 0.95rem;
}
.character-section-title {
    margin-top: 22px;
    margin-bottom: 8px;
    font-size: 1.15rem;
    color: var(--accent, #ec4899);
    font-weight: 700;
}
.character-body-text {
    line-height: 1.75;
    margin-bottom: 16px;
    color: var(--text);
    font-size: 1rem;
}
.character-actions {
    margin-top: 24px;
}
@media (max-width: 900px) {
    .character-hero {
        grid-template-columns: 1fr;
    }
    .character-media {
        order: 1;
        min-height: 420px;
        aspect-ratio: 2 / 3;
        max-width: 380px;
        margin: 0 auto 20px;
    }
    .character-info {
        order: 2;
    }}
</style>

<article class="wrap character-hero">
    <div class="character-media">
        <img src="<?php echo e(card_image_with_fallback($ch['image'], $ch['category_slug'])); ?>" alt="<?php echo e($ch['name']); ?>">
    </div>
    <div class="character-info">
        <div>
            <a class="chip" href="<?php echo e(category_url($ch['category_slug'])); ?>"><?php echo e($ch['category_name']); ?></a>
            <span class="chip"><?php echo e($ch['role_type']); ?></span>
        </div>
        <h1><?php echo e($ch['name']); ?></h1>
        <p class="meta"><?php echo e($ch['category_name']); ?> · <?php echo e($ch['role_type']); ?></p>

        <h3 class="character-section-title">Detailed Biography &amp; Lore</h3>
        <p class="character-body-text"><?php echo nl2br(e($ch['bio'])); ?></p>

        <h3 class="character-section-title">Attributes &amp; Key Abilities</h3>
        <p class="character-body-text"><?php echo e($ch['attributes']); ?></p>

        <h3 class="character-section-title">Timeline &amp; Story Milestones</h3>
        <p class="character-body-text"><?php echo e($ch['timeline']); ?></p>

        <?php render_share_bar($ch['name']); ?>

        <?php if ($user) { ?>
            <form method="post" style="margin: 16px 0;">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="bookmark_char" value="1">
                <?php if (!$bookmarked) { ?>
                    <div class="form-row"><label>Bookmark note</label><input name="notes" placeholder="Optional note for this character"></div>
                <?php } ?>
                <button class="btn" type="submit"><?php echo $bookmarked ? 'Remove bookmark' : 'Bookmark character'; ?></button>
            </form>
        <?php } ?>

        <div class="character-actions">
            <a href="<?php echo e(site_url('/characters.php')); ?>" class="btn-ghost">
                &larr; Back to character profiles
            </a>
        </div>
    </div>
</article>

<section class="wrap section">
    <h2>Related content</h2>
    <div class="grid grid-4">
        <?php foreach ($related as $item) {
            card(array(
                'href'  => '/content.php?id=' . (int) $item['id'],
                'image' => $item['thumbnail'],
                'category_slug' => $item['category_slug'],
                'title' => $item['title'],
                'desc'  => $item['body'],
                'badge' => $item['type'],
                'meta'  => $item['genre'],
                'cta'   => 'Read more',
            ));
        } ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
