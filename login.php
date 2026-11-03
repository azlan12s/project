<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) {
    if (is_admin()) {
        redirect('/admin/index.php');
    }
    redirect('/dashboard.php');
}

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $email_sql = esc($email);
    $user = db_one("SELECT * FROM users WHERE email = '$email_sql' LIMIT 1");
    if ($user && password_verify($password, $user['password'])) {
        $_SESSION['user_id'] = (int) $user['id'];
        log_activity($user['id'], 'login', 'User signed in');
        if ($user['role'] === 'admin') {
            redirect('/admin/index.php');
        }
        $next = isset($_GET['next']) ? $_GET['next'] : '/dashboard.php';
        if (strpos($next, '/') !== 0 || strpos($next, '//') === 0) {
            $next = '/dashboard.php';
        }
        redirect($next);
    } else {
        $error = 'Invalid email or password.';
    }
}

$page_title = 'Login';
$hide_navbar = true;
$hide_footer = true;
require __DIR__ . '/includes/header.php';

// Side artwork. Missing file just falls back to the gradient panel.
$login_art = is_file(__DIR__ . '/assets/images/gojo.jpg') ? site_url('/assets/images/gojo.jpg') : '';
?>
<section class="auth-split" aria-label="Sign in">
    <div class="auth-panel">
        <div class="auth-box">
            <h1>Welcome back</h1>
            <p class="auth-lede">Registered members can bookmark, rate, and submit articles across all eight fandoms.</p>

            <?php if (isset($_GET['relogin'])) { ?>
                <p class="flash flash-info auth-alert" role="status" style="margin-bottom:16px;">You have been logged out. Please log in again to continue.</p>
            <?php } ?>

            <?php if ($error) { ?>
                <p class="flash flash-error auth-alert" role="alert"><?php echo e($error); ?></p>
            <?php } ?>

            <form method="post" class="auth-form">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">

                <div class="form-row">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Please enter a valid email address (e.g., you@example.com)" required>
                </div>

                <div class="form-row">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="current-password" placeholder="Your password" title="Please enter your password" required>
                        <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="password">Show</button>
                    </div>
                </div>

                <button class="btn auth-submit" type="submit">Log in</button>
            </form>

            <div class="auth-divider"><span>or</span></div>

            <div class="login-links auth-links">
                <a class="btn-ghost auth-alt-btn" href="<?php echo e(site_url('/register.php' . (isset($_GET['next']) && $_GET['next'] !== '' ? '?next=' . rawurlencode($_GET['next']) : ''))); ?>">Register / Create account</a>
                <a class="btn-ghost auth-alt-btn" href="<?php echo e(site_url('/index.php')); ?>">Back to Home</a>
            </div>
            <div class="login-links" style="justify-content:center; margin-top:12px;">
                <a class="login-text-button" href="<?php echo e(site_url('/forgot-password.php')); ?>">Forgot password?</a>
            </div>

            <ul class="auth-perks">
                <li>Bookmark anything for later</li>
                <li>Rate titles and drop reviews</li>
                <li>Submit your own articles</li>
            </ul>
        </div>
    </div>

    <aside class="auth-art" aria-hidden="true">
        <div class="auth-art-media"<?php echo $login_art === '' ? '' : ' style="background-image:url(\'' . e($login_art) . '\')"'; ?>></div>
        <div class="auth-art-body">
            <span class="auth-art-chip">Anime &middot; Jujutsu Kaisen</span>
            <blockquote class="auth-art-quote">Throughout heaven and earth, I alone am the honoured one.</blockquote>
            <p class="auth-art-caption">Eight fandoms are waiting on the other side of this form.</p>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
