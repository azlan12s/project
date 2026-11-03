<?php
require_once __DIR__ . '/includes/functions.php';
require_once __DIR__ . '/includes/email.php';
ensure_srs_tables();

$mail_config = require __DIR__ . '/config/mail.php';
$message = '';
$dev_notice = '';
$error = '';

$token = isset($_GET['token']) ? trim($_GET['token']) : '';
$token_sql = esc($token);
$token_row = $token !== '' ? db_one("SELECT * FROM password_resets WHERE token = '$token_sql' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY id DESC LIMIT 1") : null;

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();

    // 1. Request Password Reset Link
    if (isset($_POST['email'])) {
        $email = strtolower(trim($_POST['email']));
        $email_sql = esc($email);
        $user = filter_var($email, FILTER_VALIDATE_EMAIL) ? db_one("SELECT id, name, email FROM users WHERE email = '$email_sql' LIMIT 1") : null;

        if ($user) {
            // Delete old tokens for this email to keep it clean
            db_query("DELETE FROM password_resets WHERE email = '$email_sql'");

            // Generate secure 64-character token
            $new_token = bin2hex(random_bytes(32));
            $new_token_sql = esc($new_token);
            $stored = db_query("INSERT INTO password_resets (email, token, created_at) VALUES ('$email_sql', '$new_token_sql', NOW())");

            if ($stored) {
                // Determine base URL
                $base_url = !empty($mail_config['base_url']) ? rtrim($mail_config['base_url'], '/') : '';
                if (empty($base_url)) {
                    $scheme = (!empty($_SERVER['HTTPS']) && strtolower($_SERVER['HTTPS']) !== 'off') ? 'https' : 'http';
                    $host = isset($_SERVER['HTTP_HOST']) ? $_SERVER['HTTP_HOST'] : 'localhost';
                    $base_url = $scheme . '://' . $host . site_base_path();
                }
                $link = rtrim($base_url, '/') . '/forgot-password.php?token=' . rawurlencode($new_token);
                $safe_name = e($user['name']);
                $safe_link = e($link);

                $subject = 'Reset Your Password - Fan Hub Plus';

                // Professional HTML email template
                $html = '<!DOCTYPE html><html><head><meta charset="utf-8"></head><body style="margin:0;padding:24px;font-family:\'Segoe UI\',Roboto,Helvetica,Arial,sans-serif;background-color:#121316;color:#f4f4f5;">'
                    . '<table role="presentation" width="100%" cellspacing="0" cellpadding="0" style="max-width:560px;margin:0 auto;background:#1c1d22;border:1px solid #2d2e36;border-radius:14px;overflow:hidden;">'
                    . '<tr><td style="padding:28px 32px;background:linear-gradient(135deg,#470a11,#1c1d22);border-bottom:1px solid #2d2e36;">'
                    . '<h2 style="margin:0;color:#ffffff;font-size:22px;letter-spacing:-0.5px;">Fan Hub Plus</h2>'
                    . '<p style="margin:4px 0 0;color:#fca5a5;font-size:13px;">Official Fandom Community Platform</p>'
                    . '</td></tr>'
                    . '<tr><td style="padding:32px;">'
                    . '<h3 style="margin:0 0 14px;font-size:18px;color:#ffffff;">Hello, ' . $safe_name . '!</h3>'
                    . '<p style="margin:0 0 20px;font-size:14px;line-height:1.6;color:#d4d4d8;">'
                    . 'We received a request to reset your password for your Fan Hub Plus account. Click the button below to choose a new password:'
                    . '</p>'
                    . '<div style="text-align:center;margin:28px 0;">'
                    . '<a href="' . $safe_link . '" style="display:inline-block;background:#e11d2e;color:#ffffff;text-decoration:none;font-weight:700;font-size:15px;padding:12px 28px;border-radius:10px;box-shadow:0 4px 14px rgba(225,29,46,0.4);">Reset My Password</a>'
                    . '</div>'
                    . '<p style="margin:0 0 10px;font-size:13px;line-height:1.6;color:#a1a1aa;">Or copy and paste this link into your browser:</p>'
                    . '<p style="margin:0 0 20px;font-size:12px;word-break:break-all;color:#e11d2e;background:#141416;padding:10px;border-radius:8px;border:1px solid #2d2e36;">' . $safe_link . '</p>'
                    . '<div style="border-top:1px solid #2d2e36;padding-top:16px;margin-top:24px;">'
                    . '<p style="margin:0;font-size:12px;color:#71717a;line-height:1.5;">'
                    . 'This tokenized link is valid for <strong>1 hour</strong>. If you did not request this password reset, please ignore this email.'
                    . '</p></div>'
                    . '</td></tr></table></body></html>';

                $text = "Hello " . $user['name'] . ",\n\n"
                    . "We received a request to reset your password for your Fan Hub Plus account.\n"
                    . "Click this tokenized link to choose a new password (valid for 1 hour):\n\n"
                    . $link . "\n\n"
                    . "If you did not request this, you can safely ignore this email.\n";

                // Dispatch via Gmail SMTP
                $send_result = send_gmail_smtp($email, $subject, $html, $text, $mail_config);

                if ($send_result['ok']) {
                    $message = 'A password reset link has been successfully dispatched to your email (' . e($email) . '). Please check your inbox and spam folder.';
                } else {
                    $message = 'A password reset link has been generated.';
                    if ($send_result['error'] === 'credentials_missing') {
                        $dev_notice = '<div style="margin-bottom:6px;"><strong>[Gmail SMTP Notice]</strong>: Gmail credentials not yet entered in <code>config/mail.php</code>.</div>'
                            . '<div style="font-size:0.86rem;color:var(--text);margin-bottom:10px;">Set your <code>smtp_user</code> (Gmail) and <code>smtp_pass</code> (16-digit Google App Password) in <code>config/mail.php</code> to receive live emails.</div>'
                            . '<div><strong>Testing Link:</strong> You can open the tokenized reset link directly:<br><a href="' . $safe_link . '" style="color:var(--accent-2);word-break:break-all;font-weight:700;display:inline-block;margin-top:6px;">' . $safe_link . ' &rarr;</a></div>';
                    } else {
                        $dev_notice = '<div style="margin-bottom:6px;"><strong>[Gmail SMTP Status]</strong>: ' . e($send_result['message']) . '</div>'
                            . '<div><strong>Testing Link:</strong> You can open the tokenized reset link directly:<br><a href="' . $safe_link . '" style="color:var(--accent-2);word-break:break-all;font-weight:700;display:inline-block;margin-top:6px;">' . $safe_link . ' &rarr;</a></div>';
                    }
                }
            } else {
                $error = 'Could not generate reset token. Please try again.';
            }
        } else {
            // Generic security response so user existence cannot be enumerated
            $message = 'If an account exists for that email, a password reset link has been sent.';
        }
    }
    // 2. Process New Password Submission
    elseif (isset($_POST['new_password'], $_POST['token'])) {
        $posted_token = trim($_POST['token']);
        $posted_token_sql = esc($posted_token);
        $row = db_one("SELECT * FROM password_resets WHERE token = '$posted_token_sql' AND created_at >= DATE_SUB(NOW(), INTERVAL 1 HOUR) ORDER BY id DESC LIMIT 1");

        $new_pwd = isset($_POST['new_password']) ? $_POST['new_password'] : '';
        $confirm_pwd = isset($_POST['confirm_password']) ? $_POST['confirm_password'] : '';

        if (!$row) {
            $error = 'This password reset link has expired or is invalid. Please request a new one.';
            $token_row = null;
        } elseif (strlen($new_pwd) < 8) {
            $error = 'Password must be at least 8 characters long.';
        } elseif ($confirm_pwd !== '' && $new_pwd !== $confirm_pwd) {
            $error = 'Passwords do not match. Please re-enter.';
        } else {
            $hash = esc(password_hash($new_pwd, PASSWORD_DEFAULT));
            $email_sql = esc($row['email']);

            $updated = db_query("UPDATE users SET password = '$hash' WHERE email = '$email_sql'");
            if ($updated) {
                db_query("DELETE FROM password_resets WHERE email = '$email_sql'");
                flash_set('ok', 'Your password has been successfully updated! You can now log in with your new password.');
                redirect('/login.php');
            } else {
                $error = 'Failed to update password. Please try again.';
            }
        }
    }
}

$page_title = 'Password Reset';
require __DIR__ . '/includes/header.php';
?>

<section class="auth-card card" style="max-width:520px; margin:40px auto;">
    <div class="card-body" style="padding:28px;">
        <h1 style="margin-top:0; font-size:1.6rem;"><?php echo $token_row ? 'Set New Password' : 'Forgot Password'; ?></h1>
        <p class="lede" style="font-size:0.92rem; margin-bottom:20px;">
            <?php echo $token_row ? 'Enter your new secure password below to regain access to your account.' : 'Enter your registered email address and we will send you a tokenized reset link.'; ?>
        </p>

        <?php if (!empty($message)) { ?>
            <p class="flash flash-ok" role="status" style="margin-bottom:16px;"><?php echo e($message); ?></p>
        <?php } ?>

        <?php if (!empty($error)) { ?>
            <p class="flash flash-err" role="status" style="margin-bottom:16px;"><?php echo e($error); ?></p>
        <?php } ?>

        <?php if (!empty($dev_notice)) { ?>
            <div class="flash flash-info" role="status" style="border:1px solid rgba(225,29,46,0.3); background:rgba(225,29,46,0.08); margin-bottom:18px; line-height:1.5;">
                <?php echo $dev_notice; ?>
            </div>
        <?php } ?>

        <?php if ($token_row) { ?>
            <!-- Set New Password Form -->
            <form method="post" id="resetPasswordForm">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="token" value="<?php echo e($token_row['token']); ?>">

                <div class="form-row">
                    <label for="new-password">New password</label>
                    <input id="new-password" name="new_password" type="password" autocomplete="new-password" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~` ]).{8,}" minlength="8" placeholder="At least 8 chars (A-Z, a-z, 0-9, symbol)" title="Password must be at least 8 characters long with uppercase, lowercase, number, and special symbol." required>
                </div>

                <div class="form-row">
                    <label for="confirm-password">Confirm new password</label>
                    <input id="confirm-password" name="confirm_password" type="password" autocomplete="new-password" placeholder="Re-enter new password" minlength="8" required>
                </div>

                <button class="btn btn-primary" type="submit" style="width:100%; padding:12px; font-weight:700; margin-top:10px;">Update Password &amp; Log In</button>
            </form>
        <?php } else { ?>
            <!-- Request Reset Link Form -->
            <form method="post" id="forgotPasswordForm">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">

                <div class="form-row">
                    <label for="reset-email">Your registered email address</label>
                    <input id="reset-email" name="email" type="email" autocomplete="email" placeholder="you@example.com" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Please enter a valid email address (e.g., you@example.com)" required>
                </div>

                <button class="btn btn-primary" type="submit" style="width:100%; padding:12px; font-weight:700; margin-top:10px;">Send Reset Link</button>
            </form>
        <?php } ?>

        <div style="margin-top:22px; padding-top:16px; border-top:1px solid var(--line); display:flex; justify-content:space-between; font-size:0.88rem;">
            <a href="<?php echo e(site_url('/login.php')); ?>" style="color:var(--muted); text-decoration:none;">&larr; Back to Login</a>
            <a href="<?php echo e(site_url('/register.php')); ?>" style="color:var(--accent-2); text-decoration:none;">Create New Account</a>
        </div>
    </div>
</section>

<?php require __DIR__ . '/includes/footer.php'; ?>
