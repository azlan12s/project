<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$edit = null;
if (isset($_GET['id'])) {
    $eid = (int) $_GET['id'];
    $edit = db_one("SELECT * FROM categories WHERE id = $eid LIMIT 1");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete_id'])) {
        $did = (int) $_POST['delete_id'];
        db_query("DELETE FROM categories WHERE id = $did");
        flash_set('ok', 'Category deleted.');
        redirect('/admin/categories.php');
    }
    $name = esc(trim($_POST['name']));
    $slug = esc(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $_POST['slug'] !== '' ? $_POST['slug'] : $_POST['name']), '-')));
    $description = esc(trim($_POST['description']));
    $icon = esc(trim($_POST['icon']));
    if (isset($_POST['id']) && (int) $_POST['id'] > 0) {
        $id = (int) $_POST['id'];
        db_query("UPDATE categories SET name = '$name', slug = '$slug', description = '$description', icon = '$icon' WHERE id = $id");
        flash_set('ok', 'Category updated.');
    } else {
        db_query("INSERT INTO categories (name, slug, description, icon) VALUES ('$name', '$slug', '$description', '$icon')");
        flash_set('ok', 'Category added.');
    }
    redirect('/admin/categories.php');
}

$rows = db_all("SELECT * FROM categories ORDER BY name ASC");
$page_title = 'Categories';
$active = 'categories';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h2 style="margin-top:0">Categories</h2>
        <form class="card card-body" method="post">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">
            <div class="form-row"><label>Name</label><input name="name" required value="<?php echo $edit ? e($edit['name']) : ''; ?>"></div>
            <div class="form-row"><label>Slug</label><input name="slug" value="<?php echo $edit ? e($edit['slug']) : ''; ?>"></div>
            <div class="form-row"><label>Icon</label><input name="icon" value="<?php echo $edit ? e($edit['icon']) : ''; ?>"></div>
            <div class="form-row"><label>Description</label><textarea name="description"><?php echo $edit ? e($edit['description']) : ''; ?></textarea></div>
            <button class="btn" type="submit">Save</button>
        </form>
        <table class="table">
            <thead><tr><th>Name</th><th>Slug</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r) { ?>
                <tr>
                    <td><?php echo e($r['icon'] . ' ' . $r['name']); ?></td>
                    <td><?php echo e($r['slug']); ?></td>
                    <td>
                        <a href="<?php echo e(site_url('/admin/categories.php?id=' . (int) $r['id'])); ?>">Edit</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete category? Related rows must be empty.');">
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
