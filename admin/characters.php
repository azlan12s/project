<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$cats = categories();
$edit = null;
if (isset($_GET['id'])) {
    $eid = (int) $_GET['id'];
    $edit = db_one("SELECT * FROM character_profiles WHERE id = $eid LIMIT 1");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete_id'])) {
        $did = (int) $_POST['delete_id'];
        db_query("DELETE FROM character_profiles WHERE id = $did");
        flash_set('ok', 'Character deleted.');
        redirect('/admin/characters.php');
    }
    $name = esc(trim($_POST['name']));
    $category_id = (int) $_POST['category_id'];
    $bio = esc(trim($_POST['bio']));
    $role_type = esc(trim($_POST['role_type']));
    $image = esc(trim($_POST['image']));
    $up = upload_file('image_file', 'characters');
    if ($up !== '') {
        $image = esc($up);
    }
    $attributes = esc(trim($_POST['attributes']));
    $timeline = esc(trim($_POST['timeline']));
    $related = esc(trim($_POST['related_content_ids']));
    if (isset($_POST['id']) && (int) $_POST['id'] > 0) {
        $id = (int) $_POST['id'];
        db_query("UPDATE character_profiles SET category_id = $category_id, name = '$name', bio = '$bio', role_type = '$role_type', image = '$image', attributes = '$attributes', timeline = '$timeline', related_content_ids = '$related' WHERE id = $id");
        flash_set('ok', 'Character updated.');
    } else {
        db_query("INSERT INTO character_profiles (category_id, name, bio, role_type, image, attributes, timeline, related_content_ids, created_at) VALUES ($category_id, '$name', '$bio', '$role_type', '$image', '$attributes', '$timeline', '$related', NOW())");
        flash_set('ok', 'Character created.');
    }
    redirect('/admin/characters.php');
}

$rows = db_all("SELECT character_profiles.*, categories.name AS category_name FROM character_profiles JOIN categories ON categories.id = character_profiles.category_id ORDER BY character_profiles.id DESC");
$page_title = 'Characters';
$active = 'characters';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h2 style="margin-top:0">Character profiles</h2>
        <form class="card card-body" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">
            <div class="form-row"><label>Name</label><input name="name" required value="<?php echo $edit ? e($edit['name']) : ''; ?>"></div>
            <div class="form-row">
                <label>Category</label>
                <select name="category_id">
                    <?php foreach ($cats as $c) { ?>
                        <option value="<?php echo (int) $c['id']; ?>" <?php echo ($edit && (int) $edit['category_id'] === (int) $c['id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row"><label>Role type</label><input name="role_type" value="<?php echo $edit ? e($edit['role_type']) : ''; ?>"></div>
            <div class="form-row"><label>Bio</label><textarea name="bio" rows="5"><?php echo $edit ? e($edit['bio']) : ''; ?></textarea></div>
            <div class="form-row"><label>Image URL</label><input name="image" value="<?php echo $edit ? e($edit['image']) : ''; ?>"></div>
            <div class="form-row"><label>Or upload image</label><input type="file" name="image_file" accept="image/*"></div>
            <div class="form-row"><label>Attributes</label><input name="attributes" value="<?php echo $edit ? e($edit['attributes']) : ''; ?>"></div>
            <div class="form-row"><label>Timeline</label><input name="timeline" value="<?php echo $edit ? e($edit['timeline']) : ''; ?>"></div>
            <div class="form-row"><label>Related content IDs (comma)</label><input name="related_content_ids" value="<?php echo $edit ? e($edit['related_content_ids']) : ''; ?>"></div>
            <button class="btn" type="submit">Save</button>
        </form>
        <table class="table">
            <thead><tr><th>Name</th><th>Category</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r) { ?>
                <tr>
                    <td><?php echo e($r['name']); ?></td>
                    <td><?php echo e($r['category_name']); ?></td>
                    <td>
                        <a href="<?php echo e(site_url('/admin/characters.php?id=' . (int) $r['id'])); ?>">Edit</a>
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
