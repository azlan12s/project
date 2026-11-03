<?php
require_once __DIR__ . '/includes/functions.php';
db_query("UPDATE contents SET thumbnail = '/assets/images/spotlight/tv-shows/2throne.webp' WHERE title LIKE 'Binge Pacing%' AND (thumbnail = '' OR thumbnail IS NULL OR thumbnail NOT LIKE '/assets/images/spotlight/%')");

$q = isset($_GET['q']) ? trim($_GET['q']) : '';
$category = isset($_GET['category']) ? trim($_GET['category']) : '';
$sort = isset($_GET['sort']) ? $_GET['sort'] : 'popular';
$genre = isset($_GET['genre']) ? trim($_GET['genre']) : '';
$year = isset($_GET['year']) ? trim($_GET['year']) : '';
$type = isset($_GET['type']) ? trim($_GET['type']) : '';
$page = isset($_GET['page']) ? max(1, (int) $_GET['page']) : 1;
$per = 12;
$offset = ($page - 1) * $per;

function is_nonsense_search_query($str)
{
    $clean = strtolower(trim($str));
    if ($clean === '') {
        return false;
    }
    // Single character searches (like "a", "b", "x") or pure punctuation
    $alnum = preg_replace('/[^a-z0-9]/', '', $clean);
    if ($alnum === '' || strlen($alnum) < 2) {
        return true;
    }
    // Classic keyboard sequences / test gibberish like "abcd", "asdf", "qwerty", "zxcv", "1234"
    $gibberish_patterns = array(
        'abcd', 'bcde', 'cdef', 'asdf', 'sdfg', 'qwerty', 'werty', 'zxcv', 'xcvb',
        'qazwsx', '1234', '12345', '0000', '9999', 'xxxx', 'zzzz', 'aaaa', 'bbbb', 'cccc', 'dddd'
    );
    foreach ($gibberish_patterns as $pat) {
        if (strpos($alnum, $pat) !== false) {
            return true;
        }
    }
    // Repeated same character 3+ times in a row (e.g. "aaab", "ffff")
    if (preg_match('/(.)\1\1/', $alnum)) {
        return true;
    }
    // Words of 4+ letters with zero vowels (excluding known acronyms like "kpop", "jjk", "bts", "mha", "aot", "svt", "skz", "txt")
    $allowed_acronyms = array('kpop', 'jjk', 'bts', 'mha', 'aot', 'svt', 'skz', 'txt', 'ost', 'ova', 'rpg', 'fps', 'dc');
    if (strlen($alnum) >= 4 && !in_array($alnum, $allowed_acronyms, true) && !preg_match('/[aeiouy]/', $alnum)) {
        return true;
    }
    return false;
}

$is_invalid_q = ($q !== '' && is_nonsense_search_query($q));

$where = "contents.status = 'published'";
if ($category !== '') {
    $cat = category_by_slug($category);
    if ($cat) {
        $cid = (int) $cat['id'];
        $where .= " AND contents.category_id = $cid";
    }
}
if ($q !== '') {
    if ($is_invalid_q) {
        $where .= " AND 1=0";
    } else {
        $tokens = preg_split('/\s+/', strtolower(trim($q)), -1, PREG_SPLIT_NO_EMPTY);
        foreach ($tokens as $tok) {
            $tok_s = esc($tok);
            $body_word = "CONCAT(' ', REPLACE(REPLACE(REPLACE(REPLACE(LOWER(contents.body), '.', ' '), ',', ' '), ':', ' '), '-', ' '), ' ') LIKE '% " . $tok_s . " %'";
            $where .= " AND (LOWER(contents.title) LIKE '%$tok_s%' OR LOWER(contents.genre) LIKE '%$tok_s%' OR LOWER(categories.name) LIKE '%$tok_s%' OR (CHAR_LENGTH('$tok_s') >= 4 AND $body_word))";
        }
    }
}
if ($genre !== '') {
    $genre_sql = esc($genre);
    $where .= " AND contents.genre = '$genre_sql'";
}
if ($year !== '' && ctype_digit($year)) {
    $where .= ' AND contents.release_year = ' . (int) $year;
}
if (in_array($type, array('article', 'video', 'audio', 'explainer'), true)) {
    $where .= " AND contents.type = '" . esc($type) . "'";
}

$order = "contents.created_at DESC";
if ($sort === 'popular') {
    $order = "contents.popularity_score DESC, contents.views DESC";
}
if ($sort === 'az') {
    $order = "contents.title ASC";
}

$total_row = db_one("SELECT COUNT(*) AS c FROM contents JOIN categories ON categories.id = contents.category_id WHERE $where");
$total = (int) $total_row['c'];
$pages = max(1, (int) ceil($total / $per));
$items = db_all("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE $where ORDER BY $order LIMIT $per OFFSET $offset");

// Cross-module search on page 1 when a valid search query is entered (Characters, Events, Merch)
$extra_cards = array();
if ($q !== '' && !$is_invalid_q && $page === 1 && $genre === '' && $year === '' && $type === '') {
    $qs_low = esc(strtolower(trim($q)));
    $cat_filter_sql = '';
    if ($category !== '') {
        $cat_obj = category_by_slug($category);
        if ($cat_obj) {
            $cat_filter_sql = ' AND categories.id = ' . (int) $cat_obj['id'];
        }
    }
    $char_matches = db_all("SELECT character_profiles.*, categories.name AS category_name, categories.slug AS category_slug FROM character_profiles JOIN categories ON categories.id = character_profiles.category_id WHERE (LOWER(character_profiles.name) LIKE '%$qs_low%' OR LOWER(categories.name) LIKE '%$qs_low%') $cat_filter_sql ORDER BY character_profiles.name ASC LIMIT 4");
    foreach ($char_matches as $ch) {
        $extra_cards[] = array(
            'href'          => '/character.php?id=' . (int) $ch['id'],
            'image'         => $ch['image'],
            'category_slug' => $ch['category_slug'],
            'title'         => $ch['name'],
            'desc'          => $ch['bio'],
            'badge'         => $ch['category_name'] . ' · Character',
            'badge_href'    => '/characters.php?category=' . rawurlencode($ch['category_slug']),
            'meta'          => 'Character Profile · ' . $ch['role_type'],
            'cta'           => 'View profile',
        );
    }
    $event_matches = db_all("SELECT events.*, categories.name AS category_name, categories.slug AS category_slug FROM events JOIN categories ON categories.id = events.category_id WHERE events.event_date >= CURDATE() AND (LOWER(events.title) LIKE '%$qs_low%' OR LOWER(events.city) LIKE '%$qs_low%') $cat_filter_sql ORDER BY events.event_date ASC LIMIT 4");
    foreach ($event_matches as $ev) {
        $extra_cards[] = array(
            'href'          => '/event.php?id=' . (int) $ev['id'],
            'image'         => card_image_with_fallback($ev['image'], $ev['category_slug']),
            'category_slug' => $ev['category_slug'],
            'title'         => $ev['title'],
            'desc'          => $ev['description'],
            'badge'         => $ev['category_name'] . ' · Event',
            'badge_href'    => '/events.php',
            'meta'          => $ev['city'] . ' · ' . $ev['event_date'],
            'cta'           => 'Reserve',
        );
    }
    $merch_matches = db_all("SELECT merchandise_items.*, categories.name AS category_name, categories.slug AS category_slug FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id WHERE (LOWER(merchandise_items.title) LIKE '%$qs_low%') $cat_filter_sql ORDER BY merchandise_items.id DESC LIMIT 4");
    foreach ($merch_matches as $m) {
        $extra_cards[] = array(
            'href'          => '/merch.php?id=' . (int) $m['id'],
            'image'         => card_image_with_fallback($m['image'], $m['category_slug']),
            'category_slug' => $m['category_slug'],
            'title'         => $m['title'],
            'desc'          => $m['description'],
            'badge'         => $m['category_name'] . ' · Merch',
            'badge_href'    => '/merch.php?category=' . rawurlencode($m['category_slug']),
            'meta'          => 'Merchandise · ' . $m['tag'] . ' · ' . (int) $m['views'] . ' views',
            'cta'           => 'View merch',
        );
    }
}

$cats = categories();
$genres = db_all("SELECT DISTINCT genre FROM contents WHERE status = 'published' AND genre <> '' ORDER BY genre ASC");
$years = db_all("SELECT DISTINCT release_year FROM contents WHERE status = 'published' AND release_year IS NOT NULL ORDER BY release_year DESC");
$cat_header = $category !== '' ? category_by_slug($category) : null;

$page_title = $cat_header ? $cat_header['name'] : 'Explore';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <?php if ($cat_header) { ?>
        <p class="eyebrow">/explore/<?php echo e($cat_header['slug']); ?></p>
        <h1><?php echo e($cat_header['name']); ?></h1>
        <p class="lede"><?php echo e($cat_header['description']); ?></p>
    <?php } else { ?>
        <h1>Content explorer</h1>
        <p class="lede">Search and filter published fandom content.</p>
    <?php } ?>

    <form class="filters" method="get" id="exploreForm">
        <input type="search" name="q" value="<?php echo e($q); ?>" placeholder="Search titles and descriptions" aria-label="Search content" id="searchInput">
        <select name="category" aria-label="Category" id="categorySelect">
            <option value="">All categories</option>
            <?php foreach ($cats as $c) { ?>
                <option value="<?php echo e($c['slug']); ?>" <?php echo $category === $c['slug'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
            <?php } ?>
        </select>
        <select name="genre" aria-label="Genre" id="genreSelect">
            <option value="">All genres</option>
            <?php foreach ($genres as $g) { ?>
                <option value="<?php echo e($g['genre']); ?>" <?php echo $genre === $g['genre'] ? 'selected' : ''; ?>><?php echo e($g['genre']); ?></option>
            <?php } ?>
        </select>
        <select name="year" aria-label="Release year" id="yearSelect">
            <option value="">All years</option>
            <?php foreach ($years as $y) { ?>
                <option value="<?php echo (int) $y['release_year']; ?>" <?php echo (string) $year === (string) $y['release_year'] ? 'selected' : ''; ?>><?php echo (int) $y['release_year']; ?></option>
            <?php } ?>
        </select>
        <select name="type" aria-label="Content type" id="typeSelect">
            <option value="">All content types</option>
            <option value="article" <?php echo $type === 'article' ? 'selected' : ''; ?>>Articles</option>
            <option value="video" <?php echo $type === 'video' ? 'selected' : ''; ?>>Videos</option>
            <option value="audio" <?php echo $type === 'audio' ? 'selected' : ''; ?>>Audio</option>
            <option value="explainer" <?php echo $type === 'explainer' ? 'selected' : ''; ?>>Explainers</option>
        </select>
        <select name="sort" aria-label="Sort order" id="sortSelect">
            <option value="popular" <?php echo $sort === 'popular' ? 'selected' : ''; ?>>Most Popular</option>
            <option value="newest" <?php echo $sort === 'newest' ? 'selected' : ''; ?>>Newest First</option>
            <option value="az" <?php echo $sort === 'az' ? 'selected' : ''; ?>>Title (A–Z)</option>
        </select>
    </form>
    <script>
        // Debounced live search so full words (e.g. "abcd") are typed before submitting
        const form = document.getElementById('exploreForm');
        const searchInput = document.getElementById('searchInput');
        const selects = form.querySelectorAll('select');
        let searchTimer = null;
        if (searchInput) {
            if (new URLSearchParams(window.location.search).has('q')) {
                searchInput.focus();
                const len = searchInput.value.length;
                try { searchInput.setSelectionRange(len, len); } catch (e) {}
            }
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimer);
                searchTimer = setTimeout(() => { form.submit(); }, 600);
            });
        }
        selects.forEach(s => s.addEventListener('change', () => { form.submit(); }));
    </script>


    <div class="grid grid-4">
        <?php foreach ($items as $item) {
            $thumb = $item['thumbnail'];
            if (stripos($item['title'], 'Binge Pacing') !== false) {
                $thumb = '/assets/images/spotlight/tv-shows/2throne.webp';
            }
            card(array(
                'href'          => '/content.php?id=' . (int) $item['id'],
                'image'         => $thumb,
                'category_slug' => $item['category_slug'],
                'title'         => $item['title'],
                'desc'          => $item['body'],
                'badge'         => $item['category_name'],
                'badge_href'    => category_url($item['category_slug']),
                'meta'          => $item['type'] . ' · ' . $item['genre'] . ' · ' . (int) $item['release_year'] . ' · ★ ' . (int) $item['popularity_score'],
                'cta'           => 'Read more',
            ));
        }
        foreach ($extra_cards as $ec) {
            card($ec);
        } ?>
    </div>
    <?php if (!$items && !$extra_cards) { ?>
        <?php if ($q !== '') { ?>
            <p class="meta">Sorry, "<strong><?php echo e($q); ?></strong>" is not available on Fan Hub Plus. We only show results for titles, characters, events, and merchandise available on our website. <a href="<?php echo e(site_url('/explore.php')); ?>">Reset search</a></p>
        <?php } else { ?>
            <p class="meta">No matching titles yet.</p>
        <?php } ?>
    <?php } ?>
    <nav class="pager" aria-label="Pagination">
        <?php for ($i = 1; $i <= $pages; $i++) { ?>
            <a href="?category=<?php echo rawurlencode($category); ?>&amp;sort=<?php echo rawurlencode($sort); ?>&amp;q=<?php echo rawurlencode($q); ?>&amp;genre=<?php echo rawurlencode($genre); ?>&amp;year=<?php echo rawurlencode($year); ?>&amp;type=<?php echo rawurlencode($type); ?>&amp;page=<?php echo $i; ?>"><?php echo $i; ?></a>
        <?php } ?>
    </nav>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
