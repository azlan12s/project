<?php
require_once __DIR__ . '/includes/functions.php';
ensure_srs_tables();

$user = current_user();
$cats = categories();
$category_slug = isset($_GET['category']) ? trim($_GET['category']) : '';
$selected_cat = $category_slug !== '' ? category_by_slug($category_slug) : null;

$voter_key = $user ? ('user_' . (int) $user['id']) : ('sess_' . substr(session_id(), 0, 32));

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['vote_poll'])) {
    csrf_check();
    $poll_id = (int) $_POST['poll_id'];
    $opt = (int) $_POST['selected_option'];
    $poll_row = db_one("SELECT * FROM fandom_polls WHERE id = $poll_id AND status = 'open' LIMIT 1");
    if ($poll_row && $opt >= 1 && $opt <= 4) {
        $opt_col = 'option_' . $opt;
        if (trim((string) $poll_row[$opt_col]) !== '') {
            $uid_sql = $user ? (int) $user['id'] : 'NULL';
            $vk_sql = esc($voter_key);
            db_query("INSERT INTO fandom_poll_votes (poll_id, user_id, voter_key, selected_option, created_at) VALUES ($poll_id, $uid_sql, '$vk_sql', $opt, NOW()) ON DUPLICATE KEY UPDATE selected_option = $opt, created_at = NOW()");
            if ($user) {
                log_activity((int) $user['id'], 'poll_vote', $poll_row['question']);
            }
            flash_set('ok', 'Your vote has been recorded!');
        }
    }
    redirect('/polls.php' . ($category_slug !== '' ? '?category=' . rawurlencode($category_slug) : ''));
}

$where = '1=1';
if ($selected_cat) {
    $where .= ' AND fandom_polls.category_id = ' . (int) $selected_cat['id'];
}

$polls = db_all("SELECT fandom_polls.*, categories.name AS category_name, categories.slug AS category_slug FROM fandom_polls JOIN categories ON categories.id = fandom_polls.category_id WHERE $where ORDER BY fandom_polls.id DESC");

$page_title = 'Fandom Polls & Versus Battles';
require __DIR__ . '/includes/header.php';
?>
<section class="wrap section">
    <div class="section-head">
        <div>
            <p class="eyebrow">Community Voice</p>
            <h1>Fandom Polls &amp; Versus Battles</h1>
            <p class="lede">Vote on the biggest debates across Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, and Cosplay. Every vote updates live community standings and earns Fan XP.</p>
        </div>
        <a class="btn-ghost" href="<?php echo e(site_url('/quiz.php')); ?>">Play Fandom Trivia &rarr;</a>
    </div>

    <div style="display:flex;flex-wrap:wrap;gap:8px;margin-bottom:24px">
        <a class="chip <?php echo $category_slug === '' ? 'active' : ''; ?>" href="<?php echo e(site_url('/polls.php')); ?>">All Polls</a>
        <?php foreach ($cats as $c) { ?>
            <a class="chip <?php echo $category_slug === $c['slug'] ? 'active' : ''; ?>" href="<?php echo e(site_url('/polls.php?category=' . rawurlencode($c['slug']))); ?>"><?php echo e($c['name']); ?></a>
        <?php } ?>
    </div>

    <div class="grid" style="grid-template-columns: repeat(auto-fit, minmax(340px, 1fr)); gap: 22px;">
        <?php foreach ($polls as $p) {
            $pid = (int) $p['id'];
            $vk_esc = esc($voter_key);
            $my_vote_row = db_one("SELECT selected_option FROM fandom_poll_votes WHERE poll_id = $pid AND voter_key = '$vk_esc' LIMIT 1");
            $my_vote = $my_vote_row ? (int) $my_vote_row['selected_option'] : 0;

            $vote_rows = db_all("SELECT selected_option, COUNT(*) AS c FROM fandom_poll_votes WHERE poll_id = $pid GROUP BY selected_option");
            $counts = array(1 => 0, 2 => 0, 3 => 0, 4 => 0);
            $total_votes = 0;
            foreach ($vote_rows as $vr) {
                $idx = (int) $vr['selected_option'];
                if ($idx >= 1 && $idx <= 4) {
                    $counts[$idx] = (int) $vr['c'];
                    $total_votes += (int) $vr['c'];
                }
            }
            $options = array();
            for ($k = 1; $k <= 4; $k++) {
                $txt = trim((string) $p['option_' . $k]);
                if ($txt !== '') {
                    $options[$k] = $txt;
                }
            }
        ?>
        <article class="card card-body">
            <div style="display:flex; justify-content:space-between; align-items:center; gap:8px; margin-bottom:10px;">
                <a class="chip" href="<?php echo e(category_url($p['category_slug'])); ?>"><?php echo e($p['category_name']); ?></a>
                <span class="meta"><?php echo (int) $total_votes; ?> vote<?php echo $total_votes === 1 ? '' : 's'; ?> · <?php echo e($p['status']); ?></span>
            </div>
            <h3 style="margin-top:0; font-size:1.15rem;"><?php echo e($p['question']); ?></h3>

            <form method="post" style="margin-top:14px;">
                <input type="hidden" name="csrf" value="<?php echo e(csrf_token()); ?>">
                <input type="hidden" name="vote_poll" value="1">
                <input type="hidden" name="poll_id" value="<?php echo $pid; ?>">

                <div style="display:flex; flex-direction:column; gap:10px;">
                    <?php foreach ($options as $opt_num => $opt_label) {
                        $cnt = $counts[$opt_num];
                        $pct = $total_votes > 0 ? round(($cnt / $total_votes) * 100) : 0;
                        $is_mine = ($my_vote === $opt_num);
                    ?>
                    <label style="display:block; padding:10px 12px; border-radius:10px; border:1px solid <?php echo $is_mine ? 'var(--accent)' : 'var(--line)'; ?>; cursor:pointer;">
                        <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
                            <span style="display:flex; align-items:center; gap:8px;">
                                <?php if ($p['status'] === 'open') { ?>
                                    <input type="radio" name="selected_option" value="<?php echo (int) $opt_num; ?>" <?php echo $is_mine ? 'checked' : ''; ?> required>
                                <?php } ?>
                                <strong><?php echo e($opt_label); ?></strong>
                                <?php if ($is_mine) { ?><span class="chip" style="padding:2px 8px; font-size:0.75rem;">Your Pick</span><?php } ?>
                            </span>
                            <span class="meta"><?php echo (int) $pct; ?>% (<?php echo (int) $cnt; ?>)</span>
                        </div>
                        <div style="height:6px; border-radius:99px; background:var(--line); overflow:hidden; margin-top:8px;">
                            <div style="height:100%; width:<?php echo (int) $pct; ?>%; background:var(--accent, #ec4899);"></div>
                        </div>
                    </label>
                    <?php } ?>
                </div>

                <?php if ($p['status'] === 'open') { ?>
                    <div style="margin-top:14px; display:flex; justify-content:space-between; align-items:center;">
                        <button class="btn" type="submit"><?php echo $my_vote > 0 ? 'Update Vote' : 'Cast Vote'; ?></button>
                        <?php if ($my_vote > 0) { ?><span class="meta">✓ Vote counted</span><?php } ?>
                    </div>
                <?php } else { ?>
                    <p class="meta" style="margin-top:12px;">This poll is closed. Final standings are displayed above.</p>
                <?php } ?>
            </form>
        </article>
        <?php } ?>
    </div>
    <?php if (!$polls) { ?>
        <p class="meta">No polls found for this fandom filter yet.</p>
    <?php } ?>
</section>
<?php require __DIR__ . '/includes/footer.php'; ?>
