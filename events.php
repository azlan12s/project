<?php
require_once __DIR__ . '/includes/functions.php';
ensure_upcoming_events();
$city = isset($_GET['city']) ? trim($_GET['city']) : '';
$date = isset($_GET['date']) ? trim($_GET['date']) : '';
$where = 'events.event_date >= CURDATE()';
if ($city !== '') {
    $cs = esc($city);
    $where .= " AND events.city = '$cs'";
}
if ($date !== '') {
    $ds = esc($date);
    $where .= " AND events.event_date = '$ds'";
}
$events = db_all("SELECT events.*, categories.name AS category_name, categories.slug AS category_slug FROM events JOIN categories ON categories.id = events.category_id WHERE $where ORDER BY events.event_date ASC");
$cities = db_all("SELECT DISTINCT city FROM events ORDER BY city ASC");
$page_title = 'Events';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <div class="section-head">
        <div>
            <h1>Events</h1>
            <p class="lede">Browse upcoming conventions, screenings, and fan meetups by city and date.</p>
        </div>
        <a class="events-map-btn" href="<?php echo e(site_url('/events-map.php')); ?>">
            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <path d="M21 10c0 7-9 13-9 13s-9-6-9-13a9 9 0 0 1 18 0z"></path>
                <circle cx="12" cy="10" r="3"></circle>
            </svg>
            <span>Explore Events Map</span>
        </a>
    </div>
    <form class="filters" method="get">
        <select name="city">
            <option value="">All cities</option>
            <?php foreach ($cities as $c) { ?>
                <option <?php echo $city === $c['city'] ? 'selected' : ''; ?>><?php echo e($c['city']); ?></option>
            <?php } ?>
        </select>
        <input type="date" name="date" value="<?php echo e($date); ?>">
        <button class="btn" type="submit">Search Events</button>
    </form>
    <div class="grid grid-4">
        <?php foreach ($events as $ev) {
            card(array(
                'href'  => '/event.php?id=' . (int) $ev['id'],
                'image' => card_image_with_fallback($ev['image'], $ev['category_slug']),
                'title' => $ev['title'],
                'desc'  => $ev['description'],
                'badge' => $ev['category_name'],
                'meta'  => $ev['city'] . ' · ' . $ev['event_date'],
                'cta'   => 'Reserve',
            ));
        } ?>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
