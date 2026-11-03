<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$cats = categories();
$edit = null;
if (isset($_GET['id'])) {
    $eid = (int) $_GET['id'];
    $edit = db_one("SELECT * FROM contents WHERE id = $eid LIMIT 1");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete_id'])) {
        $did = (int) $_POST['delete_id'];
        db_query("DELETE FROM contents WHERE id = $did");
        flash_set('ok', 'Content deleted.');
        redirect('/admin/contents.php');
    }
    $title = esc(trim($_POST['title']));
    $slug = esc(strtolower(trim(preg_replace('/[^a-z0-9]+/i', '-', $_POST['title']), '-')));
    $category_id = (int) $_POST['category_id'];
    $type = in_array($_POST['type'], content_types(), true) ? $_POST['type'] : 'article';
    $genre = esc(trim($_POST['genre']));
    $body = esc(trim($_POST['body']));
    $media_url = esc(trim($_POST['media_url']));
    $video_upload = upload_video_file('video_file');
    if (isset($_FILES['video_file']) && $_FILES['video_file']['error'] !== UPLOAD_ERR_NO_FILE && $video_upload === '') {
        flash_set('error', 'Upload a valid MP4 or WebM video smaller than 25 MB.');
        redirect('/admin/contents.php' . ((isset($_POST['id']) && (int) $_POST['id'] > 0) ? '?id=' . (int) $_POST['id'] : ''));
    }
    if ($video_upload !== '') {
        $media_url = esc($video_upload);
    }
    $thumbnail = esc(trim($_POST['thumbnail']));
    $up = upload_file('thumb_file', 'content');
    if ($up !== '') {
        $thumbnail = esc($up);
    }
    $year = (int) $_POST['release_year'];
    $year_sql = $year > 1900 ? $year : 'NULL';
    $pop = (int) $_POST['popularity_score'];
    $featured = isset($_POST['featured']) ? 1 : 0;
    $status = esc($_POST['status']);
    $allowed_status = array('draft', 'pending', 'approved', 'rejected', 'published');
    if (!in_array($status, $allowed_status, true)) {
        $status = 'published';
    }
    $uid = (int) current_user()['id'];
    if (isset($_POST['id']) && (int) $_POST['id'] > 0) {
        $id = (int) $_POST['id'];
        db_query("UPDATE contents SET category_id = $category_id, title = '$title', slug = '$slug', type = '$type', genre = '$genre', body = '$body', media_url = '$media_url', thumbnail = '$thumbnail', release_year = $year_sql, popularity_score = $pop, featured = $featured, status = '$status' WHERE id = $id");
        flash_set('ok', 'Content updated.');
    } else {
        db_query("INSERT INTO contents (user_id, category_id, title, slug, type, genre, body, media_url, thumbnail, release_year, popularity_score, featured, views, status, created_at) VALUES ($uid, $category_id, '$title', '$slug', '$type', '$genre', '$body', '$media_url', '$thumbnail', $year_sql, $pop, $featured, 0, '$status', NOW())");
        flash_set('ok', 'Content created.');
    }
    redirect('/admin/contents.php');
}

$rows = db_all("SELECT contents.*, categories.name AS category_name FROM contents JOIN categories ON categories.id = contents.category_id ORDER BY contents.id DESC");
$page_title = 'Manage content';
$active = 'contents';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h2 style="margin-top:0">Content management</h2>
        <form class="card card-body" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">
            <h2><?php echo $edit ? 'Edit item' : 'New item'; ?></h2>
            <div class="form-row"><label>Title</label><input name="title" required value="<?php echo $edit ? e($edit['title']) : ''; ?>"></div>
            <div class="form-row">
                <label>Category</label>
                <select name="category_id">
                    <?php foreach ($cats as $c) { ?>
                        <option value="<?php echo (int) $c['id']; ?>" <?php echo ($edit && (int) $edit['category_id'] === (int) $c['id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row">
                <label>Type</label>
                <select name="type">
                    <?php foreach (content_types() as $t) { ?>
                        <option <?php echo ($edit && $edit['type'] === $t) ? 'selected' : ''; ?>><?php echo e($t); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row"><label>Genre</label><input name="genre" value="<?php echo $edit ? e($edit['genre']) : ''; ?>"></div>
            <div class="form-row"><label>Body</label><textarea name="body" rows="6"><?php echo $edit ? e($edit['body']) : ''; ?></textarea></div>
            <div class="form-row"><label>Media URL (YouTube embed / audio)</label><input name="media_url" value="<?php echo $edit ? e($edit['media_url']) : ''; ?>"></div>
            <div class="form-row"><label>Or upload a licensed MP4/WebM video (max 25 MB)</label><input type="file" name="video_file" accept="video/mp4,video/webm"></div>
            <div class="form-row"><label>Thumbnail URL</label><input name="thumbnail" value="<?php echo $edit ? e($edit['thumbnail']) : ''; ?>"></div>
            <div class="form-row"><label>Or upload thumbnail</label><input type="file" name="thumb_file" accept="image/*"></div>
            <div class="form-row"><label>Year</label><input name="release_year" type="number" value="<?php echo $edit ? e($edit['release_year']) : date('Y'); ?>"></div>
            <div class="form-row"><label>Popularity score</label><input name="popularity_score" type="number" value="<?php echo $edit ? (int) $edit['popularity_score'] : 50; ?>"></div>
            <div class="form-row">
                <label>Status</label>
                <select name="status">
                    <?php foreach (array('draft', 'pending', 'approved', 'rejected', 'published') as $st) { ?>
                        <option <?php echo ($edit && $edit['status'] === $st) ? 'selected' : ''; ?>><?php echo e($st); ?></option>
                    <?php } ?>
                </select>
            </div>
            <label><input type="checkbox" name="featured" <?php echo ($edit && (int) $edit['featured'] === 1) ? 'checked' : ''; ?>> Featured</label>
            <p><button class="btn" type="submit">Save</button></p>
        </form>
        <table class="table">
            <thead><tr><th>Title</th><th>Type</th><th>Status</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r) { ?>
                <tr>
                    <td><?php echo e($r['title']); ?><br><span class="meta"><?php echo e($r['category_name']); ?></span></td>
                    <td><?php echo e($r['type']); ?></td>
                    <td><?php echo e($r['status']); ?></td>
                    <td>
                        <a href="<?php echo e(site_url('/admin/contents.php?id=' . (int) $r['id'])); ?>">Edit</a>
                        <form method="post" style="display:inline" onsubmit="return confirm('Delete this item?');">
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
