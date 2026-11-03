<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = isset($_POST['id']) ? (int) $_POST['id'] : 0;
    $status = isset($_POST['status']) ? $_POST['status'] : '';
    if ($id > 0 && in_array($status, array('open', 'reviewed', 'closed'), true)) {
        db_query("UPDATE feedback SET status = '$status' WHERE id = $id");
        flash_set('ok', 'Feedback status updated.');
    }
    redirect('/admin/feedback.php');
}

$items = db_all("SELECT feedback.*, users.name, users.email FROM feedback LEFT JOIN users ON users.id = feedback.user_id ORDER BY feedback.created_at DESC");
$page_title = 'Feedback';
$active = 'feedback';
require __DIR__ . '/../includes/admin_header.php';
?>
<h2 style="margin-top:0">User feedback</h2>
<?php foreach ($items as $item) { ?>
    <article class="card card-body" style="margin-bottom:16px">
        <div class="section-head">
            <div>
                <span class="badge"><?php echo e(ucfirst($item['type'])); ?></span>
                <p class="meta"><?php echo e($item['name'] ? $item['name'] . ' · ' . $item['email'] : 'Visitor'); ?> · <?php echo e($item['created_at']); ?></p>
            </div>
            <form method="post" class="filters">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                <select name="status" aria-label="Feedback status">
                    <?php foreach (array('open' => 'Open', 'reviewed' => 'Reviewed', 'closed' => 'Closed') as $value => $label) { ?>
                        <option value="<?php echo e($value); ?>" <?php echo $item['status'] === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                    <?php } ?>
                </select>
                <button class="btn" type="submit">Update</button>
            </form>
        </div>
        <p><?php echo nl2br(e($item['message'])); ?></p>
    </article>
<?php } ?>
<?php if (!$items) { ?><p class="meta">No feedback has been submitted.</p><?php } ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>
