<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';
$mail_config = require __DIR__ . '/config/mail.php';
$message = '';
$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$token_sql = esc($token);
$token_row = $token !== '' ? db_one("SELECT * FROM password_resets WHERE token = '$token_sql' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY id DESC LIMIT 1") : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    if (isset($_POST['email'])) {
        $email = strtolower(trim($_POST['email']));
        $email_sql = esc($email);
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? db_one("SELECT id, name FROM users WHERE email = '$email_sql' LIMIT 1") : null;
        if ($user) {
            $recent = db_one("SELECT id FROM password_resets WHERE email = '$email_sql' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 MINUTE) LIMIT 1");
            if (!$recent) {
                db_query("DELETE FROM password_resets WHERE email = '$email_sql'");
                $new_token = bin2hex(random_bytes(32));
                $new_token_sql = esc($new_token);
                $stored = db_query("INSERT INTO password_resets (email, token, created_at) VALUES ('$email_sql', '$new_token_sql', NOW())");
                if ($stored) {
                    $link = rtrim($mail_config['base_url'], '/') . '/forgot-password.php?token=' . rawurlencode($new_token);
                    $safe_name = e($user['name']);
                    $safe_link = e($link);
                    $subject = 'Fan Hub Plus password reset';
                    $html = '<p>Hello ' . $safe_name . ',</p><p>Use this one-time link to choose a new password. It expires in one hour.</p><p><a href="' . $safe_link . '">Reset your password</a></p><p>If you did not request this, you can ignore this email.</p>';
                    $text = "Hello " . $user['name'] . ",\n\nUse this one-time link to choose a new password. It expires in one hour.\n\n" . $link . "\n\nIf you did not request this, you can ignore this email.\n";
                    if (!send_resend_email($email, $subject, $html, $text, $mail_config)) {
                        db_query("DELETE FROM password_resets WHERE email = '$email_sql'");
                    }
                } else {
                    error_log('Fan Hub Plus could not create a password reset token.');
                }
            }
        }
        $message = 'If an account exists for that email, a password reset link has been sent.';
    } elseif (isset($_POST['new_password'], $_POST['token'])) {
        $posted_token = esc(trim($_POST['token']));
        $row = db_one("SELECT * FROM password_resets WHERE token = '$posted_token' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY id DESC LIMIT 1");
        $password = $_POST['new_password'];
        if ($row && preg_match('/^[a-f0-9]{64}$/', $posted_token) && strlen($password) >= 8) {
            $hash = esc(password_hash($password, PASSWORD_DEFAULT));
            $email_sql = esc($row['email']);
            if (db_query("UPDATE users SET password = '$hash' WHERE email = '$email_sql'")) {
                db_query("DELETE FROM password_resets WHERE email = '$email_sql'");
                flash_set('ok', 'Password updated. Please log in.');
                redirect('/login.php');
            }
            error_log('Fan Hub Plus could not update a password from a reset token.');
        }
        $message = 'This link has expired or is invalid. Request a new password reset link.';
        $token_row = null;
    }
}

$page_title = 'Password reset';
require __DIR__ . '/includes/header.php';
?>
<section class="auth-card card"><div class="card-body">
    <h1>Password reset</h1>
    <?php if ($message) { ?><p class="flash flash-ok" role="status"><?php echo e($message); ?></p><?php } ?>
    <?php if ($token_row) { ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <input type="hidden" name="token" value="<?php echo e($token_row['token']); ?>">
            <div class="form-row"><label for="new-password">New password</label><input id="new-password" name="new_password" type="password" autocomplete="new-password" minlength="8" required></div>
            <button class="btn" type="submit">Update password</button>
        </form>
    <?php } else { ?>
        <form method="post">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <div class="form-row"><label for="reset-email">Email</label><input id="reset-email" name="email" type="email" autocomplete="email" required></div>
            <button class="btn" type="submit">Send reset link</button>
        </form>
    <?php } ?>
</div></section>
<?php require __DIR__ . '/includes/footer.php'; ?>
