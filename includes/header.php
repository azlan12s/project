<?php
require_once __DIR__ . '/functions.php';
if (is_admin()) {
    redirect('/admin/index.php');
}
$user = current_user();
$theme = 'dark';
$font_size = 'md';
if ($user) {
    if (isset($user['theme']) && in_array($user['theme'], array('dark', 'light'), true)) {
        $theme = $user['theme'];
    }
    if (in_array($user['font_size'], array('sm', 'md', 'lg'), true)) {
        $font_size = $user['font_size'];
    }
}
if (isset($_COOKIE['fhp_session_theme']) && in_array($_COOKIE['fhp_session_theme'], array('dark', 'light'), true)) {
    $theme = $_COOKIE['fhp_session_theme'];
}
$page_title = isset($page_title) ? $page_title : 'Fan Hub Plus';
$nav_cats = categories();
$flash = flash_get();


$current_page = basename(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : 'index.php');
$nav_links = array(
    'index.php' => array(site_url('/index.php'), 'Home'),
    'merch.php' => array(site_url('/merch.php'), 'Merch'),
    'explore.php' => array(site_url('/explore.php'), 'Explore'),
    'multimedia.php' => array(site_url('/multimedia.php'), 'Multimedia'),
    'characters.php' => array(site_url('/characters.php'), 'Characters'),
    'articles.php' => array(site_url('/articles.php'), 'Articles'),
    'events.php' => array(site_url('/events.php'), 'Events'),
    'feedback.php' => array(site_url('/feedback.php'), 'Feedback'),
    'events-map.php' => array(site_url('/events-map.php'), 'Map'),
);


$nav_category_slugs = array();
foreach ($nav_cats as $nav_cat_row) {
    $nav_category_slugs[] = $nav_cat_row['slug'] . '.php';
}
$on_category_page = in_array($current_page, $nav_category_slugs, true);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo e($theme); ?>" data-font="<?php echo e($font_size); ?>">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <title><?php echo e($page_title); ?> | Fan Hub Plus</title>
    <link rel="icon" type="image/png" href="<?php echo e(site_url('/assets/images/logo1.png')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(site_url('/assets/images/logo1.png')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Outfit:wght@400;500;600;700;800&family=Syne:wght@700;800&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="https://unpkg.com/leaflet@1.9.4/dist/leaflet.css">
    <link rel="stylesheet" href="<?php echo e(site_url('/assets/css/app.css?v=' . filemtime(__DIR__ . '/../assets/css/app.css'))); ?>">
    <script>
        document.documentElement.classList.add('js-motion');
        window.FHP_USER = <?php echo json_encode($user ? array('id' => (int) $user['id'], 'name' => $user['name'], 'email' => $user['email']) : null); ?>;
        window.FHP_CSRF = <?php echo json_encode(csrf_token()); ?>;
        window.addEventListener('pageshow', function(e) {
            if (e.persisted || (window.performance && window.performance.getEntriesByType && window.performance.getEntriesByType('navigation')[0] && window.performance.getEntriesByType('navigation')[0].type === 'back_forward')) {
                <?php if ($user) { ?>
                    window.location.reload();
                <?php } ?>
            }
        });
    </script>
</head>
<body id="top">
<a class="skip-link" href="#main">Skip to content</a>
<div class="scroll-progress" aria-hidden="true"><span class="scroll-progress-bar" id="scrollProgress"></span></div>
<?php if (empty($hide_navbar)) { ?>
<header class="site-header">
    <div class="wrap header-inner">
        <a class="brand" href="<?php echo e(site_url('/index.php')); ?>">
            <img class="brand-mark" src="<?php echo e(site_url('/assets/images/logo1.png')); ?>" alt="">
            <span>Fan Hub <em>Plus</em></span>
        </a>
        <button class="nav-toggle" type="button" aria-expanded="false" aria-controls="site-nav">
            <span class="nav-toggle-bars" aria-hidden="true"><i></i><i></i><i></i></span>
            <span>Menu</span>
        </button>
        <nav id="site-nav" class="site-nav" aria-label="Primary">
            <?php foreach ($nav_links as $key => $link) {
                if ($key === 'events-map.php' || ($key === 'dashboard.php' && !$user)) {
                    continue;
                }
                $is_active = ($current_page === $key) ? ' active' : '';
            ?>
                <a class="nav-link<?php echo $is_active; ?>" href="<?php echo e($link[0]); ?>"<?php echo $is_active === ' active' ? ' aria-current="page"' : ''; ?>>
                    <?php echo e($link[1]); ?>
                </a>
                <?php if ($key === 'index.php') { ?>
                    <div class="nav-drop">
                        <button class="nav-drop-btn" type="button" aria-expanded="false" aria-haspopup="true"<?php echo $on_category_page ? ' aria-current="page"' : ''; ?>>
                            Categories
                            <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false" class="nav-caret"><path d="m6 9 6 6 6-6" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"/></svg>
                        </button>
                        <div class="nav-drop-menu" role="group" aria-label="Categories">
                            <?php foreach ($nav_cats as $c) { ?>
                                <a href="<?php echo e(category_url($c['slug'])); ?>"<?php echo $current_page === $c['slug'] . '.php' ? ' aria-current="page"' : ''; ?>>
                                    <span class="nav-drop-art" style="background-image:url('<?php echo e(category_banner_image($c['slug'])); ?>')" aria-hidden="true"></span>
                                    <span class="nav-drop-text">
                                        <b><?php echo e($c['name']); ?></b>
                                        <small><?php echo e($c['description']); ?></small>
                                    </span>
                                </a>
                            <?php } ?>
                        </div>
                    </div>
                <?php } ?>
            <?php } ?>
            <?php if ($user) { ?>
                <a class="nav-link mobile-only-link<?php echo $current_page === 'dashboard.php' ? ' active' : ''; ?>" href="<?php echo e(site_url('/dashboard.php')); ?>"<?php echo $current_page === 'dashboard.php' ? ' aria-current="page"' : ''; ?>>Account</a>
                <?php if ($user['role'] === 'admin') { ?>
                    <a class="nav-link mobile-only-link" href="<?php echo e(site_url('/admin/index.php')); ?>">Admin</a>
                <?php } ?>
                <a class="nav-link mobile-only-link" href="<?php echo e(site_url('/logout.php')); ?>">Logout</a>
            <?php } else { ?>
                <a class="nav-link mobile-only-link" href="<?php echo e(site_url('/login.php')); ?>">Login</a>
                <a class="nav-cta mobile-only-link" href="<?php echo e(site_url('/register.php')); ?>">Register</a>
            <?php } ?>
        </nav>
        <div class="header-tools">
            <div class="header-search-wrap">
                <button type="button" class="icon-btn" id="searchToggle" aria-label="Search" title="Search" aria-expanded="false" aria-controls="headerSearchForm">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M10.5 3a7.5 7.5 0 1 0 4.55 13.46l4.24 4.25 1.42-1.42-4.25-4.24A7.5 7.5 0 0 0 10.5 3Zm0 2a5.5 5.5 0 1 1 0 11 5.5 5.5 0 0 1 0-11Z"/></svg>
                </button>
                <form class="header-search-pop" id="headerSearchForm" method="get" action="<?php echo e(site_url('/explore.php')); ?>" role="search">
                    <label class="sr-only" for="headerSearch">Search titles</label>
                    <input id="headerSearch" type="search" name="q" placeholder="Search titles, genres..." value="<?php echo isset($_GET['q']) ? e($_GET['q']) : ''; ?>" autocomplete="off">
                    <button type="submit" class="btn header-search-submit">Search</button>
                </form>
            </div>
            <button type="button" class="icon-btn" id="themeToggle" aria-label="Switch to light theme" title="Toggle theme" aria-pressed="false">
                <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10Zm0-13a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1Zm0 14a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1ZM4 11H3a1 1 0 1 1 0-2h1a1 1 0 1 1 0 2Zm16 0h1a1 1 0 1 1 0 2h-1a1 1 0 1 1 0-2ZM5.1 4.1a1 1 0 0 1 1.4 0l.7.7a1 1 0 1 1-1.4 1.4l-.7-.7a1 1 0 0 1 0-1.4Zm11 11a1 1 0 0 1 1.4 0l.7.7a1 1 0 1 1-1.4 1.4l-.7-.7a1 1 0 0 1 0-1.4Zm2.1-11a1 1 0 0 1 0 1.4l-.7.7a1 1 0 1 1-1.4-1.4l.7-.7a1 1 0 0 1 1.4 0Zm-11 11a1 1 0 0 1 0 1.4l-.7.7a1 1 0 1 1-1.4-1.4l.7-.7a1 1 0 0 1 1.4 0Z"/></svg>
                <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 13.2A9 9 0 1 1 10.8 3a7 7 0 0 0 10.2 10.2Z"/></svg>
            </button>
            <?php if (!$user) { ?>
                <a class="nav-login-btn desktop-only-link" href="<?php echo e(site_url('/login.php')); ?>">Login</a>
                <a class="nav-cta desktop-only-link" href="<?php echo e(site_url('/register.php')); ?>">Register</a>
            <?php } else { ?>
                <a class="nav-link desktop-only-link<?php echo $current_page === 'dashboard.php' ? ' active' : ''; ?>" href="<?php echo e(site_url('/dashboard.php')); ?>"<?php echo $current_page === 'dashboard.php' ? ' aria-current="page"' : ''; ?>>Account</a>
                <?php if ($user['role'] === 'admin') { ?>
                    <a class="nav-link desktop-only-link" href="<?php echo e(site_url('/admin/index.php')); ?>">Admin</a>
                <?php } ?>
                <a class="nav-link desktop-only-link" href="<?php echo e(site_url('/logout.php')); ?>">Logout</a>
            <?php } ?>
        </div>
    </div>
</header>
<?php } ?>
<?php if ($flash) { ?>
    <div class="flash flash-<?php echo e($flash['type']); ?>" role="status" data-autodismiss><?php echo e($flash['message']); ?></div>
<?php } ?>
<main id="main">

