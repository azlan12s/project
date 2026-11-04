        </main>
    </div>
</div>
<footer class="site-footer">
    <div class="footer-glow" aria-hidden="true"></div>
    <div class="wrap footer-grid">
        <div class="footer-brand">
            <div class="footer-brand-text">
                <h3>Fan Hub <em class="footer-em">Plus</em></h3>
                <p>Administrative control panel & desk for Fan Hub Plus.</p>
            </div>
        </div>
        <nav class="footer-col" aria-label="Management">
            <h4>Management</h4>
            <ul>
                <li><a href="<?php echo e(site_url('/admin/index.php')); ?>">Dashboard</a></li>
                <li><a href="<?php echo e(site_url('/admin/moderation.php')); ?>">Moderation</a></li>
                <li><a href="<?php echo e(site_url('/admin/feedback.php')); ?>">Feedback</a></li>
                <li><a href="<?php echo e(site_url('/admin/contents.php')); ?>">Contents</a></li>
                <li><a href="<?php echo e(site_url('/admin/users.php')); ?>">Users</a></li>
            </ul>
        </nav>
        <nav class="footer-col" aria-label="Catalog">
            <h4>Catalog & Desk</h4>
            <ul>
                <li><a href="<?php echo e(site_url('/admin/categories.php')); ?>">Categories</a></li>
                <li><a href="<?php echo e(site_url('/admin/characters.php')); ?>">Characters</a></li>
                <li><a href="<?php echo e(site_url('/admin/merch.php')); ?>">Merchandise</a></li>
                <li><a href="<?php echo e(site_url('/admin/events.php')); ?>">Events</a></li>
                <li><a href="<?php echo e(site_url('/admin/account.php')); ?>">Account</a></li>
            </ul>
        </nav>
    </div>
    <div class="wrap footer-bottom">
        <p class="copy">&copy; <?php echo date('Y'); ?> Fan Hub Plus &middot; Admin Portal</p>
        <a class="footer-top" href="#top">Back to top</a>
    </div>
</footer>
<button class="to-top" id="toTop" type="button" aria-label="Back to top" title="Back to top">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5.5 21 14l-1.4 1.4-6.6-6.6V21h-2V8.8L5.4 15.4 4 14l8-8.5Z"/></svg>
</button>
<div class="toast-host" id="toastHost" role="status" aria-live="polite" aria-atomic="false"></div>
<p class="visually-hidden" id="liveRegion" role="status" aria-live="polite"></p>
<script src="<?php echo e(site_url('/assets/js/app.js?v=' . filemtime(__DIR__ . '/../assets/js/app.js'))); ?>"></script>
<?php if (isset($extra_js)) { echo $extra_js; } ?>
</body>
</html>

