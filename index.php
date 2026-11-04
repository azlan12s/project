<?php
require_once __DIR__ . '/includes/functions.php';

$page_title = 'Home';
$cats = categories();
$featured = db_all("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' AND contents.featured = 1 ORDER BY contents.popularity_score DESC LIMIT 6");
$popular = db_all("SELECT contents.*, categories.name AS category_name FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' ORDER BY contents.popularity_score DESC, contents.views DESC LIMIT 8");
$characters = db_all("SELECT character_profiles.*, categories.name AS category_name FROM character_profiles JOIN categories ON categories.id = character_profiles.category_id ORDER BY character_profiles.id DESC LIMIT 4");
$articles = db_all("SELECT contents.*, categories.name AS category_name FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' AND contents.type = 'article' ORDER BY contents.created_at DESC LIMIT 4");
$merch = db_all("SELECT merchandise_items.*, categories.name AS category_name FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id ORDER BY merchandise_items.created_at DESC, merchandise_items.id DESC LIMIT 8");
ensure_upcoming_events();
$events = db_all("SELECT events.*, categories.name AS category_name, categories.slug AS category_slug FROM events JOIN categories ON categories.id = events.category_id WHERE events.event_date >= CURDATE() ORDER BY events.event_date ASC LIMIT 8");
$videos = db_all("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' AND contents.type = 'video' AND contents.media_url <> '' ORDER BY contents.popularity_score DESC, contents.views DESC LIMIT 6");
$stats = array(
    'users' => db_one("SELECT COUNT(*) AS c FROM users"),
    'contents' => db_one("SELECT COUNT(*) AS c FROM contents WHERE status = 'published'"),
    'events' => db_one("SELECT COUNT(*) AS c FROM events"),
);

require __DIR__ . '/includes/header.php';

$slides = hero_slides();
?>
<section class="hero" aria-label="Fan Hub Plus">
    <div class="hero-band">
        <div class="hero-watermark" aria-hidden="true">FANDOM</div>
        <div class="wrap hero-content">
            <p class="eyebrow">Eight fandoms. One hub.</p>
            <h1 class="hero-title">Discover stories, characters &amp; merch built for your fandom.</h1>
            <p class="lede">Fan Hub Plus is a visitor-friendly explorer with registered-user tools and a protected admin desk. Search, filter, bookmark and rate across every fandom.</p>
            <div class="hero-actions">
                <a class="btn" href="<?php echo e(site_url('/explore.php')); ?>">Explore fandoms</a>
                <a class="btn-ghost" href="<?php echo e(site_url('/merch.php')); ?>">Shop merchandise</a>
            </div>
            <form class="search-bar" action="<?php echo e(site_url('/explore.php')); ?>" method="get" role="search">
                <label class="visually-hidden" for="q">Search content</label>
                <input id="q" name="q" type="search" placeholder="Search titles, genres, years...">
                <button class="btn" type="submit">Search</button>
            </form>
            <div class="hero-stats">
                <div><b><?php echo (int) $stats['users']['c']; ?>+</b><span>Members</span></div>
                <div><b><?php echo (int) $stats['contents']['c']; ?>+</b><span>Published</span></div>
                <div><b><?php echo (int) $stats['events']['c']; ?>+</b><span>Live events</span></div>
            </div>
        </div>
        <a class="hero-scroll-cue" href="#main-sections" aria-label="Explore site">Explore site <span aria-hidden="true">&#8595;</span></a>
    </div>
</section>
<div id="main-sections"></div>

<?php if ($videos) { ?>
<section class="wrap section">
    <div class="section-head"><h2>Video spotlight</h2><a href="<?php echo e(site_url('/multimedia.php')); ?>">All multimedia</a></div>
    <div class="video-carousel" id="vc" data-autoplay="6000">
        <div class="vc-track-wrap">
            <div class="vc-track" id="vcTrack">
                <?php foreach ($videos as $i => $v) {
                    $video_poster = card_image_with_fallback($v['thumbnail'], $v['category_slug']);
                ?>
                <div class="vc-slide">
                    <a class="vc-media" href="<?php echo e(site_url('/content.php?id=' . (int) $v['id'])); ?>" style="background-image:url('<?php echo e($video_poster); ?>')" aria-label="Open video: <?php echo e($v['title']); ?>"></a>
                    <div class="vc-caption">
                        <div>
                            <span class="chip"><?php echo e($v['category_name']); ?></span>
                            <h3><a href="<?php echo e(site_url('/content.php?id=' . (int) $v['id'])); ?>"><?php echo e($v['title']); ?></a></h3>
                        </div>
                        <a class="vc-details-link" href="<?php echo e(site_url('/content.php?id=' . (int) $v['id'])); ?>">Details <span aria-hidden="true">&#8594;</span></a>
                    </div>
                </div>
                <?php } ?>
            </div>
            <?php if (count($videos) > 1) { ?>
            <button type="button" class="vc-arrow vc-prev" aria-label="Previous video">&#10094;</button>
            <button type="button" class="vc-arrow vc-next" aria-label="Next video">&#10095;</button>
            <?php } ?>
        </div>
        <?php if (count($videos) > 1) { ?>
        <div class="vc-dots" id="vcDots">
            <?php foreach ($videos as $i => $v) { ?>
                <button type="button" class="vc-dot<?php echo $i === 0 ? ' active' : ''; ?>" data-index="<?php echo (int) $i; ?>" aria-label="Go to video <?php echo (int) $i + 1; ?>"></button>
            <?php } ?>
        </div>
        <?php } ?>
    </div>
</section>
<?php } ?>

<section class="wrap section">
    <div class="section-head"><h2>Fandom categories</h2></div>
    <div class="grid grid-4">
        <?php foreach ($cats as $c) { ?>
            <a class="cat-card <?php echo e($c['slug']); ?>" href="<?php echo e(category_url($c['slug'])); ?>" style="--category-image:url('<?php echo e(category_banner_image($c['slug'])); ?>')">
                <h3><?php echo e($c['name']); ?></h3>
                <p class="meta"><?php echo e($c['description']); ?></p>
                <span class="category-link">Open <?php echo e($c['name']); ?> <span aria-hidden="true">&#8594;</span></span>
            </a>
        <?php } ?>
    </div>
</section>


<section class="fan-slider-section" aria-label="Featured fandoms">
    <div class="wrap section-head">
        <h2>Spotlight fandoms</h2>
        <a href="<?php echo e(site_url('/explore.php')); ?>">Explore all</a>
    </div>
    <div class="fan-slider" id="heroSlider" data-autoplay="5500" aria-roledescription="carousel" aria-label="Featured fandoms">
        <div class="hs-track">
            <?php foreach ($slides as $i => $s) {
                $art = hero_slide_image(isset($s['image']) ? $s['image'] : '');
                $art_bg = $art !== '' ? "url('" . e($art) . "')" : hero_slide_gradient($i + 1);
                $youtube_id = '';
                if (preg_match('~^(?:https?://)?(?:www\.)?(?:youtu\.be/|youtube\.com/(?:watch\?v=|embed/|shorts/))([A-Za-z0-9_-]{11})~i', $s['video'], $match)) {
                    $youtube_id = $match[1];
                }
            ?>
            <div class="hs-slide<?php echo $i === 0 ? ' is-active' : ''; ?>" role="group" aria-roledescription="slide" aria-label="Slide <?php echo (int) ($i + 1); ?> of <?php echo count($slides); ?>">
                <div class="hs-media" style="background-image:<?php echo $art_bg; ?>">
                    <?php if ($youtube_id !== '') { ?>
                    <iframe class="hs-video" data-bg-video data-video-kind="youtube" data-video-src="https://www.youtube-nocookie.com/embed/<?php echo e($youtube_id); ?>?autoplay=1&amp;mute=1&amp;controls=0&amp;loop=1&amp;playlist=<?php echo e($youtube_id); ?>&amp;playsinline=1&amp;rel=0" title="<?php echo e($s['title']); ?> background video" allow="autoplay; encrypted-media; picture-in-picture" referrerpolicy="strict-origin-when-cross-origin" aria-hidden="true" tabindex="-1"></iframe>
                    <?php } elseif (!empty($s['video'])) { ?>
                    <video class="hs-video" data-bg-video data-video-kind="file" data-video-src="<?php echo e(site_url('/assets/videos/' . rawurlencode($s['video']))); ?>" poster="<?php echo e($art); ?>" muted loop playsinline preload="none" aria-hidden="true" tabindex="-1"></video>
                    <?php } ?>
                </div>
                <div class="hs-scrim" aria-hidden="true"></div>
                <div class="wrap hs-content">
                    <p class="hs-kicker"><?php echo e($s['kicker']); ?></p>
                    <h2 class="hs-title"><?php echo e($s['title']); ?></h2>
                    <p class="hs-line"><?php echo e($s['line']); ?></p>
                    <div class="hs-actions">
                        <a class="btn" href="<?php echo e(category_url($s['slug'])); ?>"><?php echo e($s['cta']); ?></a>
                        <a class="btn-ghost" href="<?php echo e(site_url('/explore.php')); ?>">Browse all</a>
                    </div>
                </div>
            </div>
            <?php } ?>
        </div>
        <?php if (count($slides) > 1) { ?>
        <button class="hs-arrow hs-prev" type="button" aria-label="Previous slide"><span aria-hidden="true">&#10094;</span></button>
        <button class="hs-arrow hs-next" type="button" aria-label="Next slide"><span aria-hidden="true">&#10095;</span></button>
        <div class="hs-dots">
            <?php foreach ($slides as $i => $s) { ?>
            <button class="hs-dot<?php echo $i === 0 ? ' active' : ''; ?>" type="button" data-index="<?php echo (int) $i; ?>" aria-label="Go to slide <?php echo (int) ($i + 1); ?>" aria-current="<?php echo $i === 0 ? 'true' : 'false'; ?>"></button>
            <?php } ?>
        </div>
        <p class="hs-count" aria-hidden="true"><b><?php echo count($slides) > 0 ? 1 : 0; ?></b> / <?php echo count($slides); ?></p>
        <?php } ?>
    </div>
</section>

<section class="wrap section">
    <div class="section-head"><h2>Featured &amp; popular</h2><a href="<?php echo e(site_url('/explore.php?sort=popular')); ?>">See all</a></div>
    <div class="grid grid-4">
        <?php foreach ($featured as $item) {
            card(array(
                'href'   => site_url('/content.php?id=' . (int) $item['id']),
                'image'  => $item['thumbnail'],
                'title'  => $item['title'],
                'desc'   => $item['body'],
                'badge'  => $item['category_name'],
                'meta'   => $item['type'] . ' · ★ ' . (int) $item['popularity_score'],
                'cta'    => 'Read more',
            ));
        } ?>
    </div>
</section>

<section class="wrap section">
    <div class="section-head"><h2>Characters</h2><a href="<?php echo e(site_url('/characters.php')); ?>">Profiles</a></div>
    <div class="grid grid-4">
        <?php foreach ($characters as $ch) {
            card(array(
                'href'   => site_url('/character.php?id=' . (int) $ch['id']),
                'image'  => $ch['image'],
                'title'  => $ch['name'],
                'desc'   => $ch['bio'],
                'badge'  => $ch['category_name'],
                'meta'   => $ch['role_type'],
                'cta'    => 'View profile',
            ));
        } ?>
    </div>
</section>

<section class="wrap section">
    <div class="section-head"><h2>Latest articles</h2><a href="<?php echo e(site_url('/articles.php')); ?>">All articles</a></div>
    <div class="grid grid-4">
        <?php foreach ($articles as $item) {
            card(array(
                'href'  => site_url('/content.php?id=' . (int) $item['id']),
                'image' => $item['thumbnail'],
                'title' => $item['title'],
                'desc'  => $item['body'],
                'badge' => $item['category_name'],
                'meta'  => $item['genre'] . ' · ' . (int) $item['release_year'],
                'cta'   => 'Read more',
            ));
        } ?>
    </div>
</section>

<section class="wrap section">
    <div class="section-head"><h2>Merchandise showcase</h2><a href="<?php echo e(site_url('/merch.php')); ?>">Browse merch</a></div>
    <div class="grid grid-4">
        <?php foreach ($merch as $i => $m) {
            card(array(
                'href'        => site_url('/merch.php?id=' . (int) $m['id']),
                'image'       => $m['image'],
                'title'       => $m['title'],
                'desc'        => $m['description'],
                'badge'       => $m['tag'],
                'meta'        => $m['category_name'] . ' · ' . (int) $m['views'] . ' views',
                'cta'         => 'Shop now',
                'cover_class' => $i === 3 ? 'merchandise-cover' : '',
            ));
        } ?>
    </div>
</section>

<section class="wrap section">
    <div class="section-head"><h2>Upcoming events</h2><a href="<?php echo e(site_url('/events-map.php')); ?>">Open map</a></div>
    <div class="grid grid-4">
        <?php foreach ($events as $ev) {
            card(array(
                'href'  => site_url('/event.php?id=' . (int) $ev['id']),
                'image' => card_image_with_fallback($ev['image'], $ev['category_slug']),
                'title' => $ev['title'],
                'desc'  => $ev['description'],
                'badge' => $ev['city'],
                'meta'  => $ev['event_date'] . ' · ' . $ev['category_name'],
                'cta'   => 'Reserve',
            ));
        } ?>
    </div>
</section>

<section class="wrap section">
    <div class="section-head">
        <h2>Interactive Community Arena</h2>
        <a href="<?php echo e(site_url('/quiz.php')); ?>">Open Arena</a>
    </div>
    <div class="grid grid-4">
        <?php
        card(array(
            'href'          => '/quiz.php',
            'image'         => '/assets/images/fandoms/anime.jfif',
            'category_slug' => 'anime',
            'title'         => 'Fandom Trivia & XP Arena',
            'desc'          => 'Test your lore knowledge across all 8 fandoms, unlock instant explanations, and earn Fan XP badges.',
            'badge'         => 'Trivia Challenge',
            'meta'          => '16 Lore Questions · XP Leaderboard',
            'cta'           => 'Play Trivia',
        ));
        card(array(
            'href'          => '/polls.php',
            'image'         => '/assets/images/kpop.jpg',
            'category_slug' => 'k-pop',
            'title'         => 'Community Polls & Versus Battles',
            'desc'          => 'Vote on the biggest debates in Anime, Gaming, Movies, K-Pop, Comics, and Cosplay with live results.',
            'badge'         => 'Live Voting',
            'meta'          => 'Real-Time Standings · +10 XP',
            'cta'           => 'Vote Now',
        ));
        card(array(
            'href'          => '/events-map.php',
            'image'         => '/assets/images/g1.jfif',
            'category_slug' => 'gaming',
            'title'         => 'Geolocation Events & .ics Calendar',
            'desc'          => 'Find fandom conventions near you on our interactive map, RSVP your attendance, and download calendar invites.',
            'badge'         => 'Events & RSVP',
            'meta'          => 'Leaflet Map · iCal Export',
            'cta'           => 'Explore Map',
        ));
        card(array(
            'href'          => '/submit-article.php',
            'image'         => '/assets/images/fandoms/comics.jfif',
            'category_slug' => 'comics',
            'title'         => 'Community Essay & Theory Publishing',
            'desc'          => 'Write and submit your own fandom articles, character studies, and reviews to earn +30 Fan XP.',
            'badge'         => 'Creator Hub',
            'meta'          => 'Moderated Publishing · +30 XP',
            'cta'           => 'Submit Article',
        ));
        ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

