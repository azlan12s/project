<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$cats = categories();
$edit = null;
if (isset($_GET['id'])) {
    $eid = (int) $_GET['id'];
    $edit = db_one("SELECT * FROM merchandise_items WHERE id = $eid LIMIT 1");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete_id'])) {
        $did = (int) $_POST['delete_id'];
        db_query("DELETE FROM merchandise_items WHERE id = $did");
        flash_set('ok', 'Item deleted.');
        redirect('/admin/merch.php');
    }
    $title = esc(trim($_POST['title']));
    $category_id = (int) $_POST['category_id'];
    $image = esc(trim($_POST['image']));
    $up = upload_file('image_file', 'merch');
    if ($up !== '') {
        $image = esc($up);
    }
    $tag = esc(trim($_POST['tag']));
    $description = esc(trim($_POST['description']));
    if (isset($_POST['id']) && (int) $_POST['id'] > 0) {
        $id = (int) $_POST['id'];
        db_query("UPDATE merchandise_items SET category_id = $category_id, title = '$title', image = '$image', tag = '$tag', description = '$description' WHERE id = $id");
        flash_set('ok', 'Merchandise updated.');
    } else {
        db_query("INSERT INTO merchandise_items (category_id, title, image, tag, views, description, created_at) VALUES ($category_id, '$title', '$image', '$tag', 0, '$description', NOW())");
        flash_set('ok', 'Merchandise added.');
    }
    redirect('/admin/merch.php');
}

$rows = db_all("SELECT merchandise_items.*, categories.name AS category_name FROM merchandise_items JOIN categories ON categories.id = merchandise_items.category_id ORDER BY merchandise_items.id DESC");
$page_title = 'Merch admin';
$active = 'merch';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h2 style="margin-top:0">Merchandise CRUD</h2>
        <p class="meta">Showcase only. No checkout fields.</p>
        <form class="card card-body" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">
            <div class="form-row"><label>Title</label><input name="title" minlength="3" maxlength="150" title="Merch title must be at least 3 characters." required value="<?php echo $edit ? e($edit['title']) : ''; ?>"></div>
            <div class="form-row">
                <label>Category</label>
                <select name="category_id">
                    <?php foreach ($cats as $c) { ?>
                        <option value="<?php echo (int) $c['id']; ?>" <?php echo ($edit && (int) $edit['category_id'] === (int) $c['id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row"><label>Tag</label><input name="tag" placeholder="Limited Edition / Pre-Order" value="<?php echo $edit ? e($edit['tag']) : ''; ?>"></div>
            <div class="form-row"><label>Image URL</label><input name="image" pattern="https?:\/\/.+" title="Image URL must begin with http:// or https://" placeholder="https://..." value="<?php echo $edit ? e($edit['image']) : ''; ?>"></div>
            <div class="form-row"><label>Or upload</label><input type="file" name="image_file" accept="image/*"></div>
            <div class="form-row"><label>Description</label><textarea name="description"><?php echo $edit ? e($edit['description']) : ''; ?></textarea></div>
            <button class="btn" type="submit">Save</button>
        </form>
        <table class="table">
            <thead><tr><th>Title</th><th>Tag</th><th>Views</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r) { ?>
                <tr>
                    <td><?php echo e($r['title']); ?></td>
                    <td><?php echo e($r['tag']); ?></td>
                    <td><?php echo (int) $r['views']; ?></td>
                    <td>
                        <a href="<?php echo e(site_url('/admin/merch.php?id=' . (int) $r['id'])); ?>">Edit</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete?');">
                            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                            <input type="hidden" name="delete_id" value="<?php echo (int) $r['id']; ?>">
                            <button class="btn-danger" type="submit">Delete</button>
                        </form>
                    </td>
                </tr>
            <?php } ?>
            </tbody>
        </table>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>

