<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $id = (int) $_POST['id'];
    $action = isset($_POST['action']) ? $_POST['action'] : '';
    if ($action === 'approve') {
        db_query("UPDATE contents SET status = 'published' WHERE id = $id");
        flash_set('ok', 'Article published.');
    } elseif ($action === 'reject') {
        db_query("UPDATE contents SET status = 'rejected' WHERE id = $id");
        flash_set('ok', 'Article rejected.');
    }
    redirect('/admin/moderation.php');
}

$pending = db_all("SELECT contents.*, users.name AS author, categories.name AS category_name FROM contents LEFT JOIN users ON users.id = contents.user_id JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'pending' ORDER BY contents.created_at ASC");
$page_title = 'Moderation';
$active = 'moderation';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h2 style="margin-top:0">Moderation queue</h2>
        <p class="lede">User submissions stay pending until you publish or reject them.</p>
        <?php foreach ($pending as $item) { ?>
            <article class="card card-body" style="margin-bottom:16px">
                <h3><?php echo e($item['title']); ?></h3>
                <p class="meta"><?php echo e($item['author']); ?> · <?php echo e($item['category_name']); ?> · <?php echo e($item['created_at']); ?></p>
                <p><?php echo nl2br(e($item['body'])); ?></p>
                <form method="post" style="display:flex;gap:8px">
                    <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                    <input type="hidden" name="id" value="<?php echo (int) $item['id']; ?>">
                    <button class="btn" name="action" value="approve">Publish</button>
                    <button class="btn-danger" name="action" value="reject">Reject</button>
                </form>
            </article>
        <?php } ?>
        <?php if (!$pending) { ?><p class="meta">Queue is empty.</p><?php } ?>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>

