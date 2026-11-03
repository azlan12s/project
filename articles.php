<?php
require_once __DIR__ . '/includes/functions.php';
$user = current_user();
$published = db_all("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.type = 'article' AND contents.status = 'published' ORDER BY contents.created_at DESC");
$page_title = 'Fan articles';
require __DIR__ . '/includes/header.php';

$submit_href = $user
    ? site_url('/submit-article.php')
    : site_url('/login.php?next=' . rawurlencode('/submit-article.php'));
?>
<section class="wrap section">
    <h1>Fan articles</h1>
    <p class="lede">Read essays, reviews, and deep dives written by the community across all eight fandoms.</p>

    <div class="grid grid-4" style="margin-top: 24px;">
        <?php foreach ($published as $item) {
            card(array(
                'href'          => '/content.php?id=' . (int) $item['id'],
                'image'         => $item['thumbnail'],
                'category_slug' => $item['category_slug'],
                'title'         => $item['title'],
                'desc'          => $item['body'],
                'badge'         => $item['category_name'],
                'cta'           => 'Read more',
            ));
        } ?>
    </div>
    <?php if (!$published) { ?>
        <p class="meta">No published articles yet.</p>
    <?php } ?>
</section>

<section class="wrap section">
    <div class="article-cta-banner">
        <div>
            <span class="eyebrow">Got a story or theory to share?</span>
            <h2>Create your own article</h2>
            <p class="lede">Share your reviews, theories, and character deep-dives with fans across all eight fandoms.</p>
        </div>
        <a class="btn" href="<?php echo e($submit_href); ?>">Submit your own article <span aria-hidden="true">&rarr;</span></a>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
