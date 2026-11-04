<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
if (is_admin()) {
    redirect('/admin/account.php');
}
$user = current_user();
$cats = categories();
$fan_profile = user_fan_profile((int) $user['id']);
$orders = db_all("SELECT id FROM merch_orders WHERE user_id = " . (int) $user['id']);

$profile_error = '';
$pwd_error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form_action = isset($_POST['form_action']) ? trim($_POST['form_action']) : 'update_profile';

    if ($form_action === 'change_password') {
        $current_pwd = isset($_POST['current_password']) ? $_POST['current_password'] : '';
        $new_pwd     = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_pwd = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        $u_row = db_one("SELECT password FROM users WHERE id = " . (int) $user['id'] . " LIMIT 1");

        if ($current_pwd === '' || $new_pwd === '' || $confirm_pwd === '') {
            $pwd_error = 'All password fields are required.';
        } elseif (!$u_row || !password_verify($current_pwd, $u_row['password'])) {
            $pwd_error = 'Current password is incorrect. Please verify and try again.';
        } elseif (strlen($new_pwd) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?~` ]).{8,}$/', $new_pwd)) {
            $pwd_error = 'New password must be at least 8 characters long and contain uppercase, lowercase, number, and special symbol.';
        } elseif ($new_pwd !== $confirm_pwd) {
            $pwd_error = 'New password and confirmation password do not match.';
        } elseif (password_verify($new_pwd, $u_row['password'])) {
            $pwd_error = 'New password cannot be identical to your current password.';
        } else {
            $hash = esc(password_hash($new_pwd, PASSWORD_DEFAULT));
            db_query("UPDATE users SET password = '$hash' WHERE id = " . (int) $user['id']);
            session_regenerate_id(true);
            log_activity((int) $user['id'], 'password_change', 'User updated account password');
            flash_set('ok', 'Your password has been changed successfully!');
            redirect('/profile.php');
        }
    } else {
        $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
        $theme = (isset($_POST['theme']) && $_POST['theme'] === 'light') ? 'light' : 'dark';
        $font = isset($_POST['font_size']) && in_array($_POST['font_size'], array('sm', 'md', 'lg'), true) ? $_POST['font_size'] : 'md';
        $fav = isset($_POST['favorites']) && is_array($_POST['favorites']) ? $_POST['favorites'] : array();

        if ($name === '' || !preg_match("/^[a-zA-Z\s\.\-']{2,60}$/", $name)) {
            $profile_error = 'Name must be 2-60 characters and contain only letters and spaces.';
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
            log_activity($uid, 'profile_updated', 'Profile information and preferences updated');
            flash_set('ok', 'Profile details updated successfully.');
            redirect('/profile.php');
        }
    }
}

$selected = favorite_slugs($user);
$page_title = 'Profile & Security';
require __DIR__ . '/includes/header.php';
?>

<section class="wrap section dash">
    <aside class="side">
        <?php if ($user['avatar'] !== '') { ?><img class="avatar" src="<?php echo e(site_url($user['avatar'])); ?>" alt=""><?php } ?>
        <h3><?php echo e($user['name']); ?></h3>
        <p class="meta"><?php echo e($user['role']); ?> · <span class="chip"><?php echo e($fan_profile['rank']); ?></span></p>
        <a href="<?php echo e(site_url('/dashboard.php')); ?>">&larr; Account Dashboard</a>
        <a href="<?php echo e(site_url('/profile.php')); ?>" class="active" style="font-weight:700; color:var(--accent-2);">Profile &amp; preferences</a>
        <a href="<?php echo e(site_url('/dashboard.php#my-orders')); ?>">My Merch Orders (<?php echo count($orders); ?>)</a>
        <a href="<?php echo e(site_url('/bookmarks.php')); ?>">Bookmarks drawer</a>
        <a href="<?php echo e(site_url('/submit-article.php')); ?>">Submit article</a>
        <?php if ($user['role'] === 'admin') { ?><a href="<?php echo e(site_url('/admin/index.php')); ?>">Admin panel</a><?php } ?>
        <a href="<?php echo e(site_url('/logout.php')); ?>">Logout</a>
    </aside>

    <div style="width:100%; min-width:0;">
        <div style="display:flex; justify-content:space-between; align-items:center; flex-wrap:wrap; gap:12px; margin-bottom:24px;">
            <div>
                <h1 style="margin:0 0 4px; font-size:1.85rem;">Profile &amp; Security Settings</h1>
                <p class="lede" style="margin:0;">Update your public profile, customize interface theme, and manage your account password.</p>
            </div>
            <a href="<?php echo e(site_url('/dashboard.php')); ?>" class="btn-ghost" style="font-size:0.85rem; padding:8px 16px;">&larr; Back to Dashboard</a>
        </div>


        <div class="card card-body" style="width:100%; border:1px solid var(--line); border-radius:16px; padding:28px; margin-bottom:28px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; border-bottom:1px solid var(--line); padding-bottom:14px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:22px; height:22px; color:var(--accent-2);"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"/><circle cx="12" cy="7" r="4"/></svg>
                <div>
                    <h2 style="margin:0; font-size:1.25rem;">Profile Details &amp; Preferences</h2>
                    <p class="meta" style="margin:2px 0 0;">Manage your avatar, screen name, and fandom categories.</p>
                </div>
            </div>

            <?php if ($profile_error) { ?><p class="flash flash-error" style="margin-bottom:18px;"><?php echo e($profile_error); ?></p><?php } ?>

            <form method="post" enctype="multipart/form-data" style="display:flex; flex-direction:column; gap:18px; width:100%;">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="form_action" value="update_profile">


                <div style="display:flex; align-items:center; gap:20px; flex-wrap:wrap; padding:16px; background:var(--bg-2); border:1px solid var(--line); border-radius:12px;">
                    <div style="position:relative; flex-shrink:0;">
                        <?php if ($user['avatar'] !== '') { ?>
                            <img src="<?php echo e(site_url($user['avatar'])); ?>" alt="" style="width:72px; height:72px; border-radius:50%; object-fit:cover; border:2px solid var(--accent-2); display:block;">
                        <?php } else { ?>
                            <div style="width:72px; height:72px; border-radius:50%; background:var(--card); border:2px dashed var(--line); display:grid; place-items:center; color:var(--muted); font-size:1.6rem; font-weight:800;">
                                <?php echo strtoupper(substr($user['name'], 0, 1)); ?>
                            </div>
                        <?php } ?>
                    </div>
                    <div style="flex:1; min-width:240px;">
                        <label for="avatar_input" style="font-weight:700; display:block; margin-bottom:6px;">Profile Avatar</label>
                        <input id="avatar_input" type="file" name="avatar" accept="image/*" style="width:100%; font-size:0.86rem;">
                        <small class="meta" style="display:block; margin-top:4px;">Upload JPG, PNG, or WebP image (square format recommended).</small>
                    </div>
                </div>


                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px; width:100%;">
                    <div class="form-row">
                        <label for="profile_name" style="font-weight:700; margin-bottom:6px; display:block;">Display Name</label>
                        <input id="profile_name" name="name" value="<?php echo e($user['name']); ?>" pattern="[a-zA-Z\s\.\-']{2,60}" minlength="2" maxlength="60" title="Name must be 2-60 characters and contain only letters and spaces." required style="width:100%;">
                    </div>
                    <div class="form-row">
                        <label style="font-weight:700; margin-bottom:6px; display:block;">Login Email</label>
                        <input value="<?php echo e($user['email']); ?>" disabled style="width:100%; opacity:0.75; cursor:not-allowed;" title="Email cannot be changed directly.">
                    </div>
                </div>


                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px; width:100%;">
                    <div class="form-row">
                        <label for="profile_theme" style="font-weight:700; margin-bottom:6px; display:block;">Interface Theme</label>
                        <select id="profile_theme" name="theme" style="width:100%; padding:10px 14px; border-radius:10px; border:1px solid var(--line); background:var(--card); color:var(--text);">
                            <option value="dark" <?php echo $user['theme'] === 'dark' ? 'selected' : ''; ?>>Dark Theme</option>
                            <option value="light" <?php echo $user['theme'] === 'light' ? 'selected' : ''; ?>>Light Theme</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="profile_font_size" style="font-weight:700; margin-bottom:6px; display:block;">Reading Font Size</label>
                        <select id="profile_font_size" name="font_size" style="width:100%; padding:10px 14px; border-radius:10px; border:1px solid var(--line); background:var(--card); color:var(--text);">
                            <option value="sm" <?php echo $user['font_size'] === 'sm' ? 'selected' : ''; ?>>Small</option>
                            <option value="md" <?php echo $user['font_size'] === 'md' ? 'selected' : ''; ?>>Medium (Default)</option>
                            <option value="lg" <?php echo $user['font_size'] === 'lg' ? 'selected' : ''; ?>>Large</option>
                        </select>
                    </div>
                </div>


                <div class="form-row" style="width:100%;">
                    <label style="font-weight:700; margin-bottom:8px; display:block;">Favorite Fandom Categories</label>
                    <div style="display:grid; grid-template-columns:repeat(auto-fill, minmax(140px, 1fr)); gap:10px; width:100%;">
                        <?php foreach ($cats as $c) {
                            $is_checked = in_array($c['slug'], $selected, true);
                        ?>
                            <label style="display:flex; align-items:center; gap:8px; padding:9px 12px; background:var(--bg-2); border:1px solid var(--line); border-radius:10px; cursor:pointer; font-size:0.88rem; font-weight:600;">
                                <input type="checkbox" name="favorites[]" value="<?php echo e($c['slug']); ?>" <?php echo $is_checked ? 'checked' : ''; ?> style="accent-color:var(--accent-2); width:16px; height:16px;">
                                <span><?php echo e($c['name']); ?></span>
                            </label>
                        <?php } ?>
                    </div>
                </div>

                <div>
                    <button class="btn btn-primary" type="submit" style="padding:10px 24px; font-weight:750;">Save Profile Changes</button>
                </div>
            </form>
        </div>


        <div class="card card-body" style="width:100%; border:1px solid var(--line); border-radius:16px; padding:28px;">
            <div style="display:flex; align-items:center; gap:10px; margin-bottom:16px; border-bottom:1px solid var(--line); padding-bottom:14px;">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" style="width:22px; height:22px; color:var(--accent-2);"><rect x="3" y="11" width="18" height="11" rx="2" ry="2"/><path d="M7 11V7a5 5 0 0 1 10 0v4"/></svg>
                <div>
                    <h2 style="margin:0; font-size:1.25rem;">Change Account Password</h2>
                    <p class="meta" style="margin:2px 0 0;">Update your password to keep your account safe and secure.</p>
                </div>
            </div>

            <?php if ($pwd_error) { ?><p class="flash flash-error" style="margin-bottom:18px;"><?php echo e($pwd_error); ?></p><?php } ?>

            <form method="post" autocomplete="off" style="display:flex; flex-direction:column; gap:18px; width:100%;">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="form_action" value="change_password">

                <div class="form-row" style="width:100%;">
                    <label for="current_password" style="font-weight:700; margin-bottom:6px; display:block;">Current Password</label>
                    <div class="password-field">
                        <input id="current_password" name="current_password" type="password" autocomplete="current-password" placeholder="Enter your current password" required style="width:100%;">
                        <button class="password-toggle" type="button" aria-controls="current_password" aria-label="Show password">Show</button>
                    </div>
                </div>

                <div style="display:grid; grid-template-columns:repeat(auto-fit, minmax(280px, 1fr)); gap:18px; width:100%;">
                    <div class="form-row">
                        <label for="new_password" style="font-weight:700; margin-bottom:6px; display:block;">New Password</label>
                        <div class="password-field">
                            <input id="new_password" name="new_password" type="password" autocomplete="new-password" placeholder="At least 8 chars (A-Z, a-z, 0-9, symbol)" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~` ]).{8,}" minlength="8" required style="width:100%;">
                            <button class="password-toggle" type="button" aria-controls="new_password" aria-label="Show password">Show</button>
                        </div>
                    </div>

                    <div class="form-row">
                        <label for="confirm_password" style="font-weight:700; margin-bottom:6px; display:block;">Confirm New Password</label>
                        <div class="password-field">
                            <input id="confirm_password" name="confirm_password" type="password" autocomplete="new-password" placeholder="Re-enter your new password" minlength="8" required style="width:100%;">
                            <button class="password-toggle" type="button" aria-controls="confirm_password" aria-label="Show password">Show</button>
                        </div>
                    </div>
                </div>

                <div style="background:var(--bg-2); border:1px solid var(--line); border-radius:10px; padding:12px 16px; font-size:0.83rem; color:var(--muted); line-height:1.5;">
                    <strong style="color:var(--text); display:block; margin-bottom:4px;">Password Security Guidelines:</strong>
                    <ul style="margin:0; padding-left:18px;">
                        <li>At least 8 characters in length.</li>
                        <li>Include uppercase (A-Z) and lowercase letters (a-z).</li>
                        <li>Include at least one number (0-9).</li>
                        <li>Include at least one special symbol (e.g. ! @ # $ % ^ &amp; *).</li>
                    </ul>
                </div>

                <div>
                    <button class="btn btn-primary" type="submit" style="padding:10px 24px; font-weight:750;">Update Password</button>
                </div>
            </form>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>

