<?php
require_once __DIR__ . '/includes/functions.php';
$type = isset($_GET['type']) ? $_GET['type'] : '';
$where = "contents.status = 'published' AND contents.type IN ('video','audio','explainer')";
if (in_array($type, array('video', 'audio', 'explainer'), true)) {
    $where .= " AND contents.type = '" . esc($type) . "'";
}
$items = db_all("SELECT contents.*, categories.name AS category_name FROM contents JOIN categories ON categories.id = contents.category_id WHERE $where ORDER BY contents.popularity_score DESC");
$page_title = 'Multimedia Center';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <h1>Multimedia Center</h1>
    <p class="lede">Trailers, soundtracks, podcasts, and animated explainers with tags, ratings, and bookmarks on each detail page.</p>
    <p>
        <a class="chip" href="<?php echo e(site_url('/multimedia.php')); ?>">All</a>
        <a class="chip" href="<?php echo e(site_url('/multimedia.php?type=video')); ?>">Video</a>
        <a class="chip" href="<?php echo e(site_url('/multimedia.php?type=audio')); ?>">Audio</a>
        <a class="chip" href="<?php echo e(site_url('/multimedia.php?type=explainer')); ?>">Explainers</a>
    </p>
    <!-- Additional descriptive text for each category -->
    
    <div class="grid grid-4">
        <?php foreach ($items as $item) {
            card(array(
                'href'  => '/content.php?id=' . (int) $item['id'],
                'image' => $item['thumbnail'],
                'title' => $item['title'],
                'desc'  => $item['body'],
                'badge' => $item['type'],
                'meta'  => $item['category_name'] . ' · ★ ' . (int) $item['popularity_score'],
                'cta'   => 'Watch now',
            ));
        } ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
