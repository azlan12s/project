<?php
require_once __DIR__ . '/includes/functions.php';
ensure_srs_tables();

$user = current_user();
$cats = categories();
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$selected_cat = $category_slug !== '' ? category_by_slug($category_slug) : null;

$where = '1=1';
if ($selected_cat) {
    $where = 'quiz_questions.category_id = ' . (int) $selected_cat['id'];
}

$questions = db_all("SELECT quiz_questions.*, categories.name AS category_name, categories.slug AS category_slug FROM quiz_questions JOIN categories ON categories.id = quiz_questions.category_id WHERE $where ORDER BY quiz_questions.id ASC LIMIT 8");

$quiz_result = null;
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['submit_quiz'])) {
    csrf_check();
    $answers = isset($_POST['ans']) && is_array($_POST['ans']) ? $_POST['ans'] : array();
    $score = 0;
    $xp_earned = 0;
    $breakdown = array();

    foreach ($questions as $q) {
        $qid = (int) $q['id'];
        $picked = isset($answers[$qid]) ? strtoupper(trim($answers[$qid])) : '';
        $correct = strtoupper(trim($q['correct_option']));
        $is_right = ($picked !== '' && $picked === $correct);
        if ($is_right) {
            $score++;
            $xp_earned += (int) $q['points'];
        }
        $breakdown[$qid] = array(
            'picked'  => $picked,
            'correct' => $correct,
            'right'   => $is_right,
        );
    }

    $total_q = count($questions);
    if ($user && $total_q > 0) {
        $uid = (int) $user['id'];
        $cid_sql = $selected_cat ? (int) $selected_cat['id'] : 0;
        db_query("INSERT INTO quiz_attempts (user_id, category_id, score, total_questions, xp_earned, created_at) VALUES ($uid, $cid_sql, $score, $total_q, $xp_earned, NOW())");
        log_activity($uid, 'quiz_attempt', ($selected_cat ? $selected_cat['name'] : 'All Fandoms') . " ($score/$total_q · +{$xp_earned} XP)");
    }

    $quiz_result = array(
        'score'     => $score,
        'total'     => $total_q,
        'xp_earned' => $xp_earned,
        'breakdown' => $breakdown,
    );
}

$fan_profile = $user ? user_fan_profile((int) $user['id']) : null;

// Community Leaderboard (Top Fans by Quiz & Platform XP)
$all_users = db_all("SELECT id, name, role, favorite_categories FROM users ORDER BY id ASC LIMIT 20");
$leaderboard = array();
foreach ($all_users as $u) {
    $prof = user_fan_profile((int) $u['id']);
    $leaderboard[] = array(
        'name'          => $u['name'],
        'rank'          => $prof['rank'],
        'xp'            => $prof['xp'],
        'quiz_attempts' => $prof['quiz_attempts'],
        'favorites'     => $u['favorite_categories'],
    );
}
usort($leaderboard, function ($a, $b) {
    return $b['xp'] - $a['xp'];
});

$page_title = 'Fandom Trivia & XP Arena';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Interactive Fandom Challenge</p>
            <h1>Fandom Trivia &amp; XP Arena</h1>
            <p class="lede">Test your lore knowledge across Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, and Cosplay to earn Fan XP and climb the community leaderboard.</p>
        </div>
        <a class="btn-ghost" href="#leaderboard">Leaderboard &darr;</a>
    </div>

    <?php if ($fan_profile) { ?>
    <div class="grid grid-4" style="margin-bottom: 24px;">
        <div class="stat card card-body">
            <span class="meta">Your Fan Rank</span>
            <b><?php echo e($fan_profile['rank']); ?></b>
        </div>
        <div class="stat card card-body">
            <span class="meta">Total Fan XP</span>
            <b><?php echo (int) $fan_profile['xp']; ?> XP</b>
        </div>
        <div class="stat card card-body">
            <span class="meta">Quizzes Completed</span>
            <b><?php echo (int) $fan_profile['quiz_attempts']; ?></b>
        </div>
        <div class="stat card card-body">
            <span class="meta">Next Rank Target</span>
            <b><?php echo e($fan_profile['next_rank']); ?> (<?php echo (int) $fan_profile['next_xp']; ?> XP)</b>
        </div>
    </div>
    <?php } else { ?>
    <p class="meta" style="margin-bottom: 18px;">Playing as Visitor — <a href="<?php echo e(site_url('/login.php?next=' . rawurlencode('/quiz.php'))); ?>">Log in</a> to save your Quiz XP and rank on the Community Leaderboard!</p>
    <?php } ?>

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px">
        <a class="chip <?php echo $category_slug === '' ? 'active' : ''; ?>" href="<?php echo e(site_url('/quiz.php')); ?>">All Fandoms Mix</a>
        <?php foreach ($cats as $c) { ?>
            <a class="chip <?php echo $category_slug === $c['slug'] ? 'active' : ''; ?>" href="<?php echo e(site_url('/quiz.php?category=' . rawurlencode($c['slug']))); ?>"><?php echo e($c['name']); ?></a>
        <?php } ?>
    </div>

    <?php if ($quiz_result) { ?>
    <div class="card card-body" style="margin-bottom: 28px; border-color: var(--accent);">
        <span class="badge">Quiz Completed</span>
        <h2 style="margin: 10px 0 6px;">You scored <?php echo (int) $quiz_result['score']; ?> / <?php echo (int) $quiz_result['total']; ?> (+<?php echo (int) $quiz_result['xp_earned']; ?> Fan XP)</h2>
        <p class="meta">
            <?php if ($quiz_result['score'] === $quiz_result['total']) { ?>
                Perfect score! You are a true Fandom Grandmaster.
            <?php } elseif ($quiz_result['score'] >= ceil($quiz_result['total'] * 0.6)) { ?>
                Great run! Review the lore explanations below to master every detail.
            <?php } else { ?>
                Good effort! Explore our category spotlights and articles to level up your lore knowledge.
            <?php } ?>
        </p>
        <div style="margin-top: 12px; display:flex; gap:10px; flex-wrap:wrap;">
            <a class="btn" href="<?php echo e(site_url('/quiz.php' . ($category_slug !== '' ? '?category=' . rawurlencode($category_slug) : ''))); ?>">Play Again</a>
            <a class="btn-ghost" href="<?php echo e(site_url('/polls.php')); ?>">Vote in Fandom Polls</a>
        </div>
    </div>
    <?php } ?>

    <form method="post">
        <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
        <input type="hidden" name="submit_quiz" value="1">
        <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(320px, 1fr)); gap: 20px;">
            <?php foreach ($questions as $idx => $q) {
                $qid = (int) $q['id'];
                $res = ($quiz_result && isset($quiz_result['breakdown'][$qid])) ? $quiz_result['breakdown'][$qid] : null;
                $opts = array(
                    'A' => $q['option_a'],
                    'B' => $q['option_b'],
                    'C' => $q['option_c'],
                    'D' => $q['option_d'],
                );
            ?>
            <article class="card card-body">
                <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:10px;">
                    <span class="chip"><?php echo e($q['category_name']); ?></span>
                    <span class="meta">+<?php echo (int) $q['points']; ?> XP</span>
                </div>
                <h3 style="margin-top:0; font-size:1.05rem;">Q<?php echo (int) ($idx + 1); ?>. <?php echo e($q['question']); ?></h3>
                <div style="display:flex; flex-direction:column; gap:8px; margin: 12px 0;">
                    <?php foreach ($opts as $letter => $text) {
                        $checked = ($res && $res['picked'] === $letter) ? 'checked' : '';
                        $highlight = '';
                        if ($res) {
                            if ($letter === $res['correct']) {
                                $highlight = 'border: 1px solid #22c55e; background: rgba(34, 197, 94, 0.12);';
                            } elseif ($letter === $res['picked'] && !$res['right']) {
                                $highlight = 'border: 1px solid #ef4444; background: rgba(239, 68, 68, 0.12);';
                            }
                        }
                    ?>
                    <label style="display:flex; align-items:center; gap:10px; padding:10px 12px; border-radius:10px; border:1px solid var(--line); cursor:pointer; <?php echo $highlight; ?>">
                        <input type="radio" name="ans[<?php echo $qid; ?>]" value="<?php echo e($letter); ?>" <?php echo $checked; ?> required>
                        <span><strong><?php echo e($letter); ?>.</strong> <?php echo e($text); ?></span>
                    </label>
                    <?php } ?>
                </div>
                <?php if ($res) { ?>
                    <p class="meta" style="margin-top:10px; padding-top:10px; border-top:1px solid var(--line);">
                        <strong><?php echo $res['right'] ? '✓ Correct!' : '✗ Correct Answer: ' . e($res['correct']); ?></strong> — <?php echo e($q['explanation']); ?>
                    </p>
                <?php } ?>
            </article>
            <?php } ?>
        </div>
        <?php if ($questions && !$quiz_result) { ?>
            <div style="margin-top: 24px;">
                <button class="btn" type="submit">Submit Answers &amp; Claim XP &rarr;</button>
            </div>
        <?php } ?>
    </form>
</section>

<section class="wrap section" id="leaderboard">
    <div class="section-head">
        <div>
            <h2>Community Fan XP Leaderboard</h2>
            <p class="meta">XP is earned across quizzes, community article submissions, 5-star reviews, event RSVPs, poll votes, and bookmarks.</p>
        </div>
    </div>
    <div class="card card-body">
        <table class="table">
            <thead>
                <tr>
                    <th>#</th>
                    <th>Fan Member</th>
                    <th>Rank Badge</th>
                    <th>Quizzes</th>
                    <th>Total XP</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($leaderboard as $i => $row) { ?>
                <tr>
                    <td><strong>#<?php echo (int) ($i + 1); ?></strong></td>
                    <td><?php echo e($row['name']); ?></td>
                    <td><span class="chip"><?php echo e($row['rank']); ?></span></td>
                    <td><?php echo (int) $row['quiz_attempts']; ?></td>
                    <td><strong><?php echo (int) $row['xp']; ?> XP</strong></td>
                </tr>
                <?php } ?>
            </tbody>
        </table>
    </div>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
