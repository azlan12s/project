</main>
<?php if (empty($hide_footer)) { ?>
<footer class="site-footer">
    <div class="footer-glow" aria-hidden="true"></div>

    <div class="wrap footer-grid">
        <div class="footer-brand">
            <div class="footer-brand-text">
                <h3>Fan Hub <em class="footer-em">Plus</em></h3>
                <p>A fandom discovery hub for Anime, Gaming, Movies, TV Shows, K-Pop, Comics, Manga, and Cosplay.</p>
                <ul class="footer-fandom-chips">
                    <?php foreach ($nav_cats as $c) { ?>
                        <li><a class="footer-chip" href="<?php echo e(category_url($c['slug'])); ?>"><?php echo e($c['name']); ?></a></li>
                    <?php } ?>
                </ul>
            </div>
        </div>

        <nav class="footer-col" aria-label="Explore">
            <h4>Explore</h4>
            <ul>
                <?php foreach ($nav_cats as $c) { ?>
                    <li><a href="<?php echo e(site_url('/explore.php?category=' . rawurlencode($c['slug']))); ?>"><?php echo e($c['name']); ?></a></li>
                <?php } ?>
            </ul>
        </nav>

        <nav class="footer-col" aria-label="Hub">
            <h4>Hub</h4>
            <ul>
                <li><a href="<?php echo e(site_url('/multimedia.php')); ?>">Multimedia Center</a></li>
                <li><a href="<?php echo e(site_url('/characters.php')); ?>">Character Profiles</a></li>
                <li><a href="<?php echo e(site_url('/events-map.php')); ?>">Events Map</a></li>
                <li><a href="<?php echo e(site_url('/merch.php')); ?>">Merchandise</a></li>
                <li><a href="<?php echo e(site_url('/articles.php')); ?>">Articles</a></li>
                <li><a href="<?php echo e(site_url('/feedback.php')); ?>">Feedback</a></li>
            </ul>
        </nav>

        <nav class="footer-col" aria-label="Account">
            <h4>Account</h4>
            <ul>
                <li><a href="<?php echo e(site_url('/login.php')); ?>">Login</a></li>
                <li><a href="<?php echo e(site_url('/register.php')); ?>">Register</a></li>
                <li><a href="<?php echo e(site_url('/dashboard.php')); ?>">Dashboard</a></li>
                <li><a href="<?php echo e(site_url('/bookmarks.php')); ?>">Bookmarks</a></li>
            </ul>
        </nav>
    </div>

    <div class="wrap sitemap-block" id="visual-sitemap">
        <h4>Dynamic Visual Sitemap</h4>
        <div class="sitemap">
            <a href="<?php echo e(site_url('/index.php')); ?>">Home</a>
            <span aria-hidden="true">&rarr;</span>
            <a href="<?php echo e(site_url('/explore.php')); ?>">Explore</a>
            <?php foreach ($nav_cats as $c) { ?>
                <a class="chip" href="<?php echo e(category_url($c['slug'])); ?>"><?php echo e($c['name']); ?></a>
            <?php } ?>
            <a href="<?php echo e(site_url('/multimedia.php')); ?>">Media</a>
            <a href="<?php echo e(site_url('/characters.php')); ?>">Characters</a>
            <a href="<?php echo e(site_url('/articles.php')); ?>">Articles</a>
            <a href="<?php echo e(site_url('/events.php')); ?>">Events</a>
            <a href="<?php echo e(site_url('/events-map.php')); ?>">Map</a>
            <a href="<?php echo e(site_url('/merch.php')); ?>">Merch</a>
            <a href="<?php echo e(site_url('/feedback.php')); ?>">Feedback</a>
        </div>
    </div>

    <div class="wrap footer-bottom">
        <p class="copy">&copy; <?php echo date('Y'); ?> Fan Hub Plus &middot; TechWiz 7</p>
        <a class="footer-top" href="#top">Back to top</a>
    </div>
</footer>
<?php } ?>
<button class="to-top" id="toTop" type="button" aria-label="Back to top" title="Back to top">
    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false"><path d="M12 5.5 21 14l-1.4 1.4-6.6-6.6V21h-2V8.8L5.4 15.4 4 14l8-8.5Z"/></svg>
</button>
<div class="toast-host" id="toastHost" role="status" aria-live="polite" aria-atomic="false"></div>
<p class="visually-hidden" id="liveRegion" role="status" aria-live="polite"></p>
<section class="site-assistant" id="siteAssistant" aria-label="Fan Hub AI Chatbot">
    <div class="assistant-panel" id="assistantPanel" hidden>
        <header class="assistant-heading">
            <span class="assistant-ai-avatar" aria-hidden="true">
                <svg viewBox="0 0 24 24" focusable="false"><path d="M12 2l2.2 5.8L20 10l-5.8 2.2L12 18l-2.2-5.8L4 10l5.8-2.2L12 2zm6 13l1.1 2.9L22 19l-2.9 1.1L18 23l-1.1-2.9L14 19l2.9-1.1L18 15z" fill="currentColor"/></svg>
            </span>
            <div>
                <strong>Fan Hub AI</strong>
                <small><span class="assistant-status-dot" aria-hidden="true"></span> Anime, Cartoon &amp; K-Pop AI</small>
            </div>
            <button class="assistant-clear" id="assistantClear" type="button" title="Clear chat" aria-label="Clear chat">Clear</button>
            <button class="assistant-close" id="assistantClose" type="button" aria-label="Close AI chat">&times;</button>
        </header>
        <div class="assistant-messages" id="assistantMessages" role="log" aria-live="polite" aria-relevant="additions">
            <div class="assistant-message assistant-bot">
                <p>Hi! I am the <strong>Fan Hub AI Chatbot</strong>. Ask me anything about <strong>Anime</strong>, <strong>Cartoons</strong>, <strong>K-Pop</strong>, or our site's content!</p>
            </div>
        </div>
        <div class="assistant-suggestions" id="assistantSuggestions" aria-label="Quick questions">
            <button type="button" class="assistant-chip" data-prompt="Tell me about Gojo Satoru and Jujutsu Kaisen">Jujutsu Kaisen</button>
            <button type="button" class="assistant-chip" data-prompt="Tell me about BTS and BLACKPINK in K-Pop">K-Pop Idols</button>
            <button type="button" class="assistant-chip" data-prompt="Best cartoons and animated series">Cartoons</button>
            <button type="button" class="assistant-chip" data-prompt="What anime are featured on this website?">Site Anime</button>
        </div>
        <form class="assistant-form" id="assistantForm" action="<?php echo e(site_url('/assistant.php')); ?>">
            <label class="visually-hidden" for="assistantInput">Ask Fan Hub AI</label>
            <input id="assistantInput" name="message" maxlength="300" placeholder="Ask about Anime, Cartoons, K-Pop..." autocomplete="off" required>
            <button type="submit" aria-label="Send message">Send</button>
        </form>
    </div>
    <button class="assistant-launcher" id="assistantOpen" type="button" aria-expanded="false" aria-controls="assistantPanel">
        <span class="assistant-launcher-icon" aria-hidden="true">
            <svg viewBox="0 0 24 24" width="16" height="16" focusable="false"><path d="M12 2l2.2 5.8L20 10l-5.8 2.2L12 18l-2.2-5.8L4 10l5.8-2.2L12 2z" fill="currentColor"/></svg>
        </span>
        <span>AI Chat</span>
    </button>
</section>
<?php
$current_page_script = basename(parse_url(isset($_SERVER['SCRIPT_NAME']) ? $_SERVER['SCRIPT_NAME'] : '', PHP_URL_PATH));
$is_merch_page = (!empty($is_merchandise_page) || $current_page_script === 'merch.php' || (isset($active) && $active === 'merch'));
if ($is_merch_page) { ?>

<div class="side-cart-backdrop" id="sideCartBackdrop"></div>
<aside class="side-cart-drawer" id="sideCartDrawer" aria-hidden="true" role="dialog" aria-label="Shopping Cart">
    <div class="side-cart-header">
        <div class="side-cart-title-box">
            <h3>Fandom Cart</h3>
            <span class="side-cart-count-badge" id="sideCartDrawerCount">0 items</span>
        </div>
        <button type="button" class="side-cart-close" id="sideCartClose" aria-label="Close cart">&times;</button>
    </div>
    <div class="side-cart-body" id="sideCartItems">

    </div>
    <div class="side-cart-footer">
        <div class="side-cart-summary">
            <div class="cart-summary-row"><span>Subtotal</span><strong id="sideCartSubtotal">$0.00</strong></div>
            <div class="cart-summary-row"><span>Shipping</span><span class="text-free-ship">FREE</span></div>
            <div class="cart-summary-row cart-summary-total"><span>Total</span><strong id="sideCartTotal">$0.00</strong></div>
        </div>
        <button type="button" class="btn btn-checkout" id="sideCartCheckout">Checkout Now</button>
        <button type="button" class="btn-ghost side-cart-clear-btn" id="sideCartClear">Clear Cart</button>
    </div>
</aside>
<button class="floating-side-cart" id="floatingSideCart" type="button" aria-label="View Shopping Cart">
    <span class="side-cart-icon" aria-hidden="true">
        <svg viewBox="0 0 24 24" width="18" height="18" fill="none" stroke="currentColor" stroke-width="2"><path d="M6 6h15l-1.5 9h-12L6 6ZM6 6 4.5 3H2m7 18a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Zm9 0a1.5 1.5 0 1 0 0-3 1.5 1.5 0 0 0 0 3Z"/></svg>
    </span>
    <span>Cart</span>
    <span class="side-cart-badge" id="sideCartCount">0</span>
</button>
<?php } ?>
<script src="https://unpkg.com/leaflet@1.9.4/dist/leaflet.js">
</script>
<script src="<?php echo e(site_url('/assets/js/app.js?v=' . filemtime(__DIR__ . '/../assets/js/app.js'))); ?>"></script>
<?php if (isset($extra_js)) { echo $extra_js; ?>
<?php } ?>
</body>
</html>

