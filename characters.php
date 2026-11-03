<?php
require_once __DIR__ . '/includes/functions.php';
$cats = categories();
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$where = '1=1';
if ($category !== '') {
    $cat_row = category_by_slug($category);
    if ($cat_row) {
        $where = 'character_profiles.category_id = ' . (int) $cat_row['id'];
    }
}
$items = db_all("SELECT character_profiles.*, categories.name AS category_name, categories.slug AS category_slug FROM character_profiles JOIN categories ON categories.id = character_profiles.category_id WHERE $where ORDER BY character_profiles.name ASC");
$page_title = 'Characters';
require __DIR__ . '/includes/header.php';
?>
<style>
.character-card .cover {
    height: 300px;
    max-height: 320px;
    aspect-ratio: 3 / 4;
    background-size: cover;
    background-position: center top;
}
.character-card .card-desc {
    display: -webkit-box;
    -webkit-box-orient: vertical;
    -webkit-line-clamp: 2;
    line-clamp: 2;
    overflow: hidden;
    min-height: 2.8em;
    font-size: 0.88rem;
    line-height: 1.45;
}
</style>
<section class="wrap section">
    <h1>Character profiles</h1>
    <p class="lede">Explore iconic heroes, idols, and legends across all eight fandoms.</p>
    <p style="margin: 14px 0 22px;">
        <a class="chip <?php echo $category === '' ? 'active' : ''; ?>" href="<?php echo e(site_url('/characters.php')); ?>">All fandoms</a>
        <?php foreach ($cats as $c) { ?>
            <a class="chip <?php echo $category === $c['slug'] ? 'active' : ''; ?>" href="<?php echo e(site_url('/characters.php?category=' . rawurlencode($c['slug']))); ?>"><?php echo e($c['name']); ?></a>
        <?php } ?>
    </p>
    <div class="grid grid-4">
        <?php foreach ($items as $ch) {
            card(array(
                'href'          => '/character.php?id=' . (int) $ch['id'],
                'image'         => $ch['image'],
                'category_slug' => $ch['category_slug'],
                'title'         => $ch['name'],
                'desc'          => $ch['bio'],
                'badge'         => $ch['category_name'],
                'meta'          => $ch['role_type'],
                'class'         => 'character-card',
                'cta'           => 'View profile',
            ));
        } ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
