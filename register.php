<?php
require_once __DIR__ . '/includes/functions.php';
if (is_logged_in()) {
    if (is_admin()) {
        redirect('/admin/index.php');
    }
    redirect('/dashboard.php');
}

$error = '';
$cats = categories();
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $name = trim(isset($_POST['name']) ? $_POST['name'] : '');
    $email = trim(isset($_POST['email']) ? $_POST['email'] : '');
    $password = isset($_POST['password']) ? $_POST['password'] : '';
    $fav = isset($_POST['favorites']) && is_array($_POST['favorites']) ? $_POST['favorites'] : array();
    $theme = (isset($_POST['theme']) && $_POST['theme'] === 'light') ? 'light' : 'dark';
    $font = isset($_POST['font_size']) && in_array($_POST['font_size'], array('sm', 'md', 'lg'), true) ? $_POST['font_size'] : 'md';

    if ($name === '' || !preg_match("/^[a-zA-Z\s\.\-']{2,60}$/", $name) || !filter_var($email, FILTER_VALIDATE_EMAIL) || strlen($password) < 8 || !preg_match('/^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};\':"\\\\|,.<>\/?~` ]).{8,}$/', $password)) {
        $error = 'Provide a valid name (2-60 letters), a valid email, and a password of at least 8 characters with uppercase, lowercase, number, and special symbol.';
    } else {
        $email_sql = esc($email);
        $exists = db_one("SELECT id FROM users WHERE email = '$email_sql' LIMIT 1");
        if ($exists) {
            $error = 'That email is already registered.';
        } else {
            $hash = password_hash($password, PASSWORD_DEFAULT);
            $fav_clean = array();
            foreach ($fav as $slug) {
                $fav_clean[] = preg_replace('/[^a-z0-9\-]/', '', $slug);
            }
            $fav_str = esc(implode(',', $fav_clean));
            $name_sql = esc($name);
            $hash_sql = esc($hash);
            db_query("INSERT INTO users (name, email, password, role, avatar, favorite_categories, theme, font_size, created_at) VALUES ('$name_sql', '$email_sql', '$hash_sql', 'user', '', '$fav_str', '$theme', '$font', NOW())");
            $_SESSION['user_id'] = db_insert_id();
            log_activity($_SESSION['user_id'], 'register', 'New member joined');
            flash_set('ok', 'Welcome to Fan Hub Plus.');
            $next = isset($_GET['next']) ? $_GET['next'] : '/dashboard.php';
            if (strpos($next, '/') !== 0 || strpos($next, '//') === 0) {
                $next = '/dashboard.php';
            }
            redirect($next);
        }
    }
}

$page_title = 'Register';
$hide_navbar = true;
$hide_footer = true;
require __DIR__ . '/includes/header.php';


$join_art = is_file(__DIR__ . '/assets/images/drdoom.webp') ? site_url('/assets/images/drdoom.webp') : '';
?>
<section class="auth-split auth-split--join" aria-label="Create an account">
    <div class="auth-panel">
        <div class="auth-box">
            <h1>Create your hub</h1>
            <p class="auth-lede">Free, no approval needed. Pick the fandoms you care about and the rest follows.</p>

            <?php if ($error) { ?>
                <p class="flash flash-error auth-alert" role="alert"><?php echo e($error); ?></p>
            <?php } ?>

            <form method="post" class="auth-form">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">

                <div class="form-row">
                    <label for="name">Name</label>
                    <input id="name" name="name" autocomplete="name" placeholder="How should we call you?" pattern="[a-zA-Z\s\.\-']{2,60}" minlength="2" maxlength="60" title="Name must be 2-60 characters and contain only letters and spaces." required>
                </div>

                <div class="form-row">
                    <label for="email">Email</label>
                    <input id="email" name="email" type="email" autocomplete="email" placeholder="you@example.com" pattern="[a-zA-Z0-9._%+\-]+@[a-zA-Z0-9.\-]+\.[a-zA-Z]{2,}" title="Please enter a valid email address (e.g., you@example.com)" required>
                </div>

                <div class="form-row">
                    <label for="password">Password</label>
                    <div class="password-field">
                        <input id="password" name="password" type="password" autocomplete="new-password" placeholder="At least 8 chars (A-Z, a-z, 0-9, symbol)" pattern="(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':&quot;\\|,.<>\/?~` ]).{8,}" minlength="8" title="Password must be at least 8 characters long with uppercase, lowercase, number, and special symbol." required>
                        <button class="password-toggle" type="button" aria-label="Show password" aria-pressed="false" aria-controls="password">Show</button>
                    </div>
                </div>

                <fieldset class="auth-fieldset">
                    <legend>Favorite fandoms</legend>
                    <div class="auth-chips">
                        <?php foreach ($cats as $c) { ?>
                            <label class="auth-chip">
                                <input type="checkbox" name="favorites[]" value="<?php echo e($c['slug']); ?>">
                                <span><?php echo e($c['name']); ?></span>
                            </label>
                        <?php } ?>
                    </div>
                    <p class="auth-hint">Optional. You can change these any time from your profile.</p>
                </fieldset>

                <div class="auth-2col">
                    <div class="form-row">
                        <label for="theme">Theme</label>
                        <select id="theme" name="theme">
                            <option value="dark">Dark</option>
                            <option value="light">Light</option>
                        </select>
                    </div>
                    <div class="form-row">
                        <label for="font_size">Font size</label>
                        <select id="font_size" name="font_size">
                            <option value="sm">Small</option>
                            <option value="md" selected>Medium</option>
                            <option value="lg">Large</option>
                        </select>
                    </div>
                </div>

                <button class="btn auth-submit" type="submit">Create account</button>
            </form>

            <div class="auth-divider"><span>or</span></div>

            <div class="login-links auth-links">
                <a class="btn-ghost auth-alt-btn" href="<?php echo e(site_url('/login.php' . (isset($_GET['next']) && $_GET['next'] !== '' ? '?next=' . rawurlencode($_GET['next']) : ''))); ?>">Already have an account? Log in</a>
                <a class="btn-ghost auth-alt-btn" href="<?php echo e(site_url('/index.php')); ?>">Back to Home</a>
            </div>
        </div>
    </div>

    <aside class="auth-art" aria-hidden="true">
        <div class="auth-art-media"<?php echo $join_art === '' ? '' : ' style="background-image:url(\'' . e($join_art) . '\')"'; ?>></div>
        <div class="auth-art-body">
            <span class="auth-art-chip">Comics &middot; Marvel</span>
            <blockquote class="auth-art-quote">One account. Eight fandoms.</blockquote>
            <p class="auth-art-caption">Register once, then bookmark, rate and submit across every category on the site.</p>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>

