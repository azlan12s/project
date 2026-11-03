/* Fan Hub Plus — front-end runtime
   Sections: navigation, theme, scroll UX, carousel, toasts. */
(function () {
    'use strict';

    var reduceMotion = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

    function announce(message) {
        var region = document.getElementById('liveRegion');
        if (!region) { return; }
        region.textContent = '';
        window.setTimeout(function () { region.textContent = message; }, 40);
    }

    function initNav() {
        var toggle = document.querySelector('.nav-toggle');
        var nav = document.getElementById('site-nav');
        if (!toggle || !nav) { return; }

        /* The Categories flyout is opened and closed by CSS :focus-within, so
           aria-expanded has to be mirrored here or assistive tech will be told
           the menu is collapsed while it is visibly open. */
        nav.querySelectorAll('.nav-drop').forEach(function (drop) {
            var btn = drop.querySelector('.nav-drop-btn');
            if (!btn) { return; }
            function sync() {
                btn.setAttribute('aria-expanded', drop.matches(':hover, :focus-within') ? 'true' : 'false');
            }
            drop.addEventListener('mouseenter', sync);
            drop.addEventListener('mouseleave', sync);
            drop.addEventListener('focusin', sync);
            drop.addEventListener('focusout', function () {
                window.setTimeout(sync, 0);
            });
            sync();
        });

        function closeNav() {
            nav.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            var open = nav.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        nav.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeNav);
        });
        document.addEventListener('click', function (event) {
            if (!nav.contains(event.target) && !toggle.contains(event.target)) { closeNav(); }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && nav.classList.contains('open')) {
                closeNav();
                toggle.focus();
            }
        });
        window.matchMedia('(min-width: 1121px)').addEventListener('change', function (event) {
            if (event.matches) { closeNav(); }
        });
    }

    function initHeaderSearch() {
        var toggle = document.getElementById('searchToggle');
        var form = document.getElementById('headerSearchForm');
        var input = document.getElementById('headerSearch');
        if (!toggle || !form) { return; }

        function setOpen(open) {
            form.classList.toggle('is-open', open);
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
            if (open && input) {
                window.setTimeout(function () { input.focus(); }, 60);
            }
        }

        toggle.addEventListener('click', function (event) {
            event.stopPropagation();
            setOpen(!form.classList.contains('is-open'));
        });
        document.addEventListener('click', function (event) {
            if (form.classList.contains('is-open') && !form.contains(event.target) && !toggle.contains(event.target)) {
                setOpen(false);
            }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && form.classList.contains('is-open')) {
                setOpen(false);
                toggle.focus();
            }
        });
    }

    function initPasswordToggle() {
        var toggle = document.querySelector('.password-toggle');
        var input = toggle ? document.getElementById(toggle.getAttribute('aria-controls')) : null;
        if (!toggle || !input) { return; }
        toggle.addEventListener('click', function () {
            var reveal = input.type === 'password';
            input.type = reveal ? 'text' : 'password';
            toggle.textContent = reveal ? 'Hide' : 'Show';
            toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
            toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
        });
    }

    function initTheme() {
        var button = document.getElementById('themeToggle');
        if (!button) { return; }
        var root = document.documentElement;

        function syncAria(next) {
            var dark = next === 'dark';
            button.setAttribute('aria-pressed', dark ? 'false' : 'true');
            button.setAttribute('aria-label', dark ? 'Switch to light theme' : 'Switch to dark theme');
        }

        syncAria(root.getAttribute('data-theme') === 'light' ? 'light' : 'dark');

        button.addEventListener('click', function () {
            var next = root.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
            document.cookie = 'fhp_session_theme=' + next + ';path=/;samesite=lax';
            announce(next === 'dark' ? 'Dark theme enabled' : 'Light theme enabled');

            /* The attribute must change INSIDE the callback, otherwise the
               "old" snapshot is captured after the swap and nothing animates. */
            if (document.startViewTransition && !reduceMotion) {
                document.startViewTransition(function () {
                    root.setAttribute('data-theme', next);
                });
            } else {
                root.setAttribute('data-theme', next);
            }
            syncAria(next);
        });
    }

    function initScrollProgress() {
        var bar = document.getElementById('scrollProgress');
        if (!bar) { return; }
        var ticking = false;

        function update() {
            var doc = document.documentElement;
            var scrollable = doc.scrollHeight - window.innerHeight;
            var ratio = scrollable > 0 ? window.scrollY / scrollable : 0;
            bar.style.transform = 'scaleX(' + Math.min(1, Math.max(0, ratio)) + ')';
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (ticking) { return; }
            ticking = true;
            window.requestAnimationFrame(update);
        }, { passive: true });
        update();
    }

    function initToTop() {
        var button = document.getElementById('toTop');
        if (!button) { return; }
        var ticking = false;

        function update() {
            button.classList.toggle('visible', window.scrollY > 420);
            ticking = false;
        }

        window.addEventListener('scroll', function () {
            if (ticking) { return; }
            ticking = true;
            window.requestAnimationFrame(update);
        }, { passive: true });

        button.addEventListener('click', function () {
            window.scrollTo({ top: 0, behavior: reduceMotion ? 'auto' : 'smooth' });
        });
        update();
    }

    /* Every block-level unit of content across the public and admin pages. */
    var REVEAL_SELECTOR = [
        '.section-head', '.card', '.cat-card', '.stat', '.hero-stats > div',
        '.detail-hero > *', '.video-carousel', '.auth-card',
        '.side', '.review', '.detail-media', '.event-detail-image',
        '.event-cover', '.filters', '.pager', 'table',
        '.sitemap-block', '.admin-page > .card', '.admin-page > .grid > *',
        '.admin-page > .dash > *', '.admin-account',
        '.spot-panel', '.spot-shot', '.spot-cos-card'
    ].join(', ');

    var REVEAL_STAGGER = 70;
    var REVEAL_MAX_STAGGER = 6;

    function initReveal() {
        var targets = Array.prototype.slice.call(document.querySelectorAll(REVEAL_SELECTOR));
        if (!targets.length) { return; }

        if (!('IntersectionObserver' in window) || reduceMotion) {
            targets.forEach(function (node) { node.classList.add('is-revealed'); });
            return;
        }

        var observer = new IntersectionObserver(function (entries) {
            entries.forEach(function (entry) {
                /* Reveal on scroll-in, or when the viewport has already jumped
                   past the element (deep link / restored scroll position). */
                if (!entry.isIntersecting && entry.boundingClientRect.top >= 0) { return; }
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 });

        /* Group by parent so a row of cards cascades left-to-right
           instead of every card popping in on the same frame. */
        var perParent = new Map();
        targets.forEach(function (node) {
            var parent = node.parentElement;
            var list = perParent.get(parent);
            if (!list) { list = []; perParent.set(parent, list); }
            list.push(node);
        });

        perParent.forEach(function (list) {
            list.forEach(function (node, index) {
                /* Already on screen: show it right away, otherwise the hero
                   would visibly flicker on first paint. */
                if (node.getBoundingClientRect().top < window.innerHeight) {
                    node.classList.add('is-revealed');
                    return;
                }
                node.style.transitionDelay = Math.min(index, REVEAL_MAX_STAGGER) * REVEAL_STAGGER + 'ms';
                node.classList.add('reveal');
                observer.observe(node);
            });
        });
    }

    function initFlashDismiss() {
        document.querySelectorAll('.flash[data-autodismiss]').forEach(function (flash) {
            window.setTimeout(function () {
                flash.classList.add('is-leaving');
                window.setTimeout(function () { flash.remove(); }, 400);
            }, 6000);
        });
    }

    window.fhpToast = function (message, type) {
        var host = document.getElementById('toastHost');
        if (!host) { return; }
        var toast = document.createElement('div');
        toast.className = 'toast toast-' + (type || 'ok');
        toast.textContent = message;
        host.appendChild(toast);
        window.requestAnimationFrame(function () { toast.classList.add('is-in'); });
        window.setTimeout(function () {
            toast.classList.remove('is-in');
            window.setTimeout(function () { toast.remove(); }, 320);
        }, 3600);
    };

    function initCarousel() {
        var root = document.getElementById('vc');
        if (!root) { return; }
        var track = document.getElementById('vcTrack');
        var slides = track.querySelectorAll('.vc-slide');
        var dots = root.querySelectorAll('.vc-dot');
        var prevBtn = root.querySelector('.vc-prev');
        var nextBtn = root.querySelector('.vc-next');
        var current = 0;
        var timer = null;
        var delay = parseInt(root.getAttribute('data-autoplay'), 10) || 6000;
        if (!slides.length) { return; }

        function goTo(index) {
            current = (index + slides.length) % slides.length;
            track.style.transform = 'translateX(-' + (current * 100) + '%)';
            dots.forEach(function (dot, i) {
                dot.classList.toggle('active', i === current);
                dot.setAttribute('aria-current', i === current ? 'true' : 'false');
            });
            resetTimer();
        }

        function resetTimer() {
            if (timer) { window.clearInterval(timer); }
            timer = window.setInterval(function () { goTo(current + 1); }, delay);
        }

        if (prevBtn) { prevBtn.addEventListener('click', function () { goTo(current - 1); }); }
        if (nextBtn) { nextBtn.addEventListener('click', function () { goTo(current + 1); }); }
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () { goTo(parseInt(dot.getAttribute('data-index'), 10)); });
        });
        root.addEventListener('mouseenter', function () {
            if (timer) { window.clearInterval(timer); }
        });
        root.addEventListener('mouseleave', function () {
            resetTimer();
        });
        root.addEventListener('focusin', function () { if (timer) { window.clearInterval(timer); } });
        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') { goTo(current - 1); }
            if (event.key === 'ArrowRight') { goTo(current + 1); }
        });

        resetTimer();
    }

    function initSpotCarousels() {
        var roots = document.querySelectorAll('[data-spot-carousel]');
        if (!roots.length) { return; }

        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        Array.prototype.forEach.call(roots, function (root) {
            var slides = root.querySelectorAll('.sg-slide');
            var videos = root.querySelectorAll('[data-bg-video]');
            var dots = root.querySelectorAll('.sg-dot');
            var prevBtn = root.querySelector('.sg-prev');
            var nextBtn = root.querySelector('.sg-next');
            var counter = root.querySelector('.sg-count b');
            var current = 0;
            var timer = null;
            var delay = parseInt(root.getAttribute('data-autoplay'), 10) || 6500;
            if (slides.length < 2) {
                /* One clip is not a carousel. Hide the controls so the tile
                   reads as a plain video with its caption. */
                if (prevBtn) { prevBtn.hidden = true; }
                if (nextBtn) { nextBtn.hidden = true; }
                var dotWrap = root.querySelector('.sg-dots');
                if (dotWrap) { dotWrap.hidden = true; }
                return;
            }

            /* Paint a slide's image only while it is the visible one. With five
               carousels on a category page this is the difference between five
               image requests and twenty. */
            function paint(slide) {
                var src = slide.getAttribute('data-image');
                if (!src) { return; }
                if (slide.getAttribute('data-painted') === '1') { return; }
                slide.style.backgroundImage = 'url("' + src + '")';
                slide.setAttribute('data-painted', '1');
            }

            /* Same lazy deal for the clips: the src is only attached to the
               visible slide, and every other slide is paused. A slide whose
               clip fails to load keeps its poster, so a bad file degrades to
               the still instead of an empty box. */
            function syncVideos() {
                Array.prototype.forEach.call(videos, function (video) {
                    if (!video.parentNode.classList.contains('is-active') || reduce || document.hidden) {
                        video.pause();
                        return;
                    }
                    if (!video.getAttribute('src')) {
                        video.setAttribute('src', video.getAttribute('data-video-src'));
                        video.load();
                    }
                    var playback = video.play();
                    if (playback && typeof playback.catch === 'function') {
                        playback.catch(function () {});
                    }
                });
            }

            function goTo(index) {
                current = (index + slides.length) % slides.length;
                slides.forEach(function (slide, i) {
                    var on = i === current;
                    slide.classList.toggle('is-active', on);
                    if (on) { paint(slide); }
                });
                syncVideos();
                dots.forEach(function (dot, i) {
                    dot.classList.toggle('active', i === current);
                    dot.setAttribute('aria-current', i === current ? 'true' : 'false');
                });
                if (counter) { counter.textContent = String(current + 1); }
                resetTimer();
            }

            function resetTimer() {
                if (timer) { window.clearInterval(timer); }
                // No autoplay under reduced motion: the visitor drives the
                // carousel with the arrows, dots, or keyboard.
                if (reduce) { return; }
                timer = window.setInterval(function () { goTo(current + 1); }, delay);
            }

            if (prevBtn) { prevBtn.addEventListener('click', function () { goTo(current - 1); }); }
            if (nextBtn) { nextBtn.addEventListener('click', function () { goTo(current + 1); }); }
            dots.forEach(function (dot) {
                dot.addEventListener('click', function () { goTo(parseInt(dot.getAttribute('data-index'), 10)); });
            });

            /* Pause while the pointer is over it or something inside it has
               focus, so somebody reading a caption is not yanked away. */
            root.addEventListener('mouseenter', function () { if (timer) { window.clearInterval(timer); } });
            root.addEventListener('mouseleave', resetTimer);
            root.addEventListener('focusin', function () { if (timer) { window.clearInterval(timer); } });
            document.addEventListener('visibilitychange', syncVideos);
            root.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowLeft') { goTo(current - 1); }
                if (event.key === 'ArrowRight') { goTo(current + 1); }
            });

            /* Stop the timer once the carousel scrolls out of view, and let go
               of the clip playing inside it. */
            if ('IntersectionObserver' in window && !reduce) {
                new IntersectionObserver(function (entries) {
                    Array.prototype.forEach.call(entries, function (entry) {
                        if (entry.isIntersecting) {
                            resetTimer();
                        } else {
                            if (timer) {
                                window.clearInterval(timer);
                                timer = null;
                            }
                            Array.prototype.forEach.call(videos, function (video) { video.pause(); });
                        }
                    });
                }, { threshold: 0.25 }).observe(root);
            } else {
                resetTimer();
            }

            goTo(0);
        });
    }

    function initFandomSlider() {
        var root = document.getElementById('heroSlider');
        if (!root) { return; }
        var slides = root.querySelectorAll('.hs-slide');
        var videos = root.querySelectorAll('[data-bg-video]');
        var dots = root.querySelectorAll('.hs-dot');
        var prevBtn = root.querySelector('.hs-prev');
        var nextBtn = root.querySelector('.hs-next');
        var counter = root.querySelector('.hs-count b');
        var current = 0;
        var timer = null;
        var delay = parseInt(root.getAttribute('data-autoplay'), 10) || 5500;
        if (!slides.length) { return; }

        var reduce = window.matchMedia && window.matchMedia('(prefers-reduced-motion: reduce)').matches;

        function syncBackgroundVideos() {
            videos.forEach(function (video, index) {
                var isYoutube = video.getAttribute('data-video-kind') === 'youtube';
                if (index !== current || reduce || document.hidden) {
                    if (isYoutube) {
                        if (video.getAttribute('src')) { video.removeAttribute('src'); }
                    } else {
                        video.pause();
                    }
                    return;
                }
                if (isYoutube) {
                    if (!video.getAttribute('src')) {
                        video.setAttribute('src', video.getAttribute('data-video-src'));
                    }
                    return;
                }
                if (!video.getAttribute('src')) {
                    video.setAttribute('src', video.getAttribute('data-video-src'));
                    video.load();
                }
                var playback = video.play();
                if (playback && typeof playback.catch === 'function') {
                    playback.catch(function () {});
                }
            });
        }

        function goTo(index) {
            current = (index + slides.length) % slides.length;
            slides.forEach(function (slide, i) { slide.classList.toggle('is-active', i === current); });
            syncBackgroundVideos();
            dots.forEach(function (dot, i) {
                dot.classList.toggle('active', i === current);
                dot.setAttribute('aria-current', i === current ? 'true' : 'false');
            });
            if (counter) { counter.textContent = String(current + 1); }
            resetTimer();
        }

        function resetTimer() {
            if (timer) { window.clearInterval(timer); }
            // No autoplay under reduced motion: the visitor drives the slider
            // with the arrows, dots, or keyboard.
            if (reduce) { return; }
            timer = window.setInterval(function () { goTo(current + 1); }, delay);
        }

        if (prevBtn) { prevBtn.addEventListener('click', function () { goTo(current - 1); }); }
        if (nextBtn) { nextBtn.addEventListener('click', function () { goTo(current + 1); }); }
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () { goTo(parseInt(dot.getAttribute('data-index'), 10)); });
        });

        /* Pause while the pointer is over the band or something inside it has
           focus, so a visitor reading a caption is not yanked away. */
        root.addEventListener('mouseenter', function () { if (timer) { window.clearInterval(timer); } });
        root.addEventListener('mouseleave', function () { resetTimer(); });
        root.addEventListener('focusin', function () { if (timer) { window.clearInterval(timer); } });
        document.addEventListener('visibilitychange', syncBackgroundVideos);
        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') { goTo(current - 1); }
            if (event.key === 'ArrowRight') { goTo(current + 1); }
        });
        /* Pause slide rotation when the carousel is offscreen. */
        if ('IntersectionObserver' in window && !reduce) {
            new IntersectionObserver(function (entries) {
                entries.forEach(function (entry) {
                    if (entry.isIntersecting) {
                        resetTimer();
                    } else if (timer) {
                        window.clearInterval(timer);
                        timer = null;
                    }
                });
            }, { threshold: 0.25 }).observe(root);
        } else {
            resetTimer();
        }
    }


    function initAdminSidebar() {
        var toggle = document.getElementById('sidebarToggle');
        var sidebar = document.getElementById('adminSidebar');
        if (!toggle || !sidebar) { return; }

        function closeSidebar() {
            sidebar.classList.remove('open');
            toggle.setAttribute('aria-expanded', 'false');
        }

        toggle.addEventListener('click', function () {
            var open = sidebar.classList.toggle('open');
            toggle.setAttribute('aria-expanded', open ? 'true' : 'false');
        });
        sidebar.querySelectorAll('a').forEach(function (link) {
            link.addEventListener('click', closeSidebar);
        });
        document.addEventListener('click', function (event) {
            if (!sidebar.contains(event.target) && !toggle.contains(event.target)) { closeSidebar(); }
        });
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && sidebar.classList.contains('open')) {
                closeSidebar();
                toggle.focus();
            }
        });
    }

    function initShareBar() {
        var bars = document.querySelectorAll('[data-share-bar]');
        if (!bars.length) { return; }
        var pageUrl = window.location.href;
        var pageTitle = document.title || 'Fan Hub Plus';
        bars.forEach(function (bar) {
            var copyBtn = bar.querySelector('[data-share-copy]');
            var waLink = bar.querySelector('[data-share-wa]');
            var xLink = bar.querySelector('[data-share-x]');
            if (waLink) {
                waLink.href = 'https://api.whatsapp.com/send?text=' + encodeURIComponent(pageTitle + ' - ' + pageUrl);
            }
            if (xLink) {
                xLink.href = 'https://twitter.com/intent/tweet?text=' + encodeURIComponent(pageTitle) + '&url=' + encodeURIComponent(pageUrl);
            }
            if (copyBtn) {
                copyBtn.addEventListener('click', function () {
                    var orig = copyBtn.innerHTML;
                    if (navigator.clipboard && navigator.clipboard.writeText) {
                        navigator.clipboard.writeText(pageUrl).then(function () {
                            copyBtn.textContent = '✓ Link Copied!';
                            setTimeout(function () { copyBtn.innerHTML = orig; }, 2000);
                        });
                    } else {
                        copyBtn.textContent = '✓ Copied!';
                        setTimeout(function () { copyBtn.innerHTML = orig; }, 2000);
                    }
                });
            }
        });
    }

    function initAssistant() {
        var panel = document.getElementById('assistantPanel');
        var open = document.getElementById('assistantOpen');
        var close = document.getElementById('assistantClose');
        var clearBtn = document.getElementById('assistantClear');
        var suggestions = document.getElementById('assistantSuggestions');
        var form = document.getElementById('assistantForm');
        var input = document.getElementById('assistantInput');
        var messages = document.getElementById('assistantMessages');
        if (!panel || !open || !form || !input || !messages) { return; }

        var endpoint = form.getAttribute('action') || 'assistant.php';
        var storageKey = 'fhp_chat_history_v2';
        var historyLog = [];
        try { sessionStorage.removeItem('fhp_chat_history'); } catch (e) {}

        function saveHistory() {
            try {
                sessionStorage.setItem(storageKey, JSON.stringify(historyLog.slice(-24)));
            } catch (e) {}
        }

        function setOpen(value) {
            panel.hidden = !value;
            open.setAttribute('aria-expanded', value ? 'true' : 'false');
            if (value) { input.focus(); }
            else { open.focus(); }
        }
        function addMessage(text, kind, items, skipSave) {
            var bubble = document.createElement('div');
            bubble.className = 'assistant-message ' + (kind === 'user' ? 'assistant-user' : 'assistant-bot');
            var copy = document.createElement('p');
            copy.textContent = text;
            bubble.appendChild(copy);
            (items || []).forEach(function (item) {
                if (!item.href || item.href.indexOf('/') !== 0 || item.href.indexOf('//') === 0) { return; }
                var link = document.createElement('a');
                link.href = item.href;
                link.textContent = item.title + ' →';
                bubble.appendChild(link);
                if (item.label) {
                    var meta = document.createElement('small');
                    meta.textContent = item.label;
                    bubble.appendChild(meta);
                }
            });
            messages.appendChild(bubble);
            messages.scrollTop = messages.scrollHeight;
            if (!skipSave) {
                historyLog.push({ text: text, kind: kind, items: items || [] });
                saveHistory();
            }
            return bubble;
        }

        // Restore persisted session chat history if present
        try {
            var saved = JSON.parse(sessionStorage.getItem(storageKey) || '[]');
            if (Array.isArray(saved) && saved.length > 0) {
                messages.innerHTML = '';
                saved.forEach(function (entry) {
                    historyLog.push(entry);
                    addMessage(entry.text, entry.kind, entry.items, true);
                });
            }
        } catch (e) {}

        function sendQuestion(question) {
            question = question.trim();
            if (!question) { return; }
            addMessage(question, 'user');
            input.value = '';
            input.disabled = true;
            var pending = addMessage('AI is thinking…', 'bot', [], true);
            pending.classList.add('is-typing');
            var payload = new URLSearchParams();
            payload.set('message', question);
            fetch(endpoint, {
                method: 'POST',
                headers: { 'Content-Type': 'application/x-www-form-urlencoded;charset=UTF-8' },
                body: payload.toString(),
                credentials: 'same-origin'
            }).then(function (response) {
                return response.json().then(function (data) {
                    if (!response.ok) { throw new Error(data.answer || 'Please try again in a moment.'); }
                    return data;
                });
            }).then(function (data) {
                pending.remove();
                addMessage(data.answer, 'bot', data.items);
            }).catch(function (error) {
                pending.remove();
                addMessage(error.message || 'I could not connect just now. Please try again.', 'bot', [], true);
            }).finally(function () {
                input.disabled = false;
                input.focus();
            });
        }

        open.addEventListener('click', function () { setOpen(panel.hidden); });
        if (close) { close.addEventListener('click', function () { setOpen(false); }); }
        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                messages.innerHTML = '';
                historyLog = [];
                try { sessionStorage.removeItem(storageKey); } catch (e) {}
                addMessage('Chat cleared! Ask me anything about Anime, Cartoons, K-Pop, or Fan Hub Plus.', 'bot', [], true);
            });
        }
        if (suggestions) {
            suggestions.querySelectorAll('.assistant-chip').forEach(function (chip) {
                chip.addEventListener('click', function () {
                    var promptText = chip.getAttribute('data-prompt');
                    if (promptText && !input.disabled) {
                        sendQuestion(promptText);
                    }
                });
            });
        }
        document.addEventListener('keydown', function (event) {
            if (event.key === 'Escape' && !panel.hidden) { setOpen(false); }
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendQuestion(input.value);
        });
    }

    function boot() {
        initNav();
        initHeaderSearch();
        initPasswordToggle();
        initTheme();
        initScrollProgress();
        initToTop();
        initReveal();
        initFlashDismiss();
        initFandomSlider();
        initSpotCarousels();
        initCarousel();
        initAdminSidebar();
        initShareBar();
        initAssistant();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
