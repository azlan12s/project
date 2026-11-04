<?php
require_once __DIR__ . '/../includes/functions.php';
require_admin();

$user = current_user();
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = strtolower(trim(isset($_POST['email']) ? $_POST['email'] : ''));
    $current_password = isset($_POST['current_password']) ? $_POST['current_password'] : '';
    $new_password = isset($_POST['new_password']) ? $_POST['new_password'] : '';
    $confirm_password = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

    if (!password_verify($current_password, $user['password'])) {
        $error = 'Current password is incorrect.';
    } elseif ($name === '' || strlen($name) > 120 || strlen($email) > 160 || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $error = 'Enter a name and a valid login email.';
    } elseif (db_one("SELECT id FROM users WHERE email = '" . esc($email) . "' AND id <> " . (int) $user['id'] . ' LIMIT 1')) {
        $error = 'That login email is already in use.';
    } elseif (($new_password !== '' || $confirm_password !== '') && (strlen($new_password) < 12 || $new_password !== $confirm_password)) {
        $error = 'New passwords must match and contain at least 12 characters.';
    } else {
        $name_sql = esc($name);
        $email_sql = esc($email);
        $password_sql = $new_password !== '' ? "'" . esc(password_hash($new_password, PASSWORD_DEFAULT)) . "'" : "'" . esc($user['password']) . "'";
        $updated = db_query("UPDATE users SET name = '$name_sql', email = '$email_sql', password = $password_sql WHERE id = " . (int) $user['id']);

        if (!$updated) {
            $error = 'Account could not be updated. Please try again.';
        } else {
            session_regenerate_id(true);
            log_activity($user['id'], 'account_updated', 'Administrator account details changed');
            flash_set('ok', 'Admin account updated. Use the new email the next time you sign in.');
            redirect('/admin/account.php');
        }
    }
}

$page_title = 'Account settings';
$active = 'account';
require __DIR__ . '/../includes/admin_header.php';
?>
<section class="admin-account">
    <h2>Admin account</h2>
    <p class="lede">Your email address is your login username. Verify your current password before saving account changes.</p>
    <?php if ($error) { ?><p class="flash flash-error" role="alert"><?php echo e($error); ?></p><?php } ?>
    <form class="card card-body" method="post" autocomplete="on">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <div class="form-row">
            <label for="admin-name">Display name</label>
            <input id="admin-name" name="name" value="<?php echo e(isset($_POST['name']) ? $_POST['name'] : $user['name']); ?>" autocomplete="name" pattern="[a-zA-Z\s\.\-']{2,120}" minlength="2" maxlength="120" title="Name must be 2-120 characters with letters, spaces, dots or hyphens." required>
        </div>
        <div class="form-row">
            <label for="admin-email">Login username (email)</label>
            <input id="admin-email" name="email" type="email" value="<?php echo e(isset($_POST['email']) ? $_POST['email'] : $user['email']); ?>" autocomplete="username" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" maxlength="160" title="Please enter a valid email address." required>
        </div>
        <div class="form-row">
            <label for="current-password">Current password</label>
            <input id="current-password" name="current_password" type="password" autocomplete="current-password" title="Please enter your current password to save changes." required>
        </div>
        <div class="form-row">
            <label for="new-password">New password</label>
            <input id="new-password" name="new_password" type="password" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~` ]).{8,}" minlength="8" placeholder="At least 8 chars with uppercase, lowercase, number, symbol" title="New password must be at least 8 characters long with uppercase, lowercase, number, and special symbol.">
        </div>
        <div class="form-row">
            <label for="confirm-password">Confirm new password</label>
            <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" minlength="8" placeholder="Re-enter new password" title="Confirm password must match new password.">
        </div>
        <button class="btn" type="submit">Save account</button>
    </form>
</section>
<?php require __DIR__ . '/../includes/admin_footer.php'; ?>

