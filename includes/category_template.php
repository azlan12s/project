<?php


require_once __DIR__ . '/functions.php';
require_once __DIR__ . '/spotlight.php';

function category_page_not_found()
{
    http_response_code(404);
    $page_title = 'Category not found';
    require __DIR__ . '/header.php';
    echo '<section class="wrap section">';
    echo '<p class="eyebrow">/404</p>';
    echo '<h1>That category does not exist</h1>';
    echo '<p class="lede">Pick one of the eight fandoms below.</p>';
    echo '<div class="grid grid-4">';
    foreach (categories() as $c) {
        echo '<a class="cat-card ' . e($c['slug']) . '" href="' . e(category_url($c['slug'])) . '" style="--category-image:url(' . e(category_banner_image($c['slug'])) . ')">';
        echo '<h3>' . e($c['name']) . '</h3>';
        echo '<span class="category-link">Open <span aria-hidden="true">&rarr;</span></span>';
        echo '</a>';
    }
    echo '</div></section>';
    require __DIR__ . '/footer.php';
    exit;
}

function render_category_page($slug)
{
    $slug = strtolower(trim((string) $slug));
    $cat = category_by_slug($slug);
    if (!$cat) {
        category_page_not_found();
        return;
    }

    $cid = (int) $cat['id'];
    ensure_upcoming_events();
    $copy = category_copy($cat['slug']);
    $counts = category_counts($cid);
    $others = categories();

    $featured = db_all(
        "SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug
         FROM contents JOIN categories ON categories.id = contents.category_id
         WHERE contents.category_id = $cid AND contents.status = 'published' AND contents.featured = 1
         ORDER BY contents.popularity_score DESC, contents.views DESC LIMIT 4"
    );
    $titles = db_all(
        "SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug
         FROM contents JOIN categories ON categories.id = contents.category_id
         WHERE contents.category_id = $cid AND contents.status = 'published'
         ORDER BY contents.popularity_score DESC, contents.created_at DESC LIMIT 12"
    );
    $people = db_all(
        "SELECT character_profiles.*, categories.name AS category_name, categories.slug AS category_slug
         FROM character_profiles JOIN categories ON categories.id = character_profiles.category_id
         WHERE character_profiles.category_id = $cid
         ORDER BY character_profiles.name ASC LIMIT 8"
    );
    $events = db_all(
        "SELECT events.*, categories.name AS category_name, categories.slug AS category_slug
         FROM events JOIN categories ON categories.id = events.category_id
         WHERE events.category_id = $cid
         ORDER BY events.event_date ASC LIMIT 6"
    );
    $merch = db_all(
        "SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug
         FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id
         WHERE merchandise_items.category_id = $cid
         ORDER BY merchandise_items.views DESC LIMIT 6"
    );
    $roadmap = category_coming_soon($cat['slug']);
    $total = category_total($counts);

    $page_title = $cat['name'];
    require __DIR__ . '/header.php';

    render_category_hero($cat, $copy, $counts);
    render_category_jump_nav($cat, $copy);
    render_category_spotlight($cat);
    render_category_featured($cat, $copy, $featured);
    render_category_titles($cat, $copy, $titles);
    render_category_people($cat, $copy, $people);
    render_category_events($cat, $copy, $events);
    render_category_merch($cat, $copy, $merch);
    render_category_others($cat, $others);
    render_category_soon($cat, $roadmap);

    require __DIR__ . '/footer.php';
}


function render_category_hero($cat, $copy, $counts)
{
    ?>
    <section class="cat-hero" style="--cat-image:url('<?php echo e(category_banner_image($cat['slug'])); ?>')">
        <div class="cat-hero-scrim" aria-hidden="true"></div>
        <div class="wrap cat-hero-content">
            <p class="eyebrow">/<?php echo e($cat['slug']); ?></p>
            <h1 class="cat-hero-title"><?php echo e($cat['name']); ?></h1>
            <p class="lede"><?php echo e($copy['blurb']); ?></p>
            <div class="cat-hero-stats">
                <div><b><?php echo (int) $counts['contents']; ?></b><span>Titles</span></div>
                <div><b><?php echo (int) $counts['characters']; ?></b><span>Characters</span></div>
                <div><b><?php echo (int) $counts['events']; ?></b><span>Events</span></div>
                <div><b><?php echo (int) $counts['merch']; ?></b><span>Merch</span></div>
            </div>
            <div class="cat-hero-actions">
                <a class="btn" href="<?php echo e(site_url('/explore.php?category=' . rawurlencode($cat['slug']))); ?>">Filter in Explore</a>
                <a class="btn-ghost" href="#cat-soon">What is coming</a>
            </div>
        </div>
    </section>
<?php
}


function render_category_jump_nav($cat, $copy)
{
    ?>
    <nav class="cat-jump" aria-label="<?php echo e($cat['name']); ?> sections">
        <div class="wrap cat-jump-inner">
            <a href="#cat-spotlight">Most famous</a>
            <a href="#cat-featured"><?php echo e($copy['spotlight']); ?></a>
            <a href="#cat-titles"><?php echo e($copy['sections'][0]); ?></a>
            <a href="#cat-people"><?php echo e($copy['sections'][1]); ?></a>
            <a href="#cat-events"><?php echo e($copy['sections'][2]); ?></a>
            <a href="#cat-merch"><?php echo e($copy['sections'][3]); ?></a>
            <a href="#cat-others">Other fandoms</a>
            <a href="#cat-soon">Coming soon</a>
        </div>
    </nav>
<?php
}


function render_category_spotlight($cat)
{
    $spotlights = spotlight_for($cat['slug']);
    ?>
    <section class="wrap section" id="cat-spotlight">
        <div class="section-head">
            <h2>Most famous <?php echo e(strtolower($cat['name'])); ?></h2>
            <a href="#cat-titles">All titles</a>
        </div>
        <?php if ($spotlights) { ?>
            <p class="meta">Five entries everyone knows, with the movies, gallery and cosplay that go with each one. Use the section menu above to jump straight to a title.</p>
        <?php } else { ?>
            <p class="meta">Spotlights for <?php echo e($cat['name']); ?> are being curated. The full catalogue is listed below.</p>
        <?php } ?>
    </section>

    <?php foreach ($spotlights as $entry) { ?>
    <section class="wrap section spot-block" id="spot-<?php echo e($entry['key']); ?>">
        <div class="section-head">
            <h2><?php echo e($entry['name']); ?></h2>
            <span class="chip"><?php echo e($entry['from']); ?></span>
        </div>

        <p class="lede"><?php echo e($entry['line']); ?></p>
        <p class="spot-bio"><?php echo e($entry['bio']); ?></p>

        <div class="spot-grid">

            <div class="spot-panel">
                <h3>Most famous movies</h3>
                <ul class="spot-list">
                    <?php foreach ($entry['works'] as $w) { ?>
                        <li>
                            <span class="spot-list-title"><?php echo e($w[0]); ?></span>
                            <span class="spot-list-meta"><?php echo (int) $w[1]; ?></span>
                        </li>
                    <?php } ?>
                </ul>
            </div>

            <div class="spot-panel">
                <h3>Gallery carousel</h3>
                <p class="meta">Clips from this category play here in caption order, stills as poster and fallback.</p>
                <?php
                    $galleryImages = spotlight_gallery_images($cat['slug'], $entry['key']);
                    $galleryVideos = spotlight_gallery_videos($cat['slug'], $entry['key']);
                    $galleryCount = max(count($entry['gallery']), count($galleryImages), count($galleryVideos));
                ?>
                <div class="spot-carousel" data-spot-carousel>
                    <div class="sg-viewport">
                        <?php for ($gi = 0; $gi < $galleryCount; $gi++) {
                            $caption = isset($entry['gallery'][$gi]) ? $entry['gallery'][$gi] : 'Gallery clip ' . ($gi + 1);
                            $shot = isset($galleryImages[$gi]) ? $galleryImages[$gi] : '';
                            $clip = isset($galleryVideos[$gi]) ? $galleryVideos[$gi] : '';
                        ?>
                            <figure class="sg-slide<?php echo $gi === 0 ? ' is-active' : ''; ?><?php echo $shot === '' ? ' is-placeholder' : ''; ?>"
                                role="group" aria-roledescription="slide"
                                aria-label="Clip <?php echo (int) $gi + 1; ?> of <?php echo (int) $galleryCount; ?>"
                                <?php if ($shot !== '') { ?>data-image="<?php echo e($shot); ?>" style="background-image:url('<?php echo e($shot); ?>')" data-painted="1"<?php } ?>>
                                <?php if ($clip !== '') { ?>
                                <video class="sg-video" data-bg-video data-video-kind="file"
                                    data-video-src="<?php echo e($clip); ?>"
                                    <?php echo $shot !== '' ? 'poster="' . e($shot) . '"' : ''; ?>
                                    muted loop playsinline preload="none" aria-hidden="true" tabindex="-1"></video>
                                <?php } ?>
                                <figcaption><?php echo e($caption); ?></figcaption>
                            </figure>
                        <?php } ?>
                    </div>
                    <button class="sg-arrow sg-prev" type="button" aria-label="Previous clip"><span aria-hidden="true">&#10094;</span></button>
                    <button class="sg-arrow sg-next" type="button" aria-label="Next clip"><span aria-hidden="true">&#10095;</span></button>
                    <div class="sg-dots">
                        <?php for ($gi = 0; $gi < $galleryCount; $gi++) {
                            $caption = isset($entry['gallery'][$gi]) ? $entry['gallery'][$gi] : 'Gallery clip ' . ($gi + 1);
                        ?>
                            <button class="sg-dot<?php echo $gi === 0 ? ' active' : ''; ?>" type="button" data-index="<?php echo (int) $gi; ?>"
                                aria-label="<?php echo e($caption); ?>"
                                aria-current="<?php echo $gi === 0 ? 'true' : 'false'; ?>"></button>
                        <?php } ?>
                    </div>
                    <p class="sg-count" aria-hidden="true"><b>1</b> / <?php echo (int) $galleryCount; ?></p>
                </div>
            </div>

            <div class="spot-panel">
                <h3>Most famous cosplay</h3>
                <div class="spot-cosplay">
                    <?php foreach ($entry['cosplay'] as $c) { ?>
                        <div class="spot-cos-card">
                            <span class="spot-cos-icon" aria-hidden="true">&#9679;</span>
                            <span><?php echo e($c); ?></span>
                        </div>
                    <?php } ?>
                </div>
            </div>

        </div>
    </section>
    <?php } ?>
<?php
}


function render_category_featured($cat, $copy, $featured)
{
    ?>
    <section class="wrap section" id="cat-featured">
        <div class="section-head">
            <h2><?php echo e($copy['spotlight']); ?></h2>
            <a href="#cat-titles">All <?php echo e(strtolower($cat['name'])); ?></a>
        </div>
        <?php if ($featured) { ?>
        <div class="grid grid-4">
            <?php foreach ($featured as $f) {
                card(array(
                    'href'   => site_url('/content.php?id=' . (int) $f['id']),
                    'image'  => $f['thumbnail'],
                    'category_slug' => $cat['slug'],
                    'title'  => $f['title'],
                    'desc'   => $f['body'],
                    'badge'  => $f['type'],
                    'meta'   => $f['genre'] . ' · ★ ' . (int) $f['popularity_score'],
                    'cta'    => 'Read more',
                ));
            } ?>
        </div>
        <?php } else { ?>
            <p class="meta">No spotlight picks in <?php echo e($cat['name']); ?> yet. Every published title is listed below.</p>
        <?php } ?>
    </section>
<?php
}


function render_category_titles($cat, $copy, $titles)
{
    ?>
    <section class="wrap section" id="cat-titles">
        <div class="section-head">
            <h2><?php echo e($copy['sections'][0]); ?></h2>
            <a href="<?php echo e(site_url('/explore.php?category=' . rawurlencode($cat['slug']) . '&sort=popular')); ?>">Sort and filter</a>
        </div>
        <?php if ($titles) { ?>
        <div class="grid grid-4">
            <?php foreach ($titles as $t) {
                card(array(
                    'href'  => site_url('/content.php?id=' . (int) $t['id']),
                    'image' => $t['thumbnail'],
                    'category_slug' => $cat['slug'],
                    'title' => $t['title'],
                    'desc'  => $t['body'],
                    'badge' => $t['type'],
                    'meta'  => $t['genre'] . ' · ' . (int) $t['release_year'] . ' · ★ ' . (int) $t['popularity_score'],
                    'cta'   => 'Read more',
                ));
            } ?>
        </div>
        <?php } else { ?>
            <p class="meta">No published titles in <?php echo e($cat['name']); ?> yet. The roadmap below is what lands first.</p>
        <?php } ?>
    </section>
<?php
}


function render_category_people($cat, $copy, $people)
{
    ?>
    <section class="wrap section" id="cat-people">
        <div class="section-head">
            <h2><?php echo e($copy['sections'][1]); ?></h2>
            <a href="<?php echo e(site_url('/characters.php')); ?>">All characters</a>
        </div>
        <?php if ($people) { ?>
        <div class="grid grid-4">
            <?php foreach ($people as $p) {
                card(array(
                    'href'   => site_url('/character.php?id=' . (int) $p['id']),
                    'image'  => $p['image'],
                    'category_slug' => $cat['slug'],
                    'title'  => $p['name'],
                    'desc'   => $p['bio'],
                    'badge'  => $p['role_type'],
                    'cta'    => 'View profile',
                ));
            } ?>
        </div>
        <?php } else { ?>
            <p class="meta">No character profiles filed here yet.</p>
        <?php } ?>
    </section>
<?php
}


function render_category_events($cat, $copy, $events)
{
    ?>
    <section class="wrap section" id="cat-events">
        <div class="section-head">
            <h2><?php echo e($copy['sections'][2]); ?></h2>
            <a href="<?php echo e(site_url('/events.php')); ?>">All events</a>
        </div>
        <?php if ($events) { ?>
        <div class="grid grid-4">
            <?php foreach ($events as $ev) {
                card(array(
                    'href'  => site_url('/event.php?id=' . (int) $ev['id']),
                    'image' => $ev['image'] !== '' ? $ev['image'] : category_banner_image($cat['slug']),
                    'category_slug' => $cat['slug'],
                    'title' => $ev['title'],
                    'desc'  => $ev['description'],
                    'badge' => $ev['city'],
                    'meta'  => $ev['event_date'],
                    'cta'   => 'Reserve',
                ));
            } ?>
        </div>
        <?php } else { ?>
            <p class="meta">No events scheduled for <?php echo e($cat['name']); ?> right now.</p>
        <?php } ?>
    </section>
<?php
}


function render_category_merch($cat, $copy, $merch)
{
    ?>
    <section class="wrap section" id="cat-merch">
        <div class="section-head">
            <h2><?php echo e($copy['sections'][3]); ?></h2>
            <a href="<?php echo e(site_url('/merch.php')); ?>">All merch</a>
        </div>
        <?php if ($merch) { ?>
        <div class="grid grid-4">
            <?php foreach ($merch as $m) {
                card(array(
                    'href'   => site_url('/merch.php?id=' . (int) $m['id']),
                    'image'  => $m['image'],
                    'category_slug' => $cat['slug'],
                    'title'  => $m['title'],
                    'desc'   => $m['description'],
                    'badge'  => $m['tag'],
                    'cta'    => 'Shop now',
                ));
            } ?>
        </div>
        <?php } else { ?>
            <p class="meta">No merch in <?php echo e($cat['name']); ?> yet.</p>
        <?php } ?>
    </section>
<?php
}


function render_category_others($cat, $others)
{
    $cid = (int) $cat['id'];
    ?>
    <section class="wrap section" id="cat-others">
        <div class="section-head">
            <h2>Other fandoms</h2>
            <a href="<?php echo e(site_url('/index.php')); ?>">Back home</a>
        </div>
        <div class="grid grid-4">
            <?php foreach ($others as $o) {
                if ((int) $o['id'] === $cid) { continue; }
            ?>
                <a class="cat-card <?php echo e($o['slug']); ?>" href="<?php echo e(category_url($o['slug'])); ?>" style="--category-image:url('<?php echo e(category_banner_image($o['slug'])); ?>')">
                    <h3><?php echo e($o['name']); ?></h3>
                    <p class="meta"><?php echo e($o['description']); ?></p>
                    <span class="category-link">Open page <span aria-hidden="true">&rarr;</span></span>
                </a>
            <?php } ?>
        </div>
    </section>
<?php
}


function render_category_soon($cat, $roadmap)
{
    $spotlights = spotlight_for($cat['slug']);
    $pool = array();
    foreach ($spotlights as $sp) {
        $imgs = spotlight_gallery_images($cat['slug'], $sp['key']);
        if (!empty($imgs[0])) {
            $pool[] = $imgs[0];
        }
    }
    ?>
    <section class="wrap section" id="cat-soon">
        <div class="section-head">
            <h2>Coming soon</h2>
            <a href="<?php echo e(site_url('/explore.php')); ?>">Keep exploring</a>
        </div>
        <p class="lede cat-soon-lede">Planned for <?php echo e($cat['name']); ?>. Nothing here is published yet, so treat it as a roadmap.</p>
        <div class="grid grid-4">
            <?php foreach ($roadmap as $idx => $r) {
                $road_img = (!empty($r[2])) ? $r[2] : (isset($pool[$idx]) ? $pool[$idx] : '');
                card(array(
                    'title'         => $r[0],
                    'desc'          => $r[1],
                    'image'         => $road_img,
                    'badge'         => 'Coming soon',
                    'category_slug' => $cat['slug'],
                    'cta'           => 'Coming soon',
                    'cta_soon'      => true,
                ));
            } ?>
        </div>
    </section>
    <?php
}

