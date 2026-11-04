<?php
require_once __DIR__ . '/functions.php';
require_admin();
$user = current_user();
$page_title = isset($page_title) ? $page_title : 'Admin';
$active = isset($active) ? $active : '';
$flash = flash_get();
$theme = 'dark';
if (isset($_COOKIE['fhp_session_theme']) && in_array($_COOKIE['fhp_session_theme'], array('dark', 'light'), true)) {
    $theme = $_COOKIE['fhp_session_theme'];
}

$admin_links = array(
    array('key' => 'dashboard', 'href' => site_url('/admin/index.php'), 'label' => 'Dashboard'),
    array('key' => 'moderation', 'href' => site_url('/admin/moderation.php'), 'label' => 'Moderation queue'),
    array('key' => 'feedback', 'href' => site_url('/admin/feedback.php'), 'label' => 'Feedback'),
    array('key' => 'contents', 'href' => site_url('/admin/contents.php'), 'label' => 'Content'),
    array('key' => 'users', 'href' => site_url('/admin/users.php'), 'label' => 'Users'),
    array('key' => 'categories', 'href' => site_url('/admin/categories.php'), 'label' => 'Categories'),
    array('key' => 'characters', 'href' => site_url('/admin/characters.php'), 'label' => 'Characters'),
    array('key' => 'merch', 'href' => site_url('/admin/merch.php'), 'label' => 'Merchandise'),
    array('key' => 'orders', 'href' => site_url('/admin/orders.php'), 'label' => 'Orders'),
    array('key' => 'events', 'href' => site_url('/admin/events.php'), 'label' => 'Events'),
    array('key' => 'account', 'href' => site_url('/admin/account.php'), 'label' => 'Account settings'),
);
$admin_icons = array(
    'dashboard' => '<path d="M4 13h6V4H4v9Zm0 7h6v-5H4v5Zm10 0h6V11h-6v9Zm0-16v5h6V4h-6Z"/>',
    'moderation' => '<path d="M12 3 4 6v6c0 4.6 3.2 8.6 8 9.9 4.8-1.3 8-5.3 8-9.9V6l-8-3Zm-1.2 12.4-3.2-3.2 1.4-1.4 1.8 1.8 4.4-4.4 1.4 1.4-5.8 5.8Z"/>',
    'feedback' => '<path d="M4 4h16v12H8l-4 4V4Zm4 4v2h8V8H8Zm0 4v2h5v-2H8Z"/>',
    'contents' => '<path d="M6 3h9l5 5v13a1 1 0 0 1-1 1H6a1 1 0 0 1-1-1V4a1 1 0 0 1 1-1Zm8 1.5V9h4.5L14 4.5ZM8 13h8v1.6H8V13Zm0 3.4h8V18H8v-1.6ZM8 9.6h4v1.6H8V9.6Z"/>',
    'users' => '<path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0 2c-4 0-8 2-8 5v2h16v-2c0-3-4-5-8-5Z"/>',
    'categories' => '<path d="M4 4h7v7H4V4Zm9 0h7v7h-7V4ZM4 13h7v7H4v-7Zm9 0h7v7h-7v-7Z"/>',
    'characters' => '<path d="M12 2a5 5 0 0 1 5 5c0 2-1.1 3.6-2.7 4.5l.2 1.4 4 1.5c1 .4 1.5 1.2 1.5 2.2V20H8v-3.4c0-1 .6-1.8 1.5-2.2l4-1.5.2-1.4C12.1 10.6 11 9 11 7a5 5 0 0 1 1-3Z"/>',
    'merch' => '<path d="M7 4h10l1.5 3H5.5L7 4Zm-2 4h14l-1 12H6L5 8Zm5 3v6h4v-6h-4Z"/>',
    'orders' => '<path d="M7 18c-1.1 0-1.99.9-1.99 2S5.9 22 7 22s2-.9 2-2-.9-2-2-2zM1 2v2h2l3.6 7.59-1.35 2.45c-.16.28-.25.61-.25.96 0 1.1.9 2 2 2h12v-2H7.42c-.14 0-.25-.11-.25-.25l.03-.12.9-1.63h7.45c.75 0 1.41-.41 1.75-1.03l3.58-6.49A1.003 1.003 0 0 0 20 4H5.21l-.94-2H1zm16 16c-1.1 0-1.99.9-1.99 2s.89 2 1.99 2 2-.9 2-2-.9-2-2-2z"/>',
    'events' => '<path d="M7 2v2H5a2 2 0 0 0-2 2v2h18V6a2 2 0 0 0-2-2h-2V2h-2v2H9V2H7ZM3 10v9a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-9H3Zm4 3h4v4H7v-4Z"/>',
    'account' => '<path d="M12 12a4.5 4.5 0 1 0 0-9 4.5 4.5 0 0 0 0 9Zm0 2c-4.4 0-8 2.2-8 5v2h16v-2c0-2.8-3.6-5-8-5Z"/>',
);
?>
<!DOCTYPE html>
<html lang="en" data-theme="<?php echo e($theme); ?>" data-font="md">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <meta name="color-scheme" content="dark light">
    <title><?php echo e($page_title); ?> | Fan Hub Plus Admin</title>
    <link rel="icon" type="image/png" href="<?php echo e(site_url('/assets/images/logo1.png')); ?>">
    <link rel="apple-touch-icon" href="<?php echo e(site_url('/assets/images/logo1.png')); ?>">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=DM+Sans:wght@400;500;600;700&family=Outfit:wght@400;500;600;700;800&family=Space+Grotesk:wght@500;600;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="<?php echo e(site_url('/assets/css/app.css?v=' . filemtime(__DIR__ . '/../assets/css/app.css'))); ?>">
    <script>
        document.documentElement.classList.add('js-motion');
        window.addEventListener('pageshow', function(e) {
            if (e.persisted || (window.performance && window.performance.getEntriesByType && window.performance.getEntriesByType('navigation')[0] && window.performance.getEntriesByType('navigation')[0].type === 'back_forward')) {
                window.location.replace("<?php echo e(site_url('/login.php?relogin=1&next=' . rawurlencode($_SERVER['REQUEST_URI']))); ?>");
            }
        });
    </script>
</head>
<body class="admin-body" id="top">
<div class="scroll-progress" aria-hidden="true"><span class="scroll-progress-bar" id="scrollProgress"></span></div>
<div class="admin-shell">
    <aside class="admin-sidebar" id="adminSidebar">
        <a class="brand" href="<?php echo e(site_url('/admin/index.php')); ?>">
            <img class="brand-mark" src="<?php echo e(site_url('/assets/images/logo1.png')); ?>" alt="">
            <span>Admin <em>Desk</em></span>
        </a>
        <nav aria-label="Admin navigation">
            <?php foreach ($admin_links as $link) { ?>
                <a href="<?php echo e($link['href']); ?>" class="<?php echo $active === $link['key'] ? 'active' : ''; ?>">
                    <svg viewBox="0 0 24 24" fill="currentColor" aria-hidden="true"><?php echo $admin_icons[$link['key']]; ?></svg>
                    <span><?php echo e($link['label']); ?></span>
                </a>
            <?php } ?>
        </nav>
    </aside>
    <div class="admin-main">
        <header class="admin-topbar">
            <button type="button" class="sidebar-toggle icon-btn" id="sidebarToggle" aria-label="Toggle menu" aria-expanded="false" aria-controls="adminSidebar">&#9776;</button>
            <h1 class="admin-topbar-title"><?php echo e($page_title); ?></h1>
            <div class="who">
                <span class="meta"><?php echo e($user['name']); ?> · <?php echo e($user['role']); ?></span>
                <button type="button" class="icon-btn" id="themeToggle" aria-label="Switch to light theme" title="Toggle theme" aria-pressed="false">
                    <svg class="icon-sun" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 17a5 5 0 1 1 0-10 5 5 0 0 1 0 10Zm0-13a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0V5a1 1 0 0 1 1-1Zm0 14a1 1 0 0 1 1 1v1a1 1 0 1 1-2 0v-1a1 1 0 0 1 1-1ZM4 11H3a1 1 0 1 1 0-2h1a1 1 0 1 1 0 2Zm16 0h1a1 1 0 1 1 0 2h-1a1 1 0 1 1 0-2ZM5.1 4.1a1 1 0 0 1 1.4 0l.7.7a1 1 0 1 1-1.4 1.4l-.7-.7a1 1 0 0 1 0-1.4Zm11 11a1 1 0 0 1 1.4 0l.7.7a1 1 0 1 1-1.4 1.4l-.7-.7a1 1 0 0 1 0-1.4Zm2.1-11a1 1 0 0 1 0 1.4l-.7.7a1 1 0 1 1-1.4-1.4l.7-.7a1 1 0 0 1 1.4 0Zm-11 11a1 1 0 0 1 0 1.4l-.7.7a1 1 0 1 1-1.4-1.4l.7-.7a1 1 0 0 1 1.4 0Z"/></svg>
                    <svg class="icon-moon" viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M21 13.2A9 9 0 1 1 10.8 3a7 7 0 0 0 10.2 10.2Z"/></svg>
                </button>
                <a class="btn-ghost" href="<?php echo e(site_url('/logout.php')); ?>">Logout</a>
            </div>
        </header>
        <?php if ($flash) { ?>
            <div class="flash flash-<?php echo e($flash['type']); ?>" role="status" data-autodismiss style="width:auto;margin:16px 28px 0"><?php echo e($flash['message']); ?></div>
        <?php } ?>
        <main class="admin-page">

