<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if (is_admin()) {
    redirect('/admin/account.php');
}
$user = current_user();
$cats = categories();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $theme = (isset($_POST['theme']) && $_POST['theme'] === 'light') ? 'light' : 'dark';
    $font = isset($_POST['font_size']) && in_array($_POST['font_size'], array('sm', 'md', 'lg'), true) ? $_POST['font_size'] : 'md';
    $fav = isset($_POST['favorites']) && is_array($_POST['favorites']) ? $_POST['favorites'] : array();
    if ($name === '') {
        $error = 'Name is required.';
    } else {
        $avatar = $user['avatar'];
        $up = upload_file('avatar', 'avatars');
        if ($up !== '') {
            $avatar = $up;
        }
        $fav_clean = array();
        foreach ($fav as $slug) {
            $fav_clean[] = preg_replace('/[^a-z0-9\-]/', '', $slug);
        }
        $fav_str = esc(implode(',', $fav_clean));
        $name_sql = esc($name);
        $avatar_sql = esc($avatar);
        $uid = (int) $user['id'];
        db_query("UPDATE users SET name = '$name_sql', favorite_categories = '$fav_str', theme = '$theme', font_size = '$font', avatar = '$avatar_sql' WHERE id = $uid");
        flash_set('ok', 'Profile updated.');
        redirect('/profile.php');
    }
}

$selected = favorite_slugs($user);
$page_title = 'Profile';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <h1>Profile editor</h1>
    <?php if ($error) { ?><p class="flash flash-error"><?php echo e($error); ?></p><?php } ?>
    <form class="card card-body" method="post" enctype="multipart/form-data" style="max-width:640px">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <?php if ($user['avatar'] !== '') { ?><img class="avatar" src="<?php echo e(site_url($user['avatar'])); ?>" alt=""><?php } ?>
        <div class="form-row"><label>Name</label><input name="name" value="<?php echo e($user['name']); ?>" required></div>
        <div class="form-row"><label>Email</label><input value="<?php echo e($user['email']); ?>" disabled></div>
        <div class="form-row"><label>Avatar</label><input type="file" name="avatar" accept="image/*"></div>
        <div class="form-row">
            <span>Favorite categories</span>
            <?php foreach ($cats as $c) { ?>
                <label><input type="checkbox" name="favorites[]" value="<?php echo e($c['slug']); ?>" <?php echo in_array($c['slug'], $selected, true) ? 'checked' : ''; ?>> <?php echo e($c['name']); ?></label>
            <?php } ?>
        </div>
        <div class="form-row">
            <label>Theme</label>
            <select name="theme">
                <option value="dark" <?php echo $user['theme'] === 'dark' ? 'selected' : ''; ?>>Dark</option>
                <option value="light" <?php echo $user['theme'] === 'light' ? 'selected' : ''; ?>>Light</option>
            </select>
        </div>
        <div class="form-row">
            <label>Font size</label>
            <select name="font_size">
                <option value="sm" <?php echo $user['font_size'] === 'sm' ? 'selected' : ''; ?>>Small</option>
                <option value="md" <?php echo $user['font_size'] === 'md' ? 'selected' : ''; ?>>Medium</option>
                <option value="lg" <?php echo $user['font_size'] === 'lg' ? 'selected' : ''; ?>>Large</option>
            </select>
        </div>
        <button class="btn" type="submit">Save</button>
    </form>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
