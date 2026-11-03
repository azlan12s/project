<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
ensure_srs_tables();

$counts = array(
    'users'        => db_one("SELECT COUNT(*) AS c FROM users"),
    'contents'     => db_one("SELECT COUNT(*) AS c FROM contents"),
    'views'        => db_one("SELECT COALESCE(SUM(views),0) AS c FROM contents"),
    'bookmarks'    => db_one("SELECT (SELECT COUNT(*) FROM bookmarks) + (SELECT COUNT(*) FROM item_bookmarks) AS c"),
    'reviews'      => db_one("SELECT COUNT(*) AS c FROM media_ratings"),
    'events'       => db_one("SELECT COUNT(*) AS c FROM events"),
    'pending'      => db_one("SELECT COUNT(*) AS c FROM contents WHERE status = 'pending'"),
    'active_users' => db_one("SELECT COUNT(DISTINCT user_id) AS c FROM activity_logs WHERE user_id IS NOT NULL AND created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)"),
    'feedback'     => db_one("SELECT COUNT(*) AS c FROM feedback WHERE status = 'open'"),
    'chatbot'      => db_one("SELECT COUNT(*) AS c FROM chatbot_queries"),
    'poll_votes'   => db_one("SELECT COUNT(*) AS c FROM fandom_poll_votes"),
    'quiz_runs'    => db_one("SELECT COUNT(*) AS c FROM quiz_attempts"),
    'event_rsvps'  => db_one("SELECT COUNT(*) AS c FROM event_rsvps"),
);

$by_cat = db_all("SELECT categories.name, COUNT(contents.id) AS total, COALESCE(SUM(contents.views), 0) AS total_views FROM categories LEFT JOIN contents ON contents.category_id = categories.id AND contents.status = 'published' GROUP BY categories.id, categories.name ORDER BY total DESC");

if (isset($_GET['export']) && $_GET['export'] === 'analytics_csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="fanhub-admin-analytics-' . date('Ymd-His') . '.csv"');
    $out = fopen('php://output', 'w');
    fputcsv($out, array('Metric', 'Value'));
    foreach ($counts as $k => $v) {
        fputcsv($out, array(ucwords(str_replace('_', ' ', $k)), (int) $v['c']));
    }
    fputcsv($out, array('', ''));
    fputcsv($out, array('Category', 'Published Items', 'Total Views'));
    foreach ($by_cat as $row) {
        fputcsv($out, array($row['name'], (int) $row['total'], (int) $row['total_views']));
    }
    fclose($out);
    exit;
}

$recent_logs = db_all("SELECT activity_logs.*, users.name AS user_name FROM activity_logs LEFT JOIN users ON users.id = activity_logs.user_id ORDER BY activity_logs.created_at DESC LIMIT 12");

$page_title = 'Admin';
$active = 'dashboard';
require __DIR__ . '/../includes/admin_header.php';
?>
<div class="section-head" style="margin-top:0">
    <div>
        <h2 style="margin-top:0">Administrator desk</h2>
        <p class="lede">Every admin URL checks role on the server. Hiding a link in the sidebar is not enough.</p>
    </div>
    <a class="btn" href="<?php echo e(site_url('/admin/index.php?export=analytics_csv')); ?>">Export Analytics (CSV)</a>
</div>
<div class="grid grid-4">
    <div class="stat card card-body"><b><?php echo (int) $counts['users']['c']; ?></b> Users</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['contents']['c']; ?></b> Content</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['pending']['c']; ?></b> Pending articles</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['events']['c']; ?></b> Events</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['active_users']['c']; ?></b> Active users (30 days)</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['feedback']['c']; ?></b> Open feedback</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['views']['c']; ?></b> Content views</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['bookmarks']['c']; ?></b> Total Bookmarks</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['reviews']['c']; ?></b> Reviews</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['chatbot']['c']; ?></b> Chatbot interactions</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['poll_votes']['c']; ?></b> Poll votes</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['quiz_runs']['c']; ?></b> Quiz attempts</div>
    <div class="stat card card-body"><b><?php echo (int) $counts['event_rsvps']['c']; ?></b> Event RSVPs</div>
</div>
<div class="card card-body" style="margin-top:20px">
    <h3 style="margin-top:0">Content &amp; engagement by category</h3>
    <?php foreach ($by_cat as $row) { ?>
        <p><?php echo e($row['name']); ?> — <strong><?php echo (int) $row['total']; ?></strong> published items · <span class="meta"><?php echo (int) $row['total_views']; ?> views</span></p>
    <?php } ?>
</div>

<div class="card card-body" style="margin-top:20px">
    <h3 style="margin-top:0">Recent Platform Activity Audit Log</h3>
    <table class="table">
        <thead>
            <tr>
                <th>Time</th>
                <th>User</th>
                <th>Action</th>
                <th>Target / Detail</th>
            </tr>
        </thead>
        <tbody>
            <?php foreach ($recent_logs as $log) { ?>
            <tr>
                <td class="meta"><?php echo e($log['created_at']); ?></td>
                <td><?php echo e($log['user_name'] ? $log['user_name'] : 'User #' . (int) $log['user_id']); ?></td>
                <td><span class="chip"><?php echo e($log['action']); ?></span></td>
                <td><?php echo e($log['detail']); ?></td>
            </tr>
            <?php } ?>
            <?php if (!$recent_logs) { ?>
            <tr><td colspan="4" class="meta">No activity logged yet.</td></tr>
            <?php } ?>
        </tbody>
    </table>
</div>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
