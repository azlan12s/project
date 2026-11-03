<?php
require_once __DIR__ . '/includes/functions.php';
require_login();
$user = current_user();
$error = '';
$cats = categories();
$form = array(
    'title' => '',
    'body' => '',
    'category_id' => '',
    'genre' => '',
    'release_year' => date('Y'),
);

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    csrf_check();
    $form['title'] = trim(isset($_POST['title']) ? $_POST['title'] : '');
    $form['body'] = trim(isset($_POST['body']) ? $_POST['body'] : '');
    $form['category_id'] = isset($_POST['category_id']) ? (int) $_POST['category_id'] : '';
    $form['genre'] = trim(isset($_POST['genre']) ? $_POST['genre'] : '');
    $form['release_year'] = trim(isset($_POST['release_year']) ? $_POST['release_year'] : '');
    $title = $form['title'];
    $body = $form['body'];
    $category_id = (int) $form['category_id'];
    $genre = $form['genre'];
    $year = $form['release_year'] === '' ? (int) date('Y') : (int) $form['release_year'];
    $valid_category = false;
    foreach ($cats as $category) {
        if ((int) $category['id'] === $category_id) {
            $valid_category = true;
            break;
        }
    }
    if ($title === '' || $body === '' || !$valid_category) {
        $error = 'Please add a title and article, then choose a valid category.';
    } elseif (strlen($title) > 200 || strlen($genre) > 80) {
        $error = 'Keep the title under 200 characters and genre under 80 characters.';
    } elseif ($year < 1900 || $year > (int) date('Y') + 5) {
        $error = 'Please enter a valid release year.';
    } else {
        $thumb = upload_file('thumbnail', 'articles');
        $title_sql = esc($title);
        $slug = strtolower(preg_replace('/[^a-z0-9]+/i', '-', $title));
        $slug = esc(trim($slug, '-'));
        $body_sql = esc($body);
        $genre_sql = esc($genre);
        $thumb_sql = esc($thumb);
        $uid = (int) $user['id'];
        db_query("INSERT INTO contents (user_id, category_id, title, slug, type, genre, body, media_url, thumbnail, release_year, popularity_score, featured, views, status, created_at) VALUES ($uid, $category_id, '$title_sql', '$slug', 'article', '$genre_sql', '$body_sql', '', '$thumb_sql', $year, 10, 0, 0, 'pending', NOW())");
        log_activity($uid, 'article_submit', $title);
        flash_set('ok', 'Article submitted. An admin will moderate it.');
        redirect('/articles.php');
    }
}

$page_title = 'Submit your article';
$hide_navbar = true;
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Community writing</p>
            <h1>Create your own article</h1>
            <p class="lede">Write about the worlds and characters you love. Your article will be reviewed before it appears publicly.</p>
        </div>
        <a class="article-back-btn" href="<?php echo e(site_url('/articles.php')); ?>">
            <span class="article-back-icon" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="M19 12H5m6 6-6-6 6-6" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round"/></svg>
            </span>
            <span>Back to articles</span>
        </a>
    </div>

    <?php if ($error) { ?><p class="flash flash-error" role="alert"><?php echo e($error); ?></p><?php } ?>

    <div class="article-submit-backdrop">
        <form class="article-submit-form" method="post" enctype="multipart/form-data">
            <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
            <header class="article-form-heading">
                <span class="article-form-kicker">Submit article</span>
                <h2>Share your take</h2>
                <p>Fill in the details below to submit your article to the community hub.</p>
            </header>
            <div class="article-meta-grid">
                <div class="form-row article-field-wide">
                    <label for="article-title">Article title</label>
                    <input id="article-title" name="title" maxlength="200" placeholder="Give your story a clear, memorable title" value="<?php echo e($form['title']); ?>" required>
                </div>
                <div class="form-row">
                    <label for="article-category">Fandom</label>
                    <select id="article-category" name="category_id" required>
                        <option value="">Choose a fandom</option>
                        <?php foreach ($cats as $c) { ?><option value="<?php echo (int) $c['id']; ?>" <?php echo (int) $form['category_id'] === (int) $c['id'] ? 'selected' : ''; ?>><?php echo e($c['name']); ?></option><?php } ?>
                    </select>
                </div>
                <div class="form-row">
                    <label for="article-genre">Genre <span class="article-optional">Optional</span></label>
                    <input id="article-genre" name="genre" maxlength="80" placeholder="Review, theory, character study..." value="<?php echo e($form['genre']); ?>">
                </div>
                <div class="form-row">
                    <label for="article-year">Release year <span class="article-optional">Optional</span></label>
                    <input id="article-year" name="release_year" type="number" min="1900" max="<?php echo (int) date('Y') + 5; ?>" value="<?php echo e($form['release_year']); ?>">
                </div>
                <div class="form-row">
                    <label for="article-thumbnail">Cover image <span class="article-optional">Optional</span></label>
                    <input id="article-thumbnail" type="file" name="thumbnail" accept="image/jpeg,image/png,image/webp,image/gif">
                    <small class="article-field-note">Choose an image to give your article its own cover.</small>
                </div>
                <div class="form-row article-field-wide">
                    <label for="article-body">Your article</label>
                    <textarea id="article-body" name="body" rows="10" placeholder="Start with the idea you cannot stop thinking about..." required><?php echo e($form['body']); ?></textarea>
                </div>
            </div>
            <div class="article-form-footer">
                <span><span class="article-review-dot"></span> Submitted to the admin review queue</span>
                <button class="btn article-submit-button" type="submit">Submit for review <span aria-hidden="true">&rarr;</span></button>
            </div>
        </form>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
