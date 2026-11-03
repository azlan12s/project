<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
ensure_srs_tables();

$cats = categories();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['create_poll'])) {
        $cid = (int) $_POST['category_id'];
        $question = esc(trim($_POST['question']));
        $o1 = esc(trim($_POST['option_1']));
        $o2 = esc(trim($_POST['option_2']));
        $o3 = esc(trim($_POST['option_3']));
        $o4 = esc(trim($_POST['option_4']));
        if ($question !== '' && $o1 !== '' && $o2 !== '' && $cid > 0) {
            db_query("INSERT INTO fandom_polls (category_id, question, option_1, option_2, option_3, option_4, status, created_at) VALUES ($cid, '$question', '$o1', '$o2', '$o3', '$o4', 'open', NOW())");
            flash_set('ok', 'New fandom poll published.');
        } else {
            flash_set('error', 'Question and at least two options are required.');
        }
        redirect('/admin/polls.php');
    }
    if (isset($_POST['toggle_id'])) {
        $pid = (int) $_POST['toggle_id'];
        $new_status = $_POST['new_status'] === 'closed' ? 'closed' : 'open';
        db_query("UPDATE fandom_polls SET status = '$new_status' WHERE id = $pid");
        flash_set('ok', 'Poll status updated.');
        redirect('/admin/polls.php');
    }
    if (isset($_POST['delete_id'])) {
        $did = (int) $_POST['delete_id'];
        db_query("DELETE FROM fandom_poll_votes WHERE poll_id = $did");
        db_query("DELETE FROM fandom_polls WHERE id = $did");
        flash_set('ok', 'Poll deleted.');
        redirect('/admin/polls.php');
    }
}

$polls = db_all("SELECT fandom_polls.*, categories.name AS category_name, (SELECT COUNT(*) FROM fandom_poll_votes WHERE fandom_poll_votes.poll_id = fandom_polls.id) AS total_votes FROM fandom_polls JOIN categories ON categories.id = fandom_polls.category_id ORDER BY fandom_polls.id DESC");

$page_title = 'Fandom Polls';
$active = 'polls';
require __DIR__ . '/../includes/admin_header.php';
?>
<h2 style="margin-top:0">Community Polls &amp; Versus Battles</h2>
<form class="card card-body" method="post" style="margin-bottom: 24px;">
    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
    <input type="hidden" name="create_poll" value="1">
    <h3 style="margin-top:0">Create new poll</h3>
    <div class="form-row">
        <label>Fandom Category</label>
        <select name="category_id" required>
            <?php foreach ($cats as $c) { ?>
                <option value="<?php echo (int) $c['id']; ?>"><?php echo e($c['name']); ?></option>
            <?php } ?>
        </select>
    </div>
    <div class="form-row"><label>Poll Question</label><input name="question" placeholder="e.g., Which upcoming anime season are you most hyped for?" required></div>
    <div class="form-row"><label>Option 1</label><input name="option_1" required></div>
    <div class="form-row"><label>Option 2</label><input name="option_2" required></div>
    <div class="form-row"><label>Option 3 (Optional)</label><input name="option_3"></div>
    <div class="form-row"><label>Option 4 (Optional)</label><input name="option_4"></div>
    <button class="btn" type="submit">Publish Poll</button>
</form>

<table class="table">
    <thead>
        <tr>
            <th>Question</th>
            <th>Category</th>
            <th>Votes</th>
            <th>Status</th>
            <th>Actions</th>
        </tr>
    </thead>
    <tbody>
        <?php foreach ($polls as $p) { ?>
        <tr>
            <td><strong><?php echo e($p['question']); ?></strong></td>
            <td><?php echo e($p['category_name']); ?></td>
            <td><?php echo (int) $p['total_votes']; ?></td>
            <td><span class="chip"><?php echo e($p['status']); ?></span></td>
            <td style="display:flex; gap:8px; flex-wrap:wrap;">
                <form method="post" style="display:inline;">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="toggle_id" value="<?php echo (int) $p['id']; ?>">
                    <input type="hidden" name="new_status" value="<?php echo $p['status'] === 'open' ? 'closed' : 'open'; ?>">
                    <button class="btn-ghost" type="submit"><?php echo $p['status'] === 'open' ? 'Close' : 'Reopen'; ?></button>
                </form>
                <form method="post" style="display:inline;" onsubmit="return confirm('Delete this poll?');">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="delete_id" value="<?php echo (int) $p['id']; ?>">
                    <button class="btn-danger" type="submit">Delete</button>
                </form>
            </td>
        </tr>
        <?php } ?>
    </tbody>
</table>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
