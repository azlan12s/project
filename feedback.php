<?php
require_once __DIR__ . '/includes/functions.php';

// Only registered (logged in) users can submit feedback.
if (!is_logged_in()) {
    flash_set('error', 'Please log in to submit feedback.');
    redirect('/login.php?next=' . rawurlencode('/feedback.php'));
}

$user = current_user();
$error = '';
$selected_type = '';
$feedback_message = '';
$types = array('bug' => 'Bug report', 'suggestion' => 'Suggestion', 'query' => 'Question');

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $type = isset($_POST['type']) ? $_POST['type'] : '';
    $message = trim(isset($_POST['message']) ? $_POST['message'] : '');
    $selected_type = $type;
    $feedback_message = $message;
    if (!isset($types[$type]) || strlen($message) < 10 || strlen($message) > 5000) {
        $error = 'Please select a feedback type and provide a message with at least 10 characters.';
    } else {
        $user_id = $user ? (int) $user['id'] : 'NULL';
        $type_sql = esc($type);
        $message_sql = esc($message);
        db_query("INSERT INTO feedback (user_id, type, message, status, created_at) VALUES ($user_id, '$type_sql', '$message_sql', 'open', NOW())");
        if ($user) {
            log_activity((int) $user['id'], 'feedback', $type);
        }
        flash_set('ok', 'Thanks! Your feedback has been sent to our team.');
        redirect('/feedback.php');
    }
}

$page_title = 'Feedback';
$hide_navbar = true;
$hide_footer = true;
require __DIR__ . '/includes/header.php';
$feedback_art = is_file(__DIR__ . '/assets/images/johnwick.webp') ? site_url('/assets/images/johnwick.webp') : '';
?>
<section class="auth-split feedback-split" aria-label="Send feedback">
    <div class="auth-panel">
        <div class="auth-box">
            <a class="auth-brand" href="<?php echo e(site_url('/index.php')); ?>">
                <img class="auth-brand-mark" src="<?php echo e(site_url('/assets/images/logo1.png')); ?>" alt="">
                <span class="auth-brand-text">
                    <b>Fan Hub Plus</b>
                    <small>Eight fandoms. One hub.</small>
                </span>
            </a>

            <h1>Send feedback</h1>
            <p class="auth-lede">Report a problem, suggest an improvement, or ask us a question.</p>

            <?php if ($error) { ?>
                <p class="flash flash-error auth-alert" role="alert"><?php echo e($error); ?></p>
            <?php } ?>

            <form class="auth-form" method="post">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <div class="form-row">
                    <label for="feedback-type">Feedback type</label>
                    <select id="feedback-type" name="type" required>
                        <option value="">Choose a type</option>
                        <?php foreach ($types as $value => $label) { ?>
                            <option value="<?php echo e($value); ?>" <?php echo $selected_type === $value ? 'selected' : ''; ?>><?php echo e($label); ?></option>
                        <?php } ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="feedback-message">Message</label>
                    <textarea id="feedback-message" name="message" rows="6" maxlength="5000" minlength="10" placeholder="Write your detailed feedback here (at least 10 characters)..." required><?php echo e($feedback_message); ?></textarea>
                </div>
                <button class="btn auth-submit" type="submit">Send feedback</button>
            </form>

            <div class="auth-divider"><span>THANK YOU</span></div>
            <p class="auth-lede feedback-note">Your feedback helps us make Fan Hub Plus better for every fandom.</p>
            <div class="login-links auth-links" style="margin-top:16px;">
                <a class="btn-ghost auth-alt-btn" href="<?php echo e(site_url('/index.php')); ?>">&larr; Back to Home</a>
            </div>
        </div>
    </div>

    <aside class="auth-art" aria-hidden="true">
        <div class="auth-art-media"<?php echo $feedback_art === '' ? '' : ' style="background-image:url(\'' . e($feedback_art) . '\')"'; ?>></div>
        <div class="auth-art-body">
            <span class="auth-art-chip">FAN HUB PLUS · FEEDBACK</span>
            <blockquote class="auth-art-quote">Every great community gets better by listening.</blockquote>
            <p class="auth-art-caption">Tell us what is working, what is not, and what you would love to see next.</p>
        </div>
    </aside>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
