<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();
$cats = categories();
$edit = null;
if (isset($_GET['id'])) {
    $eid = (int) $_GET['id'];
    $edit = db_one("SELECT * FROM events WHERE id = $eid LIMIT 1");
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['delete_id'])) {
        $did = (int) $_POST['delete_id'];
        db_query("DELETE FROM events WHERE id = $did");
        flash_set('ok', 'Event deleted.');
        redirect('/admin/events.php');
    }
    $title = esc(trim($_POST['title']));
    $category_id = (int) $_POST['category_id'];
    $city = esc(trim($_POST['city']));
    $lat = esc(trim($_POST['latitude']));
    $lng = esc(trim($_POST['longitude']));
    $date = esc(trim($_POST['event_date']));
    $ticket = esc(trim($_POST['ticket_url']));
    $description = esc(trim($_POST['description']));
    $image = esc(trim($_POST['image']));
    if (isset($_POST['id']) && (int) $_POST['id'] > 0) {
        $id = (int) $_POST['id'];
        db_query("UPDATE events SET category_id = $category_id, title = '$title', city = '$city', latitude = '$lat', longitude = '$lng', event_date = '$date', ticket_url = '$ticket', description = '$description', image = '$image' WHERE id = $id");
        flash_set('ok', 'Event updated.');
    } else {
        db_query("INSERT INTO events (category_id, title, city, latitude, longitude, event_date, ticket_url, description, image, created_at) VALUES ($category_id, '$title', '$city', '$lat', '$lng', '$date', '$ticket', '$description', '$image', NOW())");
        flash_set('ok', 'Event created.');
    }
    redirect('/admin/events.php');
}

$rows = db_all("SELECT events.*, categories.name AS category_name FROM events JOIN categories ON categories.id = events.category_id ORDER BY events.event_date DESC");
$page_title = 'Events admin';
$active = 'events';
require __DIR__ . '/../includes/admin_header.php';
?>
        <h2 style="margin-top:0">Events</h2>
        <form class="card card-body" method="post">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="id" value="<?php echo $edit ? (int) $edit['id'] : 0; ?>">
            <div class="form-row"><label>Title</label><input name="title" minlength="3" maxlength="150" title="Event title must be at least 3 characters." required value="<?php echo $edit ? e($edit['title']) : ''; ?>"></div>
            <div class="form-row">
                <label>Category</label>
                <select name="category_id">
                    <?php foreach ($cats as $c) { ?>
                        <option value="<?php echo (int) $c['id']; ?>" <?php echo ($edit && (int) $edit['category_id'] === (int) $c['id']) ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option>
                    <?php } ?>
                </select>
            </div>
            <div class="form-row"><label>City</label><input name="city" pattern="[a-zA-Z\s\.\-']{2,80}" title="City must contain letters and spaces." required value="<?php echo $edit ? e($edit['city']) : ''; ?>"></div>
            <div class="form-row"><label>Latitude</label><input name="latitude" required value="<?php echo $edit ? e($edit['latitude']) : ''; ?>"></div>
            <div class="form-row"><label>Longitude</label><input name="longitude" required value="<?php echo $edit ? e($edit['longitude']) : ''; ?>"></div>
            <div class="form-row"><label>Date</label><input name="event_date" type="date" required value="<?php echo $edit ? e($edit['event_date']) : ''; ?>"></div>
            <div class="form-row"><label>Ticket URL</label><input name="ticket_url" pattern="https?:\/\/.+" title="Ticket URL must begin with http:// or https://" placeholder="https://..." value="<?php echo $edit ? e($edit['ticket_url']) : ''; ?>"></div>
            <div class="form-row"><label>Image URL</label><input name="image" pattern="https?:\/\/.+" title="Image URL must begin with http:// or https://" placeholder="https://..." value="<?php echo $edit ? e($edit['image']) : ''; ?>"></div>
            <div class="form-row"><label>Description</label><textarea name="description"><?php echo $edit ? e($edit['description']) : ''; ?></textarea></div>
            <button class="btn" type="submit">Save</button>
        </form>
        <table class="table">
            <thead><tr><th>Title</th><th>City</th><th>Date</th><th></th></tr></thead>
            <tbody>
            <?php foreach ($rows as $r) { ?>
                <tr>
                    <td><?php echo e($r['title']); ?></td>
                    <td><?php echo e($r['city']); ?></td>
                    <td><?php echo e($r['event_date']); ?></td>
                    <td>
                        <a href="<?php echo e(site_url('/admin/events.php?id=' . (int) $r['id'])); ?>">Edit</a>
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

