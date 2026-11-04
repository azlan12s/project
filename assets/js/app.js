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
        var toggles = document.querySelectorAll('.password-toggle');
        if (!toggles || toggles.length === 0) { return; }
        Array.prototype.slice.call(toggles).forEach(function (toggle) {
            var input = document.getElementById(toggle.getAttribute('aria-controls'));
            if (!input) { return; }
            toggle.addEventListener('click', function () {
                var reveal = input.type === 'password';
                input.type = reveal ? 'text' : 'password';
                toggle.textContent = reveal ? 'Hide' : 'Show';
                toggle.setAttribute('aria-label', reveal ? 'Hide password' : 'Show password');
                toggle.setAttribute('aria-pressed', reveal ? 'true' : 'false');
            });
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

                if (!entry.isIntersecting && entry.boundingClientRect.top >= 0) { return; }
                entry.target.classList.add('is-revealed');
                observer.unobserve(entry.target);
            });
        }, { rootMargin: '0px 0px -6% 0px', threshold: 0.05 });

        var perParent = new Map();
        targets.forEach(function (node) {
            var parent = node.parentElement;
            var list = perParent.get(parent);
            if (!list) { list = []; perParent.set(parent, list); }
            list.push(node);
        });

        perParent.forEach(function (list) {
            list.forEach(function (node, index) {

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

                if (prevBtn) { prevBtn.hidden = true; }
                if (nextBtn) { nextBtn.hidden = true; }
                var dotWrap = root.querySelector('.sg-dots');
                if (dotWrap) { dotWrap.hidden = true; }
                return;
            }

            function paint(slide) {
                var src = slide.getAttribute('data-image');
                if (!src) { return; }
                if (slide.getAttribute('data-painted') === '1') { return; }
                slide.style.backgroundImage = 'url("' + src + '")';
                slide.setAttribute('data-painted', '1');
            }

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

                if (reduce) { return; }
                timer = window.setInterval(function () { goTo(current + 1); }, delay);
            }

            if (prevBtn) { prevBtn.addEventListener('click', function () { goTo(current - 1); }); }
            if (nextBtn) { nextBtn.addEventListener('click', function () { goTo(current + 1); }); }
            dots.forEach(function (dot) {
                dot.addEventListener('click', function () { goTo(parseInt(dot.getAttribute('data-index'), 10)); });
            });

            root.addEventListener('mouseenter', function () { if (timer) { window.clearInterval(timer); } });
            root.addEventListener('mouseleave', resetTimer);
            root.addEventListener('focusin', function () { if (timer) { window.clearInterval(timer); } });
            document.addEventListener('visibilitychange', syncVideos);
            root.addEventListener('keydown', function (event) {
                if (event.key === 'ArrowLeft') { goTo(current - 1); }
                if (event.key === 'ArrowRight') { goTo(current + 1); }
            });

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

            if (reduce) { return; }
            timer = window.setInterval(function () { goTo(current + 1); }, delay);
        }

        if (prevBtn) { prevBtn.addEventListener('click', function () { goTo(current - 1); }); }
        if (nextBtn) { nextBtn.addEventListener('click', function () { goTo(current + 1); }); }
        dots.forEach(function (dot) {
            dot.addEventListener('click', function () { goTo(parseInt(dot.getAttribute('data-index'), 10)); });
        });

        root.addEventListener('mouseenter', function () { if (timer) { window.clearInterval(timer); } });
        root.addEventListener('mouseleave', function () { resetTimer(); });
        root.addEventListener('focusin', function () { if (timer) { window.clearInterval(timer); } });
        document.addEventListener('visibilitychange', syncBackgroundVideos);
        root.addEventListener('keydown', function (event) {
            if (event.key === 'ArrowLeft') { goTo(current - 1); }
            if (event.key === 'ArrowRight') { goTo(current + 1); }
        });

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
        document.addEventListener('click', function (event) {
            if (!panel.hidden) {
                if (!panel.contains(event.target) && !open.contains(event.target)) {
                    setOpen(false);
                }
            }
        });
        form.addEventListener('submit', function (event) {
            event.preventDefault();
            sendQuestion(input.value);
        });
    }

    function initFormValidation() {
        var EMAIL_REGEX = /^[a-zA-Z0-9.!#$%&'*+/=?^_`{|}~-]+@[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?(?:\.[a-zA-Z0-9](?:[a-zA-Z0-9-]{0,61}[a-zA-Z0-9])?)+$/;
        var NAME_REGEX = /^[a-zA-Z\s\.\-']{2,60}$/;
        var STRONG_PWD_REGEX = /^(?=.*[a-z])(?=.*[A-Z])(?=.*\d)(?=.*[!@#$%^&*()_+\-=\[\]{};':"\\|,.<>\/?~` ]).{8,}$/;
        var SLUG_REGEX = /^[a-z0-9]+(?:-[a-z0-9]+)*$/;
        var URL_REGEX = /^https?:\/\/.+/i;
        var YEAR_REGEX = /^(19\d\d|20\d\d)$/;
        var PRICE_REGEX = /^\d+(\.\d{1,2})?$/;

        function getTargetContainer(field) {
            return field.closest('.password-field') || field;
        }

        function getErrorElement(field) {
            var key = field.id || field.name || 'field';
            var parent = field.closest('.form-row, .card-body') || field.parentNode;
            return parent ? parent.querySelector('.field-error-msg[data-for="' + key + '"]') : null;
        }

        function showError(field, message) {
            field.classList.add('is-invalid');
            field.classList.remove('is-valid');
            field.setAttribute('aria-invalid', 'true');
            var container = getTargetContainer(field);
            if (container !== field) {
                container.classList.add('is-invalid');
                container.classList.remove('is-valid');
            }

            var key = field.id || field.name || 'field';
            var errorEl = getErrorElement(field);
            if (!errorEl) {
                errorEl = document.createElement('div');
                errorEl.className = 'field-error-msg';
                errorEl.setAttribute('data-for', key);
                errorEl.setAttribute('role', 'alert');
                errorEl.innerHTML = '<svg viewBox="0 0 16 16" width="14" height="14" fill="currentColor" aria-hidden="true"><path d="M8 1a7 7 0 1 0 7 7A7 7 0 0 0 8 1Zm0 10.5a.9.9 0 1 1 .9-.9.9.9 0 0 1-.9.9Zm.9-3.4a.9.9 0 0 1-1.8 0V4.5a.9.9 0 0 1 1.8 0Z"/></svg><span></span>';
                container.insertAdjacentElement('afterend', errorEl);
            }
            errorEl.querySelector('span').textContent = message;
            errorEl.style.display = 'flex';
        }

        function clearError(field, markValid) {
            field.classList.remove('is-invalid');
            field.removeAttribute('aria-invalid');
            var container = getTargetContainer(field);
            if (container !== field) {
                container.classList.remove('is-invalid');
            }

            if (markValid) {
                field.classList.add('is-valid');
                if (container !== field) {
                    container.classList.add('is-valid');
                }
            } else {
                field.classList.remove('is-valid');
                if (container !== field) {
                    container.classList.remove('is-valid');
                }
            }

            var errorEl = getErrorElement(field);
            if (errorEl) {
                errorEl.remove();
            }
        }

        function validateField(field) {
            if (field.disabled || field.type === 'hidden' || field.type === 'submit' || field.type === 'button') {
                return { valid: true };
            }

            var form = field.form;
            var val = (field.value || '').trim();
            var name = (field.name || '').toLowerCase();
            var type = (field.type || '').toLowerCase();
            var id = (field.id || '').toLowerCase();

            if (field.required && !val) {
                if (type === 'email' || name === 'email') {
                    return { valid: false, message: 'Email address is required.' };
                }
                if (type === 'password' || name.indexOf('password') !== -1) {
                    return { valid: false, message: 'Password is required.' };
                }
                if (field.tagName === 'SELECT') {
                    return { valid: false, message: 'Please select an option from the list.' };
                }
                if (field.tagName === 'TEXTAREA') {
                    return { valid: false, message: 'This field cannot be empty.' };
                }
                return { valid: false, message: (field.title || 'This field is required.') };
            }

            if (!val && !field.required) {
                return { valid: true, optionalEmpty: true };
            }

            if (field.pattern) {
                try {
                    var customReg = new RegExp('^(?:' + field.pattern + ')$');
                    if (!customReg.test(val)) {
                        return { valid: false, message: field.title || field.getAttribute('data-error-msg') || 'Input does not match the required format.' };
                    }
                } catch (e) {}
            }

            if (type === 'email' || name === 'email') {
                if (!EMAIL_REGEX.test(val)) {
                    return { valid: false, message: 'Please enter a valid email address (e.g. name@example.com).' };
                }
            }

            if (name === 'password' || name === 'new_password') {
                var isRegistrationOrNew = (name === 'new_password') ||
                    (window.location.pathname.indexOf('register.php') !== -1) ||
                    (window.location.pathname.indexOf('forgot-password.php') !== -1) ||
                    (form && form.classList.contains('auth-split--join'));

                if (isRegistrationOrNew) {
                    if (!STRONG_PWD_REGEX.test(val)) {
                        return { valid: false, message: 'Must be 8+ chars with uppercase, lowercase, number, and special symbol.' };
                    }
                } else if (val.length < 1) {
                    return { valid: false, message: 'Please enter your password.' };
                }
            }

            if (name === 'confirm_password') {
                var newPwd = form ? form.querySelector('[name="new_password"], [name="password"]') : null;
                if (newPwd && val !== newPwd.value) {
                    return { valid: false, message: 'Passwords do not match.' };
                }
            }

            if (name === 'name' || id === 'admin-name') {
                if (!NAME_REGEX.test(val)) {
                    return { valid: false, message: 'Name must be 2-60 characters and contain only letters, dots, hyphens, and spaces.' };
                }
            }

            if (name === 'slug') {
                if (!SLUG_REGEX.test(val)) {
                    return { valid: false, message: 'Slug must be lowercase alphanumeric with hyphens (e.g. my-category).' };
                }
            }

            if (name === 'title' || id === 'article-title') {
                if (val.length < 3) {
                    return { valid: false, message: 'Title must be at least 3 characters long.' };
                }
            }

            if (field.tagName === 'TEXTAREA' || name === 'message' || name === 'body') {
                var minL = parseInt(field.getAttribute('minlength') || (field.required ? '10' : '0'), 10);
                if (minL > 0 && val.length < minL) {
                    return { valid: false, message: 'Please provide at least ' + minL + ' characters (currently ' + val.length + ').' };
                }
            }

            if (type === 'url' || name.indexOf('url') !== -1 || name === 'trailer') {
                if (!URL_REGEX.test(val)) {
                    return { valid: false, message: 'Please enter a valid URL starting with http:// or https://' };
                }
            }

            if (name === 'release_year' || name === 'year') {
                if (!YEAR_REGEX.test(val)) {
                    return { valid: false, message: 'Please enter a valid 4-digit year (1900-2099).' };
                }
            }

            if (name === 'price') {
                if (!PRICE_REGEX.test(val)) {
                    return { valid: false, message: 'Please enter a valid price (e.g. 19.99).' };
                }
            }

            if (field.minLength && field.minLength > 0 && val.length < field.minLength) {
                return { valid: false, message: 'Must be at least ' + field.minLength + ' characters.' };
            }

            return { valid: true };
        }

        var forms = document.querySelectorAll('form');
        forms.forEach(function (form) {
            if (form.id === 'assistantForm' || form.id === 'headerSearchForm') {
                return;
            }

            form.setAttribute('novalidate', 'true');

            var inputs = form.querySelectorAll('input, select, textarea');
            inputs.forEach(function (input) {
                if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button') {
                    return;
                }

                input.addEventListener('blur', function () {
                    input._blurred = true;
                    var res = validateField(input);
                    if (!res.valid) {
                        showError(input, res.message);
                    } else {
                        clearError(input, !res.optionalEmpty);
                    }
                });

                input.addEventListener('input', function () {
                    if (input._blurred || input.classList.contains('is-invalid')) {
                        var res = validateField(input);
                        if (!res.valid) {
                            showError(input, res.message);
                        } else {
                            clearError(input, !res.optionalEmpty);
                        }
                    }
                });

                input.addEventListener('change', function () {
                    var res = validateField(input);
                    if (!res.valid) {
                        showError(input, res.message);
                    } else {
                        clearError(input, !res.optionalEmpty);
                    }
                });
            });

            form.addEventListener('submit', function (event) {
                var firstInvalid = null;
                var formIsValid = true;

                inputs.forEach(function (input) {
                    if (input.type === 'hidden' || input.type === 'submit' || input.type === 'button' || input.disabled) {
                        return;
                    }
                    input._blurred = true;
                    var res = validateField(input);
                    if (!res.valid) {
                        showError(input, res.message);
                        formIsValid = false;
                        if (!firstInvalid) {
                            firstInvalid = input;
                        }
                    } else {
                        clearError(input, !res.optionalEmpty);
                    }
                });

                if (!formIsValid) {
                    event.preventDefault();
                    event.stopImmediatePropagation();
                    if (firstInvalid) {
                        var container = getTargetContainer(firstInvalid);
                        container.classList.add('field-shake');
                        setTimeout(function () {
                            container.classList.remove('field-shake');
                        }, 400);
                        firstInvalid.focus();
                        firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                    }
                }
            });
        });
    }

    function initCartSystem() {
        var CART_KEY = 'fhp_user_cart_v1';
        var drawer = document.getElementById('sideCartDrawer');
        var backdrop = document.getElementById('sideCartBackdrop');
        var floatBtn = document.getElementById('floatingSideCart');
        var closeBtn = document.getElementById('sideCartClose');
        var clearBtn = document.getElementById('sideCartClear');
        var checkoutBtn = document.getElementById('sideCartCheckout');
        var itemsContainer = document.getElementById('sideCartItems');
        var badgeCount = document.getElementById('sideCartCount');
        var drawerCount = document.getElementById('sideCartDrawerCount');
        var subtotalEl = document.getElementById('sideCartSubtotal');
        var totalEl = document.getElementById('sideCartTotal');

        function loadCart() {
            try {
                return JSON.parse(localStorage.getItem(CART_KEY) || '[]');
            } catch (e) {
                return [];
            }
        }

        function saveCart(cart) {
            try {
                localStorage.setItem(CART_KEY, JSON.stringify(cart));
            } catch (e) {}
            renderCart();
        }

        function openDrawer() {
            if (drawer) {
                drawer.classList.add('open');
                drawer.setAttribute('aria-hidden', 'false');
            }
            if (backdrop) {
                backdrop.classList.add('open');
            }
        }

        function closeDrawer() {
            if (drawer) {
                drawer.classList.remove('open');
                drawer.setAttribute('aria-hidden', 'true');
            }
            if (backdrop) {
                backdrop.classList.remove('open');
            }
        }

        window.openSideCart = openDrawer;
        window.closeSideCart = closeDrawer;

        function renderCart() {
            var cart = loadCart();
            var totalItems = 0;
            var subtotal = 0;

            cart.forEach(function (item) {
                totalItems += (item.qty || 1);
                subtotal += (parseFloat(item.price) || 0) * (item.qty || 1);
            });

            if (badgeCount) { badgeCount.textContent = totalItems; }
            if (drawerCount) { drawerCount.textContent = totalItems + (totalItems === 1 ? ' item' : ' items'); }
            document.querySelectorAll('.page-cart-count').forEach(function (el) {
                el.textContent = totalItems;
            });

            if (subtotalEl) { subtotalEl.textContent = '$' + subtotal.toFixed(2); }
            if (totalEl) { totalEl.textContent = '$' + subtotal.toFixed(2); }

            if (!itemsContainer) { return; }

            if (cart.length === 0) {
                itemsContainer.innerHTML = '<div class="side-cart-empty"><div class="side-cart-empty-icon">🛒</div><p>Your fandom cart is currently empty.</p><a href="merch.php" class="btn" style="display:inline-block;margin-top:12px;padding:8px 18px;">Explore Merch Store</a></div>';
                if (checkoutBtn) { checkoutBtn.disabled = true; }
                return;
            }

            if (checkoutBtn) { checkoutBtn.disabled = false; }

            var html = '';
            cart.forEach(function (item) {
                var itemTotal = ((parseFloat(item.price) || 0) * (item.qty || 1)).toFixed(2);
                html += '<div class="cart-item-card" data-id="' + item.id + '">' +
                    '<div class="cart-item-thumb" style="background-image:url(\'' + (item.image || '') + '\')"></div>' +
                    '<div class="cart-item-details">' +
                        '<h4>' + (item.title || 'Merch Item') + '</h4>' +
                        '<div class="cart-item-price">$' + item.price + ' <small style="color:var(--muted);font-weight:normal;">(Total: $' + itemTotal + ')</small></div>' +
                        '<div class="cart-item-qty-row">' +
                            '<button type="button" class="cart-item-qty-btn cart-qty-minus" data-id="' + item.id + '" aria-label="Decrease quantity">-</button>' +
                            '<span class="cart-item-qty-num">' + (item.qty || 1) + '</span>' +
                            '<button type="button" class="cart-item-qty-btn cart-qty-plus" data-id="' + item.id + '" aria-label="Increase quantity">+</button>' +
                        '</div>' +
                    '</div>' +
                    '<button type="button" class="cart-item-remove" data-id="' + item.id + '" title="Remove item" aria-label="Remove item">&times;</button>' +
                '</div>';
            });

            itemsContainer.innerHTML = html;

            itemsContainer.querySelectorAll('.cart-qty-minus').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-id');
                    updateItemQty(id, -1);
                });
            });

            itemsContainer.querySelectorAll('.cart-qty-plus').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-id');
                    updateItemQty(id, 1);
                });
            });

            itemsContainer.querySelectorAll('.cart-item-remove').forEach(function (btn) {
                btn.addEventListener('click', function () {
                    var id = btn.getAttribute('data-id');
                    removeItem(id);
                });
            });
        }

        function addToCart(newItem, qtyToAdd) {
            qtyToAdd = parseInt(qtyToAdd, 10) || 1;
            var cart = loadCart();
            var existing = null;
            for (var i = 0; i < cart.length; i++) {
                if (String(cart[i].id) === String(newItem.id)) {
                    existing = cart[i];
                    break;
                }
            }

            if (existing) {
                existing.qty = (existing.qty || 1) + qtyToAdd;
            } else {
                newItem.qty = qtyToAdd;
                cart.push(newItem);
            }

            saveCart(cart);
            if (window.toast) {
                window.toast('Added "' + newItem.title + '" to your cart!', 'ok');
            }
            openDrawer();
        }

        function updateItemQty(id, delta) {
            var cart = loadCart();
            for (var i = 0; i < cart.length; i++) {
                if (String(cart[i].id) === String(id)) {
                    cart[i].qty = (cart[i].qty || 1) + delta;
                    if (cart[i].qty <= 0) {
                        cart.splice(i, 1);
                    }
                    break;
                }
            }
            saveCart(cart);
        }

        function removeItem(id) {
            var cart = loadCart();
            cart = cart.filter(function (item) {
                return String(item.id) !== String(id);
            });
            saveCart(cart);
            if (window.toast) {
                window.toast('Item removed from cart.', 'info');
            }
        }

        if (floatBtn) {
            floatBtn.addEventListener('click', function () {
                if (drawer && drawer.classList.contains('open')) {
                    closeDrawer();
                } else {
                    openDrawer();
                }
            });
        }

        if (closeBtn) { closeBtn.addEventListener('click', closeDrawer); }
        if (backdrop) { backdrop.addEventListener('click', closeDrawer); }

        document.addEventListener('keydown', function (e) {
            if (e.key === 'Escape' && drawer && drawer.classList.contains('open')) {
                closeDrawer();
            }
        });

        if (clearBtn) {
            clearBtn.addEventListener('click', function () {
                saveCart([]);
                if (window.toast) { window.toast('Cart cleared.', 'info'); }
            });
        }

        if (checkoutBtn) {
            checkoutBtn.addEventListener('click', function () {
                var cart = loadCart();
                if (!cart || cart.length === 0) {
                    if (window.toast) { window.toast('Your cart is empty!', 'info'); }
                    return;
                }

                if (!window.FHP_USER) {
                    if (window.toast) {
                        window.toast('Please log in or register to complete your order.', 'error');
                    }
                    var loginUrl = (window.FHP_URLS && window.FHP_URLS.login)
                        ? window.FHP_URLS.login
                        : ((window.FHP_BASE && window.FHP_BASE !== '') ? (window.FHP_BASE + '/login.php') : 'login.php');
                    var registerUrl = (window.FHP_URLS && window.FHP_URLS.register)
                        ? window.FHP_URLS.register
                        : ((window.FHP_BASE && window.FHP_BASE !== '') ? (window.FHP_BASE + '/register.php') : 'register.php');

                    itemsContainer.innerHTML = '<div class="cart-auth-gate">' +
                        '<h4>Sign in to Checkout</h4>' +
                        '<p>You must be a registered member to place Cash on Delivery merchandise orders.</p>' +
                        '<div class="cart-auth-btns">' +
                            '<a href="' + loginUrl + '?next=' + encodeURIComponent('checkout.php') + '" class="btn" style="padding:9px 18px;">Log In</a>' +
                            '<a href="' + registerUrl + '?next=' + encodeURIComponent('checkout.php') + '" class="btn-ghost" style="padding:9px 18px;">Register</a>' +
                        '</div>' +
                        '<button type="button" class="btn-ghost" id="backToCartBtn" style="margin-top:14px;font-size:0.8rem;border:none;cursor:pointer;">&larr; Back to cart items</button>' +
                    '</div>';
                    var backBtn = document.getElementById('backToCartBtn');
                    if (backBtn) { backBtn.addEventListener('click', renderCart); }
                    return;
                }

                var targetCheckoutUrl = (window.FHP_URLS && window.FHP_URLS.checkout)
                    ? window.FHP_URLS.checkout
                    : ((window.FHP_BASE && window.FHP_BASE !== '') ? (window.FHP_BASE + '/checkout.php') : 'checkout.php');
                window.location.href = targetCheckoutUrl;
            });
        }

        document.addEventListener('click', function (e) {
            var btn = e.target.closest('.merch-add-btn');
            if (btn) {
                e.preventDefault();
                var item = {
                    id: btn.getAttribute('data-id'),
                    title: btn.getAttribute('data-title'),
                    price: btn.getAttribute('data-price'),
                    image: btn.getAttribute('data-image'),
                    cat: btn.getAttribute('data-cat')
                };
                addToCart(item, 1);
            }
        });

        var detailAddBtn = document.getElementById('detailAddToCart');
        var detailQtyInput = document.getElementById('detailQty');
        var qtyMinus = document.getElementById('qtyMinus');
        var qtyPlus = document.getElementById('qtyPlus');

        if (qtyMinus && detailQtyInput) {
            qtyMinus.addEventListener('click', function () {
                var cur = parseInt(detailQtyInput.value, 10) || 1;
                if (cur > 1) { detailQtyInput.value = cur - 1; }
            });
        }
        if (qtyPlus && detailQtyInput) {
            qtyPlus.addEventListener('click', function () {
                var cur = parseInt(detailQtyInput.value, 10) || 1;
                if (cur < 99) { detailQtyInput.value = cur + 1; }
            });
        }
        if (detailAddBtn && detailQtyInput) {
            detailAddBtn.addEventListener('click', function () {
                var qty = parseInt(detailQtyInput.value, 10) || 1;
                var item = {
                    id: detailAddBtn.getAttribute('data-id'),
                    title: detailAddBtn.getAttribute('data-title'),
                    price: detailAddBtn.getAttribute('data-price'),
                    image: detailAddBtn.getAttribute('data-image'),
                    cat: detailAddBtn.getAttribute('data-cat')
                };
                addToCart(item, qty);
            });
        }

        renderCart();
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
        initFormValidation();
        initCartSystem();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot);
    } else {
        boot();
    }
})();
