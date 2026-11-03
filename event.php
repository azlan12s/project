<?php
require_once __DIR__ . '/includes/functions.php';
ensure_srs_tables();

$id = isset($_GET['id']) ? (int) $_GET['id'] : 0;
$ev = db_one("SELECT events.*, categories.name AS category_name, categories.slug AS category_slug FROM events JOIN categories ON categories.id = events.category_id WHERE events.id = $id LIMIT 1");
if (!$ev) {
    redirect('/events.php');
}

// iCalendar (.ics) Export for Google / Outlook / Apple Calendar
if (isset($_GET['ical']) && $_GET['ical'] === '1') {
    $dt_start = str_replace('-', '', $ev['event_date']) . 'T180000Z';
    $dt_end   = str_replace('-', '', $ev['event_date']) . 'T210000Z';
    $dt_stamp = gmdate('Ymd\THis\Z');
    $uid_ics  = 'event-' . (int) $ev['id'] . '@fanhubplus.test';
    $summary  = preg_replace('/[\r\n]+/', ' ', $ev['title'] . ' (' . $ev['category_name'] . ')');
    $desc     = preg_replace('/[\r\n]+/', ' ', $ev['description'] . ($ev['ticket_url'] ? ' Tickets: ' . $ev['ticket_url'] : ''));
    $loc      = preg_replace('/[\r\n]+/', ' ', $ev['city']);

    header('Content-Type: text/calendar; charset=utf-8');
    header('Content-Disposition: attachment; filename="fanhub-event-' . (int) $ev['id'] . '.ics"');
    echo "BEGIN:VCALENDAR\r\n";
    echo "VERSION:2.0\r\n";
    echo "PRODID:-//Fan Hub Plus//TechWiz 7 Events//EN\r\n";
    echo "BEGIN:VEVENT\r\n";
    echo "UID:" . $uid_ics . "\r\n";
    echo "DTSTAMP:" . $dt_stamp . "\r\n";
    echo "DTSTART:" . $dt_start . "\r\n";
    echo "DTEND:" . $dt_end . "\r\n";
    echo "SUMMARY:" . $summary . "\r\n";
    echo "DESCRIPTION:" . $desc . "\r\n";
    echo "LOCATION:" . $loc . "\r\n";
    echo "GEO:" . $ev['latitude'] . ";" . $ev['longitude'] . "\r\n";
    echo "END:VEVENT\r\n";
    echo "END:VCALENDAR\r\n";
    exit;
}

$user = current_user();
if ($_SERVER['REQUEST_METHOD'] === 'POST' && $user && isset($_POST['rsvp_action'])) {
    csrf_check();
    $uid = (int) $user['id'];
    $act = $_POST['rsvp_action'];
    if ($act === 'cancel') {
        db_query("DELETE FROM event_rsvps WHERE event_id = $id AND user_id = $uid");
        flash_set('ok', 'Event RSVP removed.');
    } elseif ($act === 'going' || $act === 'interested') {
        $act_sql = esc($act);
        db_query("INSERT INTO event_rsvps (event_id, user_id, status, created_at) VALUES ($id, $uid, '$act_sql', NOW()) ON DUPLICATE KEY UPDATE status = '$act_sql', created_at = NOW()");
        log_activity($uid, 'event_rsvp', $ev['title'] . ' (' . $act . ')');
        flash_set('ok', 'RSVP saved as "' . ucfirst($act) . '"!');
    }
    redirect('/event.php?id=' . $id);
}

$rsvp_counts = event_rsvp_counts($id);
$my_rsvp = $user ? user_event_rsvp((int) $user['id'], $id) : null;

$page_title = $ev['title'];
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <div class="event-detail-image" style="background-image:url('<?php echo e(card_image_with_fallback($ev['image'], $ev['category_slug'])); ?>')"></div>
    <p class="chip"><?php echo e($ev['category_name']); ?></p>
    <h1><?php echo e($ev['title']); ?></h1>
    <p class="meta">
        <?php echo e($ev['city']); ?> · <?php echo e($ev['event_date']); ?> · <?php echo e($ev['latitude']); ?>, <?php echo e($ev['longitude']); ?>
        · <strong><?php echo (int) $rsvp_counts['going']; ?> Going</strong> · <strong><?php echo (int) $rsvp_counts['interested']; ?> Interested</strong>
    </p>
    <p><?php echo nl2br(e($ev['description'])); ?></p>
    <?php render_share_bar($ev['title']); ?>

    <div class="card card-body" style="margin: 18px 0; display:flex; flex-wrap:wrap; justify-content:space-between; align-items:center; gap:14px;">
        <div>
            <strong>Community Attendance &amp; Calendar Tools</strong>
            <p class="meta" style="margin:4px 0 0;">
                <?php if ($my_rsvp) { ?>
                    Your RSVP status: <span class="chip"><?php echo e(ucfirst($my_rsvp)); ?></span>
                <?php } else { ?>
                    Mark your attendance to track this event on your Dashboard and earn +15 Fan XP.
                <?php } ?>
            </p>
        </div>
        <div style="display:flex; flex-wrap:wrap; gap:10px; align-items:center;">
            <?php if ($user) { ?>
                <form method="post" style="display:flex; gap:8px; flex-wrap:wrap;">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <button class="<?php echo $my_rsvp === 'going' ? 'btn' : 'btn-ghost'; ?>" type="submit" name="rsvp_action" value="going">✓ I'm Going (<?php echo (int) $rsvp_counts['going']; ?>)</button>
                    <button class="<?php echo $my_rsvp === 'interested' ? 'btn' : 'btn-ghost'; ?>" type="submit" name="rsvp_action" value="interested">★ Interested (<?php echo (int) $rsvp_counts['interested']; ?>)</button>
                    <?php if ($my_rsvp) { ?>
                        <button class="btn-danger" type="submit" name="rsvp_action" value="cancel">Cancel RSVP</button>
                    <?php } ?>
                </form>
            <?php } else { ?>
                <a class="btn-ghost" href="<?php echo e(site_url('/login.php?next=' . rawurlencode('/event.php?id=' . $id))); ?>">Log in to RSVP</a>
            <?php } ?>
            <a class="btn-ghost" href="<?php echo e(site_url('/event.php?id=' . $id . '&ical=1')); ?>">Download .ics Calendar</a>
            <?php if ($ev['ticket_url'] !== '') { ?>
                <a class="btn" href="<?php echo e($ev['ticket_url']); ?>" rel="noopener" target="_blank">External tickets</a>
            <?php } ?>
        </div>
    </div>

    <div id="map"></div>
</section>
<?php
$extra_js = '<script>var map=L.map("map").setView([' . $ev['latitude'] . ',' . $ev['longitude'] . '], 11);L.tileLayer("https://{s}.tile.openstreetmap.org/{z}/{x}/{y}.png",{attribution:"© OpenStreetMap"}).addTo(map);L.marker([' . $ev['latitude'] . ',' . $ev['longitude'] . ']).addTo(map).bindPopup(' . json_encode($ev['title']) . ');</script>';
require __DIR__ . '/includes/footer.php';
?>
