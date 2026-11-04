<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/db.php';

function e($value)
{
    return htmlspecialchars((string) $value, ENT_QUOTES, 'UTF-8');
}

function site_base_path()
{
    static $base_path = null;
    if ($base_path !== null) {
        return $base_path;
    }

    $project_root = realpath(__DIR__ . '/..');
    $document_root = isset($_SERVER['DOCUMENT_ROOT']) ? realpath($_SERVER['DOCUMENT_ROOT']) : false;
    if ($project_root !== false && $document_root !== false) {
        $project_root = str_replace('\\', '/', $project_root);
        $document_root = rtrim(str_replace('\\', '/', $document_root), '/');
        if ($project_root === $document_root) {
            $base_path = '';
        } elseif (strpos($project_root, $document_root . '/') === 0) {
            $base_path = substr($project_root, strlen($document_root));
        } else {
            $base_path = '';
        }
        return $base_path;
    }

    $script_name = isset($_SERVER['SCRIPT_NAME']) ? str_replace('\\', '/', $_SERVER['SCRIPT_NAME']) : '';
    $admin_position = strpos($script_name, '/admin/');
    $base_path = $admin_position === false ? dirname($script_name) : substr($script_name, 0, $admin_position);
    $base_path = $base_path === '/' || $base_path === '.' ? '' : rtrim($base_path, '/');
    return $base_path;
}

function site_url($path = '')
{
    $path = (string) $path;
    if ($path === '' || $path[0] === '#' || preg_match('~^(?:https?:)?//~i', $path)) {
        return $path;
    }
    $base_path = site_base_path();
    $absolute_path = '/' . ltrim($path, '/');
    if ($base_path !== '' && ($absolute_path === $base_path || strpos($absolute_path, $base_path . '/') === 0)) {
        return $absolute_path;
    }
    return $base_path . $absolute_path;
}

function esc($value)
{
    global $conn;
    return mysqli_real_escape_string($conn, (string) $value);
}

function db_escape($value)
{
    return esc($value);
}


function card($opts = array())
{
    $href       = site_url(isset($opts['href']) ? $opts['href'] : '#');
    $image      = card_image_with_fallback(
        isset($opts['image']) ? $opts['image'] : '',
        (isset($opts['category_slug']) ? $opts['category_slug'] : '') . ' ' .
        (isset($opts['badge']) ? $opts['badge'] : '') . ' ' .
        (isset($opts['meta']) ? $opts['meta'] : '')
    );
    $title      = isset($opts['title']) ? $opts['title'] : '';
    $desc       = isset($opts['desc']) ? $opts['desc'] : '';
    $badge      = isset($opts['badge']) ? $opts['badge'] : '';
    $badge_href = isset($opts['badge_href']) && $opts['badge_href'] !== '' ? site_url($opts['badge_href']) : '';
    $meta       = isset($opts['meta']) ? $opts['meta'] : '';
    $cta        = isset($opts['cta']) ? $opts['cta'] : 'Read more';
    $cta_href   = site_url(isset($opts['cta_href']) ? $opts['cta_href'] : $href);
    $soon       = !empty($opts['cta_soon']);
    $cover_cls  = isset($opts['cover_class']) ? $opts['cover_class'] : '';
    $card_cls   = isset($opts['class']) ? $opts['class'] : '';

    $classes = 'card' . ($card_cls !== '' ? ' ' . $card_cls : '');

    echo '<article class="' . e($classes) . '">';

    if ($badge !== '') {
        echo '<div class="card-top">';
        if ($badge_href !== '') {
            echo '<a class="card-badge" href="' . e($badge_href) . '">' . e($badge) . '</a>';
        } else {
            echo '<span class="card-badge">' . e($badge) . '</span>';
        }
        echo '</div>';
    }

    echo '<a class="cover' . ($cover_cls !== '' ? ' ' . $cover_cls : '') . '" href="' . e($href) . '"'
        . ($image !== '' ? ' style="background-image:url(\'' . e($image) . '\')"' : '')
        . ' aria-label="' . e($title) . '"></a>';

    echo '<div class="card-body">';
    echo '<h3 class="card-title"><a href="' . e($href) . '">' . e($title) . '</a></h3>';

    if ($desc !== '') {
        echo '<p class="card-desc">' . e($desc) . '</p>';
    } elseif ($meta !== '') {


        echo '<p class="card-desc"></p>';
    }

    if ($meta !== '') {
        echo '<p class="card-meta">' . e($meta) . '</p>';
    }

    if ($soon) {
        echo '<span class="card-cta is-soon">' . e($cta) . '</span>';
    } else {
        echo '<a class="card-cta" href="' . e($cta_href) . '">' . e($cta)
            . ' <span aria-hidden="true">&rarr;</span></a>';
    }

    echo '</div>';
    echo '</article>';
}


function card_image_with_fallback($image, $category_hint = '')
{
    static $fixed_thumbs = false;
    if (!$fixed_thumbs) {
        $fixed_thumbs = true;
        db_query("UPDATE contents SET thumbnail = '/assets/images/spotlight/tv-shows/2throne.webp' WHERE title LIKE 'Binge Pacing%' AND thumbnail <> '/assets/images/spotlight/tv-shows/2throne.webp'");
    }
    $image = trim((string) $image);
    if ($image !== '') {
        if (preg_match('~^(https?:)?//~i', $image)) {
            return $image;
        }
        $decoded = rawurldecode($image);
        $base = site_base_path();
        if ($base !== '' && strpos($decoded, $base . '/') === 0) {
            $decoded = substr($decoded, strlen($base));
        }
        $relative = ltrim($decoded, '/');
        $local_path = __DIR__ . '/../' . str_replace('/', DIRECTORY_SEPARATOR, $relative);
        if (is_file($local_path)) {
            return str_replace(' ', '%20', site_url('/' . $relative));
        }
    }

    $hint = strtolower(trim((string) $category_hint));
    $hint = preg_replace('/[^a-z0-9]+/', '-', $hint);
    $slugs = array('anime', 'gaming', 'movies', 'tv-shows', 'k-pop', 'comics', 'manga', 'cosplay');
    foreach ($slugs as $slug) {
        if (strpos('-' . $hint . '-', '-' . $slug . '-') !== false) {
            return category_banner_image($slug);
        }
    }
    return category_banner_image('anime');
}

function db_query($sql)
{
    global $conn;
    try {
        $result = mysqli_query($conn, $sql);
        if ($result === false) {
            return false;
        }
        return $result;
    } catch (\Throwable $e) {
        return false;
    }
}

function db_all($sql)
{
    $result = db_query($sql);
    $rows = array();
    if ($result) {
        while ($row = mysqli_fetch_assoc($result)) {
            $rows[] = $row;
        }
        mysqli_free_result($result);
    }
    return $rows;
}

function db_one($sql)
{
    $rows = db_all($sql);
    if (count($rows) > 0) {
        return $rows[0];
    }
    return null;
}

function db_insert_id()
{
    global $conn;
    return mysqli_insert_id($conn);
}

function current_user()
{
    if (!isset($_SESSION['user_id'])) {
        return null;
    }
    $id = (int) $_SESSION['user_id'];
    return db_one("SELECT * FROM users WHERE id = $id LIMIT 1");
}

function is_logged_in()
{
    return isset($_SESSION['user_id']);
}

function is_admin()
{
    $user = current_user();
    return $user && $user['role'] === 'admin';
}

function prevent_browser_cache()
{
    if (!headers_sent()) {
        header('Cache-Control: no-store, no-cache, must-revalidate, max-age=0, post-check=0, pre-check=0');
        header('Pragma: no-cache');
        header('Expires: Sat, 26 Jul 1997 05:00:00 GMT');
    }
}

function require_login()
{
    prevent_browser_cache();
    if (!is_logged_in()) {
        header('Location: ' . site_url('/login.php?relogin=1&next=' . urlencode($_SERVER['REQUEST_URI'])));
        exit;
    }
}

function require_admin()
{
    prevent_browser_cache();
    require_login();
    if (!is_admin()) {
        http_response_code(403);
        echo '<!DOCTYPE html><html><body style="font-family:sans-serif;padding:40px;background:#0b1020;color:#fff"><h1>Access denied</h1><p>Administrator privileges are required.</p><p><a href="' . e(site_url('/index.php')) . '" style="color:#7dd3fc">Back home</a></p></body></html>';
        exit;
    }
}

function redirect($path)
{
    header('Location: ' . site_url($path));
    exit;
}

function flash_set($type, $message)
{
    $_SESSION['flash'] = array('type' => $type, 'message' => $message);
}

function flash_get()
{
    if (!isset($_SESSION['flash'])) {
        return null;
    }
    $flash = $_SESSION['flash'];
    unset($_SESSION['flash']);
    return $flash;
}

function categories()
{
    return db_all("SELECT * FROM categories ORDER BY name ASC");
}

function category_visual_image($slug)
{
    $images = array(
        'anime' => 'https://images.unsplash.com/photo-1578632767115-351597cf2477?auto=format&fit=crop&w=1200&q=85',
        'gaming' => 'https://images.unsplash.com/photo-1542751371-adc38448a05e?auto=format&fit=crop&w=1200&q=85',
        'movies' => 'https://images.unsplash.com/photo-1489599849927-2ee91cede3ba?auto=format&fit=crop&w=1200&q=85',
        'tv-shows' => 'https://images.unsplash.com/photo-1522869635100-9f4c5e86aa37?auto=format&fit=crop&w=1200&q=85',
        'k-pop' => 'https://images.unsplash.com/photo-1493225457124-a3eb161ffa5f?auto=format&fit=crop&w=1200&q=85',
        'comics' => 'https://images.unsplash.com/photo-1612036782180-6f0b6cd846fe?auto=format&fit=crop&w=1200&q=85',
        'manga' => 'https://images.unsplash.com/photo-1544716278-ca5e3f4abd8c?auto=format&fit=crop&w=1200&q=85',
        'cosplay' => 'https://images.unsplash.com/photo-1514525253161-7a46d19cd819?auto=format&fit=crop&w=1200&q=85',
    );

    return isset($images[$slug]) ? $images[$slug] : $images['anime'];
}

function category_by_slug($slug)
{
    $slug = esc($slug);
    return db_one("SELECT * FROM categories WHERE slug = '$slug' LIMIT 1");
}

function log_activity($user_id, $action = '', $detail = '')
{
    if ($detail === '' && func_num_args() === 2) {
        $detail = (string) $action;
        $action = (string) $user_id;
        $user_id = isset($_SESSION['user_id']) ? (int) $_SESSION['user_id'] : 0;
    }
    $user_id = (int) $user_id;
    $action = esc($action);
    $detail = esc($detail);
    db_query("INSERT INTO activity_logs (user_id, action, detail, created_at) VALUES ($user_id, '$action', '$detail', NOW())");
}

function bump_views($table, $id)
{
    $id = (int) $id;
    if ($table === 'contents') {
        db_query("UPDATE contents SET views = views + 1 WHERE id = $id");
    }
    if ($table === 'merchandise_items') {
        db_query("UPDATE merchandise_items SET views = views + 1 WHERE id = $id");
    }
}

function avg_rating($content_id)
{
    $content_id = (int) $content_id;
    $row = db_one("SELECT AVG(rating) AS avg_rating, COUNT(*) AS total FROM media_ratings WHERE content_id = $content_id");
    if (!$row || $row['total'] == 0) {
        return array('avg' => 0, 'total' => 0);
    }
    return array('avg' => round((float) $row['avg_rating'], 1), 'total' => (int) $row['total']);
}

function is_bookmarked($user_id, $content_id)
{
    $user_id = (int) $user_id;
    $content_id = (int) $content_id;
    $row = db_one("SELECT id FROM bookmarks WHERE user_id = $user_id AND content_id = $content_id LIMIT 1");
    return $row ? true : false;
}

function ensure_srs_tables()
{
    static $done = false;
    if ($done) {
        return;
    }
    $done = true;
    db_query("CREATE TABLE IF NOT EXISTS chatbot_queries (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED DEFAULT NULL,
        message TEXT NOT NULL,
        response TEXT NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_cq_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS item_bookmarks (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        item_type ENUM('character','merch') NOT NULL,
        item_id INT UNSIGNED NOT NULL,
        notes TEXT,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uniq_user_item (user_id, item_type, item_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS event_rsvps (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        event_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED NOT NULL,
        status ENUM('going','interested') NOT NULL DEFAULT 'going',
        created_at DATETIME NOT NULL,
        UNIQUE KEY uniq_event_user (event_id, user_id),
        INDEX idx_rsvp_event (event_id),
        INDEX idx_rsvp_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS merch_orders (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        order_number VARCHAR(60) NOT NULL UNIQUE,
        total_amount DECIMAL(10,2) NOT NULL DEFAULT 0.00,
        payment_method VARCHAR(60) NOT NULL DEFAULT 'Cash on Delivery',
        status ENUM('pending','successful','cancelled') NOT NULL DEFAULT 'pending',
        shipping_name VARCHAR(120) NOT NULL,
        shipping_phone VARCHAR(40) NOT NULL,
        shipping_address TEXT NOT NULL,
        shipping_city VARCHAR(80) NOT NULL,
        order_notes TEXT,
        created_at DATETIME NOT NULL,
        updated_at DATETIME NOT NULL,
        INDEX idx_mo_user (user_id),
        INDEX idx_mo_status (status)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS merch_order_items (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        order_id INT UNSIGNED NOT NULL,
        merch_id INT UNSIGNED NOT NULL,
        title VARCHAR(180) NOT NULL,
        price DECIMAL(10,2) NOT NULL,
        quantity INT UNSIGNED NOT NULL DEFAULT 1,
        image VARCHAR(255) DEFAULT '',
        INDEX idx_moi_order (order_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS password_resets (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        email VARCHAR(160) NOT NULL,
        token VARCHAR(64) NOT NULL,
        created_at DATETIME NOT NULL,
        INDEX idx_pr_email (email),
        INDEX idx_pr_token (token)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS fandom_polls (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id INT UNSIGNED NOT NULL,
        question VARCHAR(255) NOT NULL,
        option_1 VARCHAR(140) NOT NULL,
        option_2 VARCHAR(140) NOT NULL,
        option_3 VARCHAR(140) DEFAULT '',
        option_4 VARCHAR(140) DEFAULT '',
        status ENUM('open','closed') NOT NULL DEFAULT 'open',
        created_at DATETIME NOT NULL,
        INDEX idx_poll_cat (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS fandom_poll_votes (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        poll_id INT UNSIGNED NOT NULL,
        user_id INT UNSIGNED DEFAULT NULL,
        voter_key VARCHAR(80) NOT NULL,
        selected_option TINYINT UNSIGNED NOT NULL,
        created_at DATETIME NOT NULL,
        UNIQUE KEY uniq_poll_voter (poll_id, voter_key),
        INDEX idx_pv_poll (poll_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS quiz_questions (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        category_id INT UNSIGNED NOT NULL,
        question VARCHAR(300) NOT NULL,
        option_a VARCHAR(160) NOT NULL,
        option_b VARCHAR(160) NOT NULL,
        option_c VARCHAR(160) NOT NULL,
        option_d VARCHAR(160) NOT NULL,
        correct_option CHAR(1) NOT NULL DEFAULT 'A',
        explanation TEXT,
        points INT UNSIGNED NOT NULL DEFAULT 20,
        INDEX idx_qq_cat (category_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    db_query("CREATE TABLE IF NOT EXISTS quiz_attempts (
        id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
        user_id INT UNSIGNED NOT NULL,
        category_id INT UNSIGNED DEFAULT NULL,
        score INT UNSIGNED NOT NULL DEFAULT 0,
        total_questions INT UNSIGNED NOT NULL DEFAULT 0,
        xp_earned INT UNSIGNED NOT NULL DEFAULT 0,
        created_at DATETIME NOT NULL,
        INDEX idx_qa_user (user_id)
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4");

    $poll_count = db_one("SELECT COUNT(*) AS c FROM fandom_polls");
    if ($poll_count && (int) $poll_count['c'] === 0) {
        $seed_polls = array(
            array(1, 'Which modern Anime finale delivered the best visual direction?', 'Attack on Titan: The Final Chapters', 'Jujutsu Kaisen: Shibuya Incident', 'Demon Slayer: Hashira Training', 'Chainsaw Man Season 1'),
            array(2, 'What makes an Open-World game truly unforgettable?', 'Vertical traversal & seamless exploration', 'Dynamic boss encounters & combat depth', 'Atmospheric soundtrack & world lore', 'Player-driven story choices'),
            array(5, 'What is the highlight of K-Pop Comeback Week for you?', 'Synchronized choreography & dance break', 'Music video visual storytelling', 'Live arena encore & lightstick ocean', 'Concept album B-side tracks'),
            array(3, 'In modern blockbuster filmmaking, which craft wins?', 'Tactile practical effects & miniatures', 'Hybrid practical + digital VFX extensions', 'Full CGI world-building', 'In-camera stunt choreography'),
            array(6, 'Which comic storytelling device hits the hardest?', 'Full double-page splash reveal', 'Tight kinetic panel-to-panel gutters', 'Silent atmospheric sequences', 'Cover art & variant editions'),
            array(8, 'Most impressive Cosplay convention craft discipline?', 'EVA foam & Worbla armor fabrication', 'Arduino RGB LED & animatronics', 'Precision wig styling & makeup FX', 'Screen-accurate prop weaponry'),
        );
        foreach ($seed_polls as $sp) {
            $cid = (int) $sp[0];
            $q = esc($sp[1]);
            $o1 = esc($sp[2]);
            $o2 = esc($sp[3]);
            $o3 = esc($sp[4]);
            $o4 = esc($sp[5]);
            db_query("INSERT INTO fandom_polls (category_id, question, option_1, option_2, option_3, option_4, status, created_at) VALUES ($cid, '$q', '$o1', '$o2', '$o3', '$o4', 'open', NOW())");
        }

        $seed_votes = array(
            array(1, 2, 'seed_u2_p1', 1),
            array(1, 1, 'seed_u1_p1', 2),
            array(1, null, 'seed_g1_p1', 1),
            array(2, 2, 'seed_u2_p2', 1),
            array(2, 1, 'seed_u1_p2', 3),
            array(3, 2, 'seed_u2_p3', 1),
            array(3, 1, 'seed_u1_p3', 3),
            array(4, 1, 'seed_u1_p4', 2),
            array(5, 2, 'seed_u2_p5', 1),
            array(6, 1, 'seed_u1_p6', 2),
        );
        foreach ($seed_votes as $sv) {
            $pid = (int) $sv[0];
            $uid_sql = $sv[1] ? (int) $sv[1] : 'NULL';
            $vk = esc($sv[2]);
            $opt = (int) $sv[3];
            db_query("INSERT IGNORE INTO fandom_poll_votes (poll_id, user_id, voter_key, selected_option, created_at) VALUES ($pid, $uid_sql, '$vk', $opt, NOW())");
        }
    }

    $quiz_count = db_one("SELECT COUNT(*) AS c FROM quiz_questions");
    if ($quiz_count && (int) $quiz_count['c'] === 0) {
        $seed_questions = array(
            array(1, 'In Attack on Titan, what elite military branch does Mikasa Ackerman join to fight Titans outside the Walls?', 'Garrison Regiment', 'Survey Corps', 'Military Police Brigade', 'Marleyan Warrior Unit', 'B', 'Mikasa graduates at the top of the 104th Training Corps and joins the Survey Corps (Scout Regiment).', 25),
            array(1, 'How long is a standard anime television opening theme (OP) broadcast sequence?', '45 seconds', '60 seconds', '90 seconds', '120 seconds', 'C', 'Traditional television anime openings are storyboarded to a strict 89–90 second ritual.', 20),
            array(2, 'In the Horizon series, what ancient technological device allows Aloy to scan machines and data points?', 'Pip-Boy', 'Focus', 'Codec', 'Omni-Tool', 'B', 'Aloy discovers a triangular earpiece called a Focus in an Old Ones ruin during her childhood.', 25),
            array(2, 'In open-world game design, what term describes using tall landmarks and vertical geometry to guide player curiosity without waypoints?', 'Traversal & sightline anchoring', 'Hitscan raycasting', 'Procedural culling', 'Frame-buffer locking', 'A', 'Landmarks and verticality create natural curiosity loops that guide players organically.', 20),
            array(3, 'In the film Aliens, what piece of industrial equipment does Ellen Ripley pilot to battle the Xenomorph Queen?', ' Dropship APC Turret', 'Caterpillar P-5000 Power Loader', 'Nostromo Escape Pod', 'Orbital Lifter Mech', 'B', 'Ripley famously uses the hydraulic P-5000 Power Loader exoskeleton in one of cinema\'s greatest practical-effects showdowns.', 25),
            array(3, 'Why do directors combine on-set practical props with digital visual effects?', 'Practical lighting and physical weight ground actors\' performances before CGI adds scale', 'CGI cannot render dark scenes', 'Practical effects require zero lighting setup', 'Film cameras cannot record green screens', 'A', 'Real light and tactile props give performers genuine physical references that blend seamlessly with digital extensions.', 20),
            array(4, 'Which boarding school does Wednesday Addams attend in the hit series Wednesday?', 'Hawkins High School', 'Nevermore Academy', 'Hexside Academy', 'Brakebills University', 'B', 'Wednesday is enrolled at Nevermore Academy in Vermont, a school for outcasts.', 20),
            array(4, 'What makes a television season finale feel emotionally earned to viewers?', 'Leaving every single plotline unresolved', 'Characters arriving at the ending changed by their choices and emotional continuity', 'Introducing a brand-new cast in the final 5 minutes', 'Skipping character arcs for shock value', 'B', 'Emotional continuity and paying off character choices make a season finale resonate.', 20),
            array(5, 'In K-Pop concert culture, what synchronized wireless item do fans wave in arenas to create glowing oceans?', 'Laser batons', 'Official Bluetooth Lightsticks', 'Neon wristbands', 'Holo-cards', 'B', 'Fandom-specific official lightsticks sync with stage production during arena tours.', 20),
            array(5, 'What role does Stage Captain Hana hold in her K-Pop group on Fan Hub Plus?', 'Main Rapper & Producer only', 'Idol Leader, Lead Vocalist & Visual Centerpiece', 'Solo Acoustic Guitarist', 'Choreographer only', 'B', 'Stage Captain Hana serves as Idol Leader, Lead Vocalist, and creative contributor.', 25),
            array(6, 'In comic book art, what is the blank space between panels that controls how readers perceive time and motion?', 'The Bleed', 'The Gutter', 'The Halftone', 'The Variant', 'B', 'The gutter is the space between panels where the reader\'s imagination connects two moments in time.', 25),
            array(6, 'Where does Bruce Wayne house his tactical computer, vehicles, and forensic laboratory?', 'The Fortress of Solitude', 'The Batcave beneath Wayne Manor', 'Arkham Watchtower', 'Wayne Enterprises Penthouse', 'B', 'Batman operates from the Batcave hidden beneath Wayne Manor.', 20),
            array(7, 'What is the Japanese term for a compiled standalone volume of manga chapters?', 'Tankobon', 'Doujinshi', 'Storyboard Name', 'Omake', 'A', 'Tankobon refers to collected standalone volumes, alongside collector formats like Kanzenban and Bunko.', 25),
            array(7, 'What is the true mythical nature of Monkey D. Luffy\'s Devil Fruit awakening in One Piece?', 'Flame Emperor Logia', 'Gear Fifth — Sun God Nika (Hito Hito no Mi, Model: Nika)', 'Shadow Sovereign Paramecia', 'Storm Dragon Zoan', 'B', 'Luffy\'s Gum-Gum Fruit awakens into Gear Fifth, revealing the mythical Sun God Nika.', 25),
            array(8, 'Which lightweight heat-formable material is the foundational staple for building convention-safe cosplay armor?', 'Cast iron', 'High-density EVA Foam & Worbla thermoplastic', 'Solid oak', 'Tempered glass', 'B', 'Cosplay artisans heat-form high-density EVA foam and Worbla for durable, lightweight convention armor.', 20),
            array(8, 'What microcontroller platform powers the addressable RGB LED conduits in the Knight of Neon cosplay suit?', 'Arduino microcontroller', 'Raspberry Pi rack server', 'Car alternator', 'CRT transformer', 'A', 'Custom Arduino microcontrollers drive the addressable RGB LED strips in the Knight of Neon armor.', 25),
        );
        foreach ($seed_questions as $sq) {
            $cid = (int) $sq[0];
            $q = esc($sq[1]);
            $oa = esc($sq[2]);
            $ob = esc($sq[3]);
            $oc = esc($sq[4]);
            $od = esc($sq[5]);
            $co = esc($sq[6]);
            $ex = esc($sq[7]);
            $pts = (int) $sq[8];
            db_query("INSERT INTO quiz_questions (category_id, question, option_a, option_b, option_c, option_d, correct_option, explanation, points) VALUES ($cid, '$q', '$oa', '$ob', '$oc', '$od', '$co', '$ex', $pts)");
        }
        db_query("INSERT INTO quiz_attempts (user_id, category_id, score, total_questions, xp_earned, created_at) VALUES (2, 1, 2, 2, 45, NOW()), (1, 0, 8, 8, 180, NOW())");
    }

    $rsvp_count = db_one("SELECT COUNT(*) AS c FROM event_rsvps");
    if ($rsvp_count && (int) $rsvp_count['c'] === 0) {
        db_query("INSERT IGNORE INTO event_rsvps (event_id, user_id, status, created_at) VALUES (1, 2, 'going', NOW()), (2, 2, 'interested', NOW()), (3, 1, 'going', NOW())");
    }
}

function fan_profile_summary($user_id)
{
    return user_fan_profile($user_id);
}

function user_fan_profile($user_id)
{
    ensure_srs_tables();
    $uid = (int) $user_id;
    $bm = db_one("SELECT (SELECT COUNT(*) FROM bookmarks WHERE user_id = $uid) + (SELECT COUNT(*) FROM item_bookmarks WHERE user_id = $uid) AS c");
    $rt = db_one("SELECT COUNT(*) AS c FROM media_ratings WHERE user_id = $uid");
    $ar = db_one("SELECT COUNT(*) AS c FROM contents WHERE user_id = $uid");
    $qz = db_one("SELECT COALESCE(SUM(xp_earned), 0) AS xp, COUNT(*) AS attempts FROM quiz_attempts WHERE user_id = $uid");
    $pv = db_one("SELECT COUNT(*) AS c FROM fandom_poll_votes WHERE user_id = $uid");
    $rv = db_one("SELECT COUNT(*) AS c FROM event_rsvps WHERE user_id = $uid");

    $bookmarks_count = $bm ? (int) $bm['c'] : 0;
    $ratings_count   = $rt ? (int) $rt['c'] : 0;
    $articles_count  = $ar ? (int) $ar['c'] : 0;
    $quiz_xp         = $qz ? (int) $qz['xp'] : 0;
    $quiz_attempts   = $qz ? (int) $qz['attempts'] : 0;
    $poll_votes      = $pv ? (int) $pv['c'] : 0;
    $rsvps_count     = $rv ? (int) $rv['c'] : 0;

    $total_xp = $quiz_xp
        + ($bookmarks_count * 10)
        + ($ratings_count * 15)
        + ($articles_count * 30)
        + ($poll_votes * 10)
        + ($rsvps_count * 15);

    if ($total_xp >= 300) {
        $rank = 'Grandmaster Otaku';
        $next_rank = 'Max Rank Achieved';
        $next_xp = 300;
    } elseif ($total_xp >= 150) {
        $rank = 'Fandom Vanguard';
        $next_rank = 'Grandmaster Otaku';
        $next_xp = 300;
    } elseif ($total_xp >= 50) {
        $rank = 'Lore Seeker';
        $next_rank = 'Fandom Vanguard';
        $next_xp = 150;
    } else {
        $rank = 'Rookie Fan';
        $next_rank = 'Lore Seeker';
        $next_xp = 50;
    }

    return array(
        'xp'              => $total_xp,
        'rank'            => $rank,
        'next_rank'       => $next_rank,
        'next_xp'         => $next_xp,
        'quiz_xp'         => $quiz_xp,
        'quiz_attempts'   => $quiz_attempts,
        'bookmarks_count' => $bookmarks_count,
        'ratings_count'   => $ratings_count,
        'articles_count'  => $articles_count,
        'poll_votes'      => $poll_votes,
        'rsvps_count'     => $rsvps_count,
    );
}

function get_recommended_contents($user, $limit = 4, $exclude_id = 0)
{
    $limit = max(1, (int) $limit);
    $exclude_id = (int) $exclude_id;
    $cat_ids = array();

    if ($user) {
        $uid = (int) $user['id'];
        $fav_slugs = favorite_slugs($user);
        if ($fav_slugs) {
            $slug_list = array();
            foreach ($fav_slugs as $s) {
                $slug_list[] = "'" . esc($s) . "'";
            }
            $rows = db_all("SELECT id FROM categories WHERE slug IN (" . implode(',', $slug_list) . ")");
            foreach ($rows as $r) {
                $cat_ids[] = (int) $r['id'];
            }
        }
        $bm_cats = db_all("SELECT DISTINCT contents.category_id FROM bookmarks JOIN contents ON contents.id = bookmarks.content_id WHERE bookmarks.user_id = $uid");
        foreach ($bm_cats as $bc) {
            $cat_ids[] = (int) $bc['category_id'];
        }
        $rt_cats = db_all("SELECT DISTINCT contents.category_id FROM media_ratings JOIN contents ON contents.id = media_ratings.content_id WHERE media_ratings.user_id = $uid AND media_ratings.rating >= 4");
        foreach ($rt_cats as $rc) {
            $cat_ids[] = (int) $rc['category_id'];
        }
    }

    $cat_ids = array_values(array_unique(array_filter($cat_ids)));
    $excl_sql = $exclude_id > 0 ? " AND contents.id <> $exclude_id" : "";

    if ($cat_ids) {
        $id_str = implode(',', $cat_ids);
        $recs = db_all("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' AND contents.category_id IN ($id_str) $excl_sql ORDER BY contents.popularity_score DESC, contents.views DESC LIMIT $limit");
        if (count($recs) >= $limit) {
            return $recs;
        }
    }

    return db_all("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.status = 'published' $excl_sql ORDER BY contents.popularity_score DESC, contents.views DESC LIMIT $limit");
}

function track_recently_viewed($content_id)
{
    $cid = (int) $content_id;
    if ($cid <= 0) {
        return;
    }
    $list = isset($_SESSION['recently_viewed']) && is_array($_SESSION['recently_viewed']) ? $_SESSION['recently_viewed'] : array();
    $list = array_values(array_diff($list, array($cid)));
    array_unshift($list, $cid);
    $_SESSION['recently_viewed'] = array_slice($list, 0, 8);
}

function get_recently_viewed($limit = 4)
{
    $limit = max(1, (int) $limit);
    $list = isset($_SESSION['recently_viewed']) && is_array($_SESSION['recently_viewed']) ? $_SESSION['recently_viewed'] : array();
    $ids = array();
    foreach (array_slice($list, 0, $limit) as $id) {
        if ((int) $id > 0) {
            $ids[] = (int) $id;
        }
    }
    if (!$ids) {
        return array();
    }
    $id_str = implode(',', $ids);
    return db_all("SELECT contents.*, categories.name AS category_name, categories.slug AS category_slug FROM contents JOIN categories ON categories.id = contents.category_id WHERE contents.id IN ($id_str) AND contents.status = 'published' ORDER BY FIELD(contents.id, $id_str) LIMIT $limit");
}

function event_rsvp_counts($event_id)
{
    ensure_srs_tables();
    $eid = (int) $event_id;
    $going = db_one("SELECT COUNT(*) AS c FROM event_rsvps WHERE event_id = $eid AND status = 'going'");
    $interested = db_one("SELECT COUNT(*) AS c FROM event_rsvps WHERE event_id = $eid AND status = 'interested'");
    return array(
        'going'      => $going ? (int) $going['c'] : 0,
        'interested' => $interested ? (int) $interested['c'] : 0,
        'total'      => ($going ? (int) $going['c'] : 0) + ($interested ? (int) $interested['c'] : 0),
    );
}

function user_event_rsvp($user_id, $event_id)
{
    ensure_srs_tables();
    $uid = (int) $user_id;
    $eid = (int) $event_id;
    if ($uid <= 0 || $eid <= 0) {
        return null;
    }
    $row = db_one("SELECT status FROM event_rsvps WHERE user_id = $uid AND event_id = $eid LIMIT 1");
    return $row ? $row['status'] : null;
}

function is_item_bookmarked($user_id, $item_type, $item_id)
{
    ensure_srs_tables();
    $user_id = (int) $user_id;
    $item_type_sql = esc($item_type);
    $item_id = (int) $item_id;
    $row = db_one("SELECT id FROM item_bookmarks WHERE user_id = $user_id AND item_type = '$item_type_sql' AND item_id = $item_id LIMIT 1");
    return $row ? true : false;
}

function ensure_upcoming_events()
{
    static $checked = false;
    if ($checked) {
        return;
    }
    $checked = true;


    db_query("UPDATE events SET event_date = '2026-11-15' WHERE title = 'Midnight Anime Screening' AND event_date < CURDATE()");
    db_query("UPDATE events SET event_date = '2026-11-22' WHERE title = 'Lightstick Night' AND event_date < CURDATE()");
    db_query("UPDATE events SET event_date = '2026-12-14' WHERE title = 'Manga Library Fair' AND event_date < CURDATE()");


    $extra_events = array(
        array(
            'cat_id' => 6,
            'title'  => 'Gotham & Multiverse Comic-Con Panel',
            'city'   => 'New York',
            'lat'    => '40.7128000',
            'lng'    => '-74.0060000',
            'date'   => '2026-11-29',
            'ticket' => 'https://example.com/tickets/ny-comiccon',
            'desc'   => 'Artist alley signings, live ink demonstrations, and superhero multiverse Q&A panels.',
            'image'  => '/assets/images/fandoms/comics.jfif',
        ),
        array(
            'cat_id' => 3,
            'title'  => 'Sci-Fi & Practical Effects Premiere Night',
            'city'   => 'Los Angeles',
            'lat'    => '34.0928000',
            'lng'    => '-118.3287000',
            'date'   => '2026-12-19',
            'ticket' => 'https://example.com/tickets/la-scifi-premiere',
            'desc'   => 'IMAX fan screening with miniature prop showcase and director commentary.',
            'image'  => '/assets/images/mo1.jfif',
        ),
        array(
            'cat_id' => 4,
            'title'  => 'Winter Finale & Animation Watch Party',
            'city'   => 'Toronto',
            'lat'    => '43.6532000',
            'lng'    => '-79.3832000',
            'date'   => '2027-01-10',
            'ticket' => 'https://example.com/tickets/toronto-watchparty',
            'desc'   => 'Community marathon screening, trivia showdown, and fan theory roundtable.',
            'image'  => '/assets/images/fandoms/tv shows.jfif',
        ),
    );

    foreach ($extra_events as $ev) {
        $title_esc = esc($ev['title']);
        $exists = db_one("SELECT id FROM events WHERE title = '$title_esc' LIMIT 1");
        if (!$exists) {
            $cid = (int) $ev['cat_id'];
            $city = esc($ev['city']);
            $lat = esc($ev['lat']);
            $lng = esc($ev['lng']);
            $dt = esc($ev['date']);
            $tk = esc($ev['ticket']);
            $ds = esc($ev['desc']);
            $img = esc($ev['image']);
            db_query("INSERT INTO events (category_id, title, city, latitude, longitude, event_date, ticket_url, description, image, created_at) VALUES ($cid, '$title_esc', '$city', $lat, $lng, '$dt', '$tk', '$ds', '$img', NOW())");
        }
    }
}

function render_breadcrumbs($crumbs = array())
{

}

function render_share_bar($title = '')
{
    ?>
    <div class="share-bar" data-share-bar aria-label="Share this page">
        <span class="share-label">Share:</span>
        <button type="button" class="btn-ghost share-btn" data-share-copy data-share-title="<?php echo e($title); ?>">Copy link</button>
        <a class="btn-ghost share-btn" data-share-wa target="_blank" rel="noopener" href="#">WhatsApp</a>
        <a class="btn-ghost share-btn" data-share-x target="_blank" rel="noopener" href="#">X / Post</a>
    </div>
    <?php
}

function upload_file($field, $folder)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK) {
        return '';
    }
    $allowed = array('image/jpeg', 'image/png', 'image/webp', 'image/gif');
    $type = $_FILES[$field]['type'];
    if (!in_array($type, $allowed, true)) {
        return '';
    }
    if ($_FILES[$field]['size'] > 3 * 1024 * 1024) {
        return '';
    }
    $ext = strtolower(pathinfo($_FILES[$field]['name'], PATHINFO_EXTENSION));
    $name = uniqid('fhp_', true) . '.' . $ext;
    $dir = __DIR__ . '/../uploads/' . $folder;
    if (!is_dir($dir)) {
        mkdir($dir, 0777, true);
    }
    $dest = $dir . '/' . $name;
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dest)) {
        return 'uploads/' . $folder . '/' . $name;
    }
    return '';
}

function upload_video_file($field)
{
    if (!isset($_FILES[$field]) || $_FILES[$field]['error'] !== UPLOAD_ERR_OK || $_FILES[$field]['size'] > 25 * 1024 * 1024) {
        return '';
    }

    $finfo = finfo_open(FILEINFO_MIME_TYPE);
    if (!$finfo) {
        return '';
    }
    $mime = finfo_file($finfo, $_FILES[$field]['tmp_name']);
    finfo_close($finfo);

    $extensions = array('video/mp4' => 'mp4', 'video/webm' => 'webm');
    if (!isset($extensions[$mime])) {
        return '';
    }

    $dir = __DIR__ . '/../uploads/videos';
    if (!is_dir($dir) && !mkdir($dir, 0755, true)) {
        return '';
    }

    $filename = bin2hex(random_bytes(16)) . '.' . $extensions[$mime];
    if (move_uploaded_file($_FILES[$field]['tmp_name'], $dir . '/' . $filename)) {
        return 'uploads/videos/' . $filename;
    }

    return '';
}

function csrf_token()
{
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(16));
    }
    return $_SESSION['csrf_token'];
}

function csrf_check()
{
    if ($_SERVER['REQUEST_METHOD'] === 'POST') {
        $token = isset($_POST['csrf']) ? $_POST['csrf'] : '';
        if (!$token || !isset($_SESSION['csrf_token']) || $token !== $_SESSION['csrf_token']) {
            die('Invalid request token.');
        }
    }
}

function content_types()
{
    return array('article', 'video', 'audio', 'explainer');
}

function favorite_slugs($user)
{
    if (!$user || $user['favorite_categories'] === '') {
        return array();
    }
    return array_filter(array_map('trim', explode(',', $user['favorite_categories'])));
}


function category_url($slug)
{
    $slug = preg_replace('/[^a-z0-9\-]/', '', strtolower((string) $slug));
    return $slug === '' ? site_url('/index.php') : site_url('/' . $slug . '.php');
}


function category_counts($category_id)
{
    $cid = (int) $category_id;
    $row = db_one(
        "SELECT
            (SELECT COUNT(*) FROM contents WHERE category_id = $cid AND status = 'published') AS contents,
            (SELECT COUNT(*) FROM character_profiles WHERE category_id = $cid) AS characters,
            (SELECT COUNT(*) FROM events WHERE category_id = $cid) AS events,
            (SELECT COUNT(*) FROM merchandise_items WHERE category_id = $cid) AS merch"
    );
    if (!$row) {
        return array('contents' => 0, 'characters' => 0, 'events' => 0, 'merch' => 0);
    }
    return array(
        'contents' => (int) $row['contents'],
        'characters' => (int) $row['characters'],
        'events' => (int) $row['events'],
        'merch' => (int) $row['merch'],
    );
}

function category_total($counts)
{
    return (int) $counts['contents'] + (int) $counts['characters']
        + (int) $counts['events'] + (int) $counts['merch'];
}


function category_copy($slug)
{
    static $copy = array(
        'anime' => array(
            'kicker' => 'Episode deep dives',
            'blurb' => 'Frame by frame breakdowns of the openings, endings, and fight choreography that built the modern shonen fight.',
            'spotlight' => 'Standout episode',
            'sections' => array('Top anime titles', 'Characters in the spotlight', 'Screening events', 'Anime merch'),
        ),
        'gaming' => array(
            'kicker' => 'Design and craft',
            'blurb' => 'Traversal systems, open world structure, and the soundtrack work that makes a game stick with you.',
            'spotlight' => 'Must play',
            'sections' => array('Top game titles', 'Featured characters', 'Convention play floors', 'Gaming merch'),
        ),
        'movies' => array(
            'kicker' => 'The big screen',
            'blurb' => 'Practical effects against CGI, score breakdowns, and the trade craft behind the films we rewatch.',
            'spotlight' => 'Now showing',
            'sections' => array('Featured films', 'On screen characters', 'Premiere events', 'Movie merch'),
        ),
        'tv-shows' => array(
            'kicker' => 'Weekly recaps',
            'blurb' => 'Spoiler safe recaps, character arcs, and the writing choices that keep a season finale earned.',
            'spotlight' => 'Binge worthy',
            'sections' => array('Top series', 'Series characters', 'Premiere nights', 'TV merch'),
        ),
        'k-pop' => array(
            'kicker' => 'Comeback season',
            'blurb' => 'Choreo breakdowns, album reviews, and comeback week coverage for the biggest fandom on the planet.',
            'spotlight' => 'On repeat',
            'sections' => array('Comebacks and music', 'Idols and groups', 'Concert events', 'K-Pop merch'),
        ),
        'comics' => array(
            'kicker' => 'Panel by panel',
            'blurb' => 'How gutters, splash pages, and motion lines turn a static page into a fight you can read.',
            'spotlight' => 'Essential read',
            'sections' => array('Featured runs', 'Heroes and villains', 'Launch events', 'Comic merch'),
        ),
        'manga' => array(
            'kicker' => 'Panel and ink',
            'blurb' => 'Shojo, seinen, and shonen series reviewed with the art and the pacing given equal weight.',
            'spotlight' => 'Collector pick',
            'sections' => array('Top manga series', 'Manga characters', 'Library fair events', 'Manga merch'),
        ),
        'cosplay' => array(
            'kicker' => 'Armor and craft',
            'blurb' => 'Foam armour labs, wig styling, and convention photography alleys run by the people who make it.',
            'spotlight' => 'Best in show',
            'sections' => array('Cosplay features', 'Cosplayers', 'Convention events', 'Cosplay gear'),
        ),
    );

    $slug = (string) $slug;
    if (isset($copy[$slug])) {
        return $copy[$slug];
    }
    return array(
        'kicker' => 'From the club',
        'blurb' => 'Everything the club has filed under this category.',
        'spotlight' => 'Featured',
        'sections' => array('Top titles', 'Characters', 'Events', 'Merch'),
    );
}


function category_coming_soon($slug)
{
    static $roadmap = array(
        'anime' => array(
            array('Episode 118 rewatch thread', 'Every cut and frame hold, in order.', '/assets/images/spotlight/anime/Kyojuro Rengoku.jpg'),
            array('Studio comparison: bones to CGI', 'How four studios stage the same fight.', '/assets/images/spotlight/anime/Tanjiro Kamado.jpg'),
            array('Opening theme vocal deep dive', 'The singers behind the 90 second hook.', '/assets/images/spotlight/anime/Satoru Gojo.jpg'),
            array('Manga adaptation timeline', 'Chapter by chapter against the anime.', '/assets/images/spotlight/anime/Ryomen Sukuna.jpg'),
        ),
        'gaming' => array(
            array('Open world traversal map', 'Every fast travel point annotated.'),
            array('Boss rush frame data', 'Inputs, punishes, and the one second window.'),
            array('Soundtrack vinyl wishlist', 'The scores that deserve the shelf.'),
            array('Speedrun route vault', 'Community routes with notes.'),
        ),
        'movies' => array(
            array('Practical vs digital shootout', 'Same shot, both methods.'),
            array('Score and silence', 'Why the quiet scenes carry the film.'),
            array('Stunt breakdown reel', 'Wire work, plates, and the fall.'),
            array('Rewatch tier list', 'What holds up on the second viewing.'),
        ),
        'tv-shows' => array(
            array('Season finale scoring', 'Ranked by earned vs cheap.'),
            array('Recap without spoilers', 'Safe rewatches for the new viewer.'),
            array('Showrunner interview archive', 'On the decisions behind the season.'),
            array('Cancelled and back again', 'What saved a show and what did not.'),
        ),
        'k-pop' => array(
            array('Comeback stage breakdown', 'Counts, formations, and transitions.'),
            array('Album track by track', 'Rated with the lyrics read closely.'),
            array('Choreo difficulty index', 'Which dances need a practice room.'),
            array('Concert setlist archive', 'Every encore from every tour stop.'),
        ),
        'comics' => array(
            array('Gutter and panel grammar', 'Reading motion as a language.'),
            array('Splash page appreciation', 'The single image that sells an issue.'),
            array('Run completion checklist', 'Where to start and where to stop.'),
            array('Trade paperback tier list', 'Worth the shelf space or not.'),
        ),
        'manga' => array(
            array('Shojo to seinen sampler', 'Six series, one page each.'),
            array('Panel translation comparison', 'Official and fan scans side by side.'),
            array('Artist technique notes', 'Screentone, speed lines, and negative space.'),
            array('Volume guide by arc', 'Skip nothing, waste nothing.'),
        ),
        'cosplay' => array(
            array('Foam armour build log', 'From cardboard to full plate.'),
            array('Wig and makeup tutorial', 'Colour match that survives a con day.'),
            array('Convention photo alley guide', 'Best light, quiet corners, timing.'),
            array('Repair and storage', 'Making it survive the next event.'),
        ),
    );

    $slug = (string) $slug;
    return isset($roadmap[$slug]) ? $roadmap[$slug] : array(
        array('More features', 'The next piece is being written.'),
        array('Community picks', 'Member submissions open soon.'),
        array('Deep dive', 'A long read is in the works.'),
        array('Watch party', 'Dates to be announced.'),
    );
}


function category_banner_image($slug)
{
    $local = array(
        'anime' => 'anime.jfif',
        'comics' => 'comics.jfif',
        'cosplay' => 'co1.jfif',
        'manga' => 'ma1.jfif',
        'movies' => 'mo1.jfif',
        'gaming' => 'g1.jfif',
        'k-pop' => 'kpop.jpg',
        'tv-shows' => 'tv shows.jfif',
    );
    $slug = (string) $slug;
    if (isset($local[$slug])) {
        $root_images = array('cosplay' => 'co1.jfif', 'manga' => 'ma1.jfif', 'movies' => 'mo1.jfif', 'gaming' => 'g1.jfif', 'k-pop' => 'kpop.jpg');
        if (isset($root_images[$slug])) {
            return site_url('/assets/images/' . $root_images[$slug]);
        }
        return hero_slide_image($local[$slug]);
    }
    return category_visual_image($slug);
}


function hero_slide_image($filename)
{
    $filename = basename((string) $filename);
    if ($filename === '') {
        return '';
    }
    $file = __DIR__ . '/../assets/images/fandoms/' . $filename;
    if (is_file($file)) {
        return site_url('/assets/images/fandoms/' . rawurlencode($filename));
    }
    return '';
}


function hero_slide_gradient($n)
{
    $palettes = array(
        array(22, 163, 74),
        array(56, 189, 248),
        array(168, 85, 247),
        array(244, 63, 94),
        array(251, 191, 36),
        array(14, 165, 233),
        array(236, 72, 153),
        array(45, 212, 191),
    );
    $i = ((int) $n - 1) % count($palettes);
    if ($i < 0) {
        $i = 0;
    }
    list($r, $g, $b) = $palettes[$i];
    return 'radial-gradient(760px 440px at 84% 6%, rgba(' . $r . ', ' . $g . ', ' . $b . ', 0.34), transparent 62%), linear-gradient(135deg, rgba(' . $r . ', ' . $g . ', ' . $b . ', 0.22) 0%, rgba(4, 16, 11, 0.9) 66%)';
}

function hero_slides()
{
    return array(
        array(
            'slug' => 'movies',
            'image' => 'spiderman.jpg',
            'video' => '',
            'kicker' => 'Movies',
            'title' => 'Spider-Man',
            'line' => 'Every swing is a lesson in framing. We break down the panel timing behind the shots everyone argues about.',
            'cta' => 'Open Movies',
        ),
        array(
            'slug' => 'anime',
            'image' => 'anime.jfif',
            'video' => 'spotlight/anime.mp4',

            'kicker' => 'Anime',
            'title' => 'Anime Worlds',
            'line' => 'From shonen battles to quiet character stories, explore the series and scenes fans keep coming back to.',
            'cta' => 'Open Anime',
        ),
        array(
            'slug' => 'gaming',
            'image' => 'speed.jpg',
            'video' => 'spotlight/gaming.mp4',
            'kicker' => 'Gaming',
            'title' => 'The Speedrun',
            'line' => 'Route theory, frame perfect inputs, and the skips that still work after a patch.',
            'cta' => 'Open Gaming',
        ),
        array(
            'slug' => 'k-pop',
            'image' => 'comeback.webp',
            'video' => 'spotlight/k-pop.mp4',
            'kicker' => 'K-Pop',
            'title' => 'Comeback Week',
            'line' => 'Choreo breakdowns stage by stage, from the first count to the final formation.',
            'cta' => 'Open K-Pop',
        ),
        array(
            'slug' => 'comics',
            'image' => 'comics.jfif',
            'video' => 'spotlight/comics.mp4',
            'kicker' => 'Comics',
            'title' => 'Panel Language',
            'line' => 'Gutters, splashes, and motion lines. How a page you are still reading works like a fight.',
            'cta' => 'Open Comics',
        ),
        array(
            'slug' => 'manga',
            'image' => 'manga.jfif',
            'video' => 'spotlight/manga.mp4',
            'kicker' => 'Manga',
            'title' => 'Ink and Panel',
            'line' => 'Shojo, seinen, shonen. Art and pacing reviewed with equal weight on every chapter.',
            'cta' => 'Open Manga',
        ),
        array(
            'slug' => 'tv-shows',
            'image' => 'tv shows.jfif',
            'video' => 'spotlight/tv-shows.mp4',
            'kicker' => 'TV Shows',
            'title' => 'Series Worth Watching',
            'line' => 'From long-running favourites to new breakout shows, find the stories made for the next episode.',
            'cta' => 'Open TV Shows',
        ),
        array(
            'slug' => 'cosplay',
            'image' => 'cosplay.jfif',
            'video' => 'spotlight/cosplay.mp4',
            'kicker' => 'Cosplay',
            'title' => 'Armor Lab',
            'line' => 'Foam, thermoplast, and the finishing pass that makes a convention entry look screen accurate.',
            'cta' => 'Open Cosplay',
        ),
    );
}

