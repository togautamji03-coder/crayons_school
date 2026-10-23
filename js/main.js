
/* =========================================================
   SCHOOL NAME TYPEWRITER - VANILLA JS FALLBACK
   Runs independently of jQuery so the desktop header works even
   when another desktop script/load order prevents the jQuery block.
   ========================================================= */
(function () {
    function clean(v) {
        return String(v || '').replace(/\s+/g, ' ').trim();
    }

    function phrases(v) {
        return String(v || '')
            .split(/[,\n]+/)
            .map(clean)
            .filter(Boolean)
            .sort(function (a, b) { return b.length - a.length; });
    }

    function render(el, settings) {
        if (!el || el.getAttribute('data-typewriter-running') === '1') return;

        var text = clean(settings.school_name || el.textContent);
        if (!text) return;

        var small = phrases(settings.small_words);
        var bold = phrases(settings.bold_words);
        var lower = text.toLowerCase();
        var smallStart = -1;

        // Use the first small phrase as the split point. All remaining
        // small words/phrases stay together on ONE second line.
        for (var i = 0; i < small.length; i++) {
            var pos = lower.indexOf(small[i].toLowerCase());
            if (pos >= 0 && (smallStart < 0 || pos < smallStart)) smallStart = pos;
        }

        var first = smallStart >= 0 ? text.slice(0, smallStart).trim() : text;
        var second = smallStart >= 0 ? text.slice(smallStart).trim() : '';

        el.setAttribute('data-typewriter-running', '1');
        el.classList.add('school-name-typewriter');
        el.setAttribute('aria-label', text);
        el.innerHTML = '';

        var line1 = document.createElement('span');
        line1.className = 'school-name-typewriter-line school-name-typewriter-line-main';
        el.appendChild(line1);

        var line2 = null;
        if (second) {
            line2 = document.createElement('span');
            line2.className = 'school-name-typewriter-line school-name-typewriter-line-small';
            el.appendChild(line2);
        }

        var all = [];
        var content = first + (second ? second : '');
        for (var c = 0; c < content.length; c++) {
            var ch = content.charAt(c);
            var target = (second && c >= first.length) ? line2 : line1;
            var span = document.createElement('span');
            span.className = 'school-name-char';
            span.textContent = ch;
            span.style.visibility = 'hidden';

            var absoluteIndex = second && c >= first.length
                ? smallStart + (c - first.length)
                : c;
            var charLower = text.charAt(absoluteIndex).toLowerCase();
            var isBold = false;
            for (var b = 0; b < bold.length; b++) {
                var bp = lower.indexOf(bold[b].toLowerCase());
                if (bp >= 0 && absoluteIndex >= bp && absoluteIndex < bp + bold[b].length) {
                    isBold = true;
                    break;
                }
            }
            if (isBold) span.classList.add('school-name-char-bold');
            target.appendChild(span);
            all.push(span);
        }

        var n = 0;
        var typeSpeed = 75;
        var deleteSpeed = 45;
        var fullPause = 3000;
        var restartPause = 700;

        function typeNext() {
            if (n < all.length) {
                all[n].style.visibility = 'visible';
                n++;
                setTimeout(typeNext, typeSpeed);
            } else {
                setTimeout(deleteNext, fullPause);
            }
        }
        function deleteNext() {
            if (n > 0) {
                n--;
                all[n].style.visibility = 'hidden';
                setTimeout(deleteNext, deleteSpeed);
            } else {
                setTimeout(typeNext, restartPause);
            }
        }
        setTimeout(typeNext, 250);
    }

    function styles() {
        if (document.getElementById('school-name-typewriter-vanilla-styles')) return;
        var s = document.createElement('style');
        s.id = 'school-name-typewriter-vanilla-styles';
        s.textContent = `
            .school-name-typewriter {
                display: inline-flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                justify-content: center !important;
                white-space: normal !important;
                line-height: 1.05 !important;
                max-width: 100% !important;
                overflow: visible !important;
                min-width: 0 !important;
                position: relative !important;
            }
            .school-name-typewriter-line {
                display: block !important;
                white-space: nowrap !important;
                width: auto !important;
                max-width: none !important;
                overflow: visible !important;
            }
            .school-name-typewriter-line-main {
                font-weight: 800 !important;
            }
            .school-name-typewriter-line-small {
                font-size: .68em !important;
                font-weight: 600 !important;
                line-height: 1.05 !important;
            }
            .school-name-typewriter .school-name-char-bold {
                font-weight: 800 !important;
            }
            .school-name-typewriter::after {
                content: '';
                display: inline-block;
                position: absolute;
                right: -5px;
                top: .05em;
                width: 2px;
                height: .95em;
                background: var(--bs-primary, #FF4880);
                animation: schoolNameCursorBlinkVanilla .75s step-end infinite;
            }
            @keyframes schoolNameCursorBlinkVanilla {
                0%,45% { opacity:1; }
                46%,100% { opacity:0; }
            }
            /* Desktop: give the brand enough room for a long two-line name. */
            @media (min-width: 768px) {
                .navbar-brand {
                    flex-shrink: 0 !important;
                    min-width: 0 !important;
                    overflow: visible !important;
                }
                .navbar-brand > h1.school-name-typewriter {
                    max-width: 390px !important;
                    overflow: visible !important;
                    margin-right: 12px !important;
                }
            }
            @media (max-width: 767.98px) {
                .navbar-brand > h1.school-name-typewriter {
                    max-width: 190px !important;
                    overflow: visible !important;
                }
            }
        `;
        document.head.appendChild(s);
    }

    function start() {
        styles();
        var els = document.querySelectorAll('a.navbar-brand h1, .school-name-header');
        if (!els.length) return;
        fetch('school-settings.json?typewriter_v2=' + Date.now(), { cache: 'no-store', credentials: 'same-origin' })
            .then(function (r) { return r.ok ? r.json() : {}; })
            .catch(function () { return {}; })
            .then(function (settings) {
                Array.prototype.forEach.call(els, function (el) { render(el, settings || {}); });
            });
    }

    function boot() {
        start();
        setTimeout(start, 700);
        setTimeout(start, 1800);
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', boot, { once: true });
    } else {
        boot();
    }
    window.addEventListener('load', boot, { once: true });
})();

/* =========================================================
   Main JS - school website
   Review carousel is deliberately isolated from the old
   .testimonial-carousel selector so cached older JS cannot
   re-initialize it and clone a single review.
   ========================================================= */
(function () {
    function hideSpinner() {
        var spinner = document.getElementById('spinner');
        if (spinner) {
            spinner.classList.remove('show');
            spinner.style.opacity = '0';
            spinner.style.visibility = 'hidden';
            spinner.style.pointerEvents = 'none';
        }
    }
    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', hideSpinner, { once: true });
    } else {
        hideSpinner();
    }
    window.addEventListener('load', hideSpinner, { once: true });
    setTimeout(hideSpinner, 2500);
})();

(function ($) {
    "use strict";

    var spinner = function () {
        setTimeout(function () {
            if ($('#spinner').length > 0) {
                $('#spinner').removeClass('show');
            }
        }, 1);
    };
    spinner(0);

    if (typeof WOW !== 'undefined') {
        new WOW().init();
    }

    // Back to top button
    $(window).scroll(function () {
        if ($(this).scrollTop() > 300) {
            $('.back-to-top').fadeIn('slow');
        } else {
            $('.back-to-top').fadeOut('slow');
        }
    });
    $('.back-to-top').click(function () {
        $('html, body').animate({ scrollTop: 0 }, 1500, 'easeInOutExpo');
        return false;
    });

    /* =====================================================
       PARENT REVIEWS - HARD SINGLE/UNIQUE RENDER
       =====================================================
       IMPORTANT:
       - The HTML no longer has .owl-carousel or .testimonial-carousel.
       - Therefore any older cached main.js cannot initialize Owl
         on the review container.
       - 1-3 reviews are plain responsive cards.
       - Owl Carousel is activated only for 4+ unique reviews.
    ===================================================== */
    function normalizeReviewText(value) {
        return String(value || '')
            .replace(/\s+/g, ' ')
            .trim()
            .toLowerCase();
    }

    function reviewSignature($item) {
        var name = normalizeReviewText($item.find('h4').first().text());
        var ps = $item.find('p');
        var profession = ps.length > 1 ? normalizeReviewText($(ps[0]).text()) : '';
        var reviewText = ps.length > 1 ? normalizeReviewText($(ps[ps.length - 1]).text()) : '';
        var rating = $item.find('.fa-star').length;
        return name + '|' + profession + '|' + rating + '|' + reviewText;
    }

    function prepareReviewContainer($container) {
        // Destroy Owl if another script initialized it before this code.
        if ($container.hasClass('owl-loaded') && $.fn.owlCarousel) {
            try { $container.trigger('destroy.owl.carousel'); } catch (e) {}
        }

        $container.removeClass('owl-loaded owl-hidden owl-theme');
        $container.find('.owl-stage-outer, .owl-stage, .owl-item, .owl-nav, .owl-dots').each(function () {
            // Restore children if Owl had wrapped them.
            var $node = $(this);
            if ($node.hasClass('owl-item')) {
                $node.contents().unwrap();
            }
        });
        $container.find('.owl-stage-outer').contents().unwrap();
        $container.find('.owl-stage').contents().unwrap();

        // Remove duplicate cards from the actual DOM before any carousel starts.
        var seen = Object.create(null);
        $container.children('.testimonial-item').each(function () {
            var $item = $(this);
            var sig = reviewSignature($item);
            if (sig && seen[sig]) {
                $item.remove();
            } else if (sig) {
                seen[sig] = true;
            }
        });

        var $items = $container.children('.testimonial-item');
        var count = $items.length;

        // Explicitly remove any old carousel-generated controls.
        $container.find('.owl-nav, .owl-dots').remove();

        if (count <= 3) {
            $container.removeClass('owl-carousel');
            $container.addClass('review-static-list review-count-' + count);
            $container.css({
                display: 'grid',
                gridTemplateColumns: count === 1
                    ? 'minmax(0, 520px)'
                    : 'repeat(' + count + ', minmax(0, 1fr))',
                gap: '50px',
                width: '100%',
                alignItems: 'stretch'
            });

            if (count === 1) {
                $container.css({ justifyContent: 'center' });
            }

            $items.css({
                display: 'block',
                width: '100%',
                maxWidth: 'none',
                margin: '0'
            });
            return;
        }

        // 4+ unique reviews: now and only now activate Owl Carousel.
        $container.addClass('owl-carousel');
        $container.css({ display: '', gridTemplateColumns: '', gap: '', justifyContent: '' });
        $items.css({ display: '', width: '', maxWidth: '', margin: '' });

        if (typeof $.fn.owlCarousel === 'function') {
            // Make the carousel manually navigable with visible previous/next arrows.
            $container.owlCarousel({
                autoplay: false,
                smartSpeed: 700,
                center: false,
                dots: true,
                nav: true,
                navText: [
                    '<span aria-label="Previous review">&#10094;</span>',
                    '<span aria-label="Next review">&#10095;</span>'
                ],
                loop: false,
                rewind: false,
                slideBy: 1,
                margin: 50,
                responsiveClass: true,
                responsive: {
                    0: { items: 1 },
                    576: { items: 1 },
                    768: { items: 2 },
                    992: { items: 2 },
                    1200: { items: 3 }
                }
            });

            // Ensure navigation remains visible even if an older theme stylesheet
            // hides Owl's default navigation buttons.
            var navStyleId = 'parent-review-owl-nav-style';
            if (!document.getElementById(navStyleId)) {
                var style = document.createElement('style');
                style.id = navStyleId;
                style.textContent = `
                    .review-carousel-container .owl-nav {
                        display: flex !important;
                        align-items: center;
                        justify-content: space-between;
                        position: absolute;
                        top: 50%;
                        left: -28px;
                        right: -28px;
                        transform: translateY(-50%);
                        pointer-events: none;
                        margin: 0 !important;
                        z-index: 20;
                    }
                    .review-carousel-container .owl-nav button.owl-prev,
                    .review-carousel-container .owl-nav button.owl-next {
                        width: 42px !important;
                        height: 42px !important;
                        border-radius: 50% !important;
                        border: 0 !important;
                        background: var(--bs-primary, #FF4880) !important;
                        color: #fff !important;
                        font-size: 24px !important;
                        line-height: 42px !important;
                        padding: 0 !important;
                        margin: 0 !important;
                        display: flex !important;
                        align-items: center;
                        justify-content: center;
                        pointer-events: auto;
                        transition: .2s ease;
                    }
                    .review-carousel-container .owl-nav button.owl-prev:hover,
                    .review-carousel-container .owl-nav button.owl-next:hover {
                        background: var(--bs-secondary, #1A1A1A) !important;
                    }
                    .review-carousel-container .owl-nav button.disabled {
                        opacity: .35 !important;
                        cursor: not-allowed !important;
                    }
                    @media (max-width: 767.98px) {
                        .review-carousel-container .owl-nav {
                            left: -8px;
                            right: -8px;
                        }
                        .review-carousel-container .owl-nav button.owl-prev,
                        .review-carousel-container .owl-nav button.owl-next {
                            width: 36px !important;
                            height: 36px !important;
                            font-size: 20px !important;
                            line-height: 36px !important;
                        }
                    }
                `;
                document.head.appendChild(style);
            }
        }
    }

    function initReviews() {
        $('[data-review-carousel="1"]').each(function () {
            prepareReviewContainer($(this));
        });
    }

    if (document.readyState === 'loading') {
        $(document).ready(initReviews);
    } else {
        initReviews();
    }

    // Modal Video
    $(document).ready(function () {
        var $videoSrc = '';

        function normalizeYouTubeUrl(value) {
            var raw = String(value || '').trim();
            if (!raw) return '';

            try {
                var url = new URL(raw, window.location.href);
                var host = url.hostname.toLowerCase();
                var videoId = '';

                if (host === 'youtu.be' || host === 'www.youtu.be') {
                    videoId = url.pathname.replace(/^\/+|\/+$/g, '').split('/')[0];
                } else if (host === 'youtube.com' || host === 'www.youtube.com' || host === 'm.youtube.com') {
                    if (url.pathname === '/watch') {
                        videoId = url.searchParams.get('v') || '';
                    } else {
                        var match = url.pathname.match(/^\/(?:embed|shorts)\/([^/?#]+)/i);
                        if (match) videoId = match[1];
                    }
                }

                if (/^[A-Za-z0-9_-]{6,20}$/.test(videoId)) {
                    return 'https://www.youtube.com/embed/' + videoId;
                }
            } catch (e) {
                // Fall back to the original URL below.
            }

            return raw;
        }

        $('.btn-play').click(function () {
            $videoSrc = normalizeYouTubeUrl($(this).data("src"));
        });

        $('#videoModal').on('shown.bs.modal', function () {
            if (!$videoSrc) return;
            $("#video").attr('src', $videoSrc + ($videoSrc.indexOf('?') === -1 ? '?' : '&') + "autoplay=1&modestbranding=1&rel=0");
        });

        $('#videoModal').on('hide.bs.modal', function () {
            $("#video").attr('src', '');
        });
    });


    // Repeating school-name typewriter effect with separate second line.
    // Works on BOTH desktop and mobile header elements.
    function parsePhrases(value) {
        return String(value || '')
            .split(/[\n,]+/)
            .map(function (item) { return item.replace(/\s+/g, ' ').trim(); })
            .filter(Boolean)
            .sort(function (a, b) { return b.length - a.length; });
    }

    function buildStyleMap(text, boldPhrases, smallPhrases) {
        var map = new Array(text.length).fill('normal');
        var lower = text.toLowerCase();

        function applyPhrases(phrases, styleName, overwrite) {
            phrases.forEach(function (phrase) {
                var needle = phrase.toLowerCase();
                if (!needle) return;
                var from = 0;
                while (from < lower.length) {
                    var pos = lower.indexOf(needle, from);
                    if (pos === -1) break;
                    for (var i = pos; i < pos + needle.length && i < map.length; i++) {
                        if (overwrite || map[i] === 'normal') map[i] = styleName;
                    }
                    from = pos + needle.length;
                }
            });
        }

        // Small is applied first; Bold has priority.
        applyPhrases(smallPhrases, 'small', false);
        applyPhrases(boldPhrases, 'bold', true);
        return map;
    }

    function findSmallRanges(text, smallPhrases) {
        var lower = text.toLowerCase();
        var ranges = [];
        smallPhrases.forEach(function (phrase) {
            var needle = phrase.toLowerCase();
            if (!needle) return;
            var from = 0;
            while (from < lower.length) {
                var pos = lower.indexOf(needle, from);
                if (pos === -1) break;
                ranges.push({ start: pos, end: pos + needle.length });
                from = pos + needle.length;
            }
        });
        ranges.sort(function (a, b) { return a.start - b.start; });
        return ranges;
    }

    function isInsideRange(index, ranges) {
        for (var i = 0; i < ranges.length; i++) {
            if (index >= ranges[i].start && index < ranges[i].end) return true;
        }
        return false;
    }

    function createAnimatedSchoolName(el, settings) {
        var fullText = String((settings && settings.school_name) || el.textContent || '')
            .replace(/\s+/g, ' ')
            .trim();
        if (!fullText) return;

        var boldPhrases = parsePhrases(settings && settings.bold_words);
        var smallPhrases = parsePhrases(settings && settings.small_words);
        var styleMap = buildStyleMap(fullText, boldPhrases, smallPhrases);
        var smallRanges = findSmallRanges(fullText, smallPhrases);

        // Do not allow separate comma entries such as "PUBLIC, SCHOOL"
        // to create separate lines. All small phrases belong to ONE second line.
        var hasSmallPart = smallRanges.length > 0;
        var firstLineText = fullText;
        var secondLineText = '';
        var firstSmallStart = -1;

        if (hasSmallPart) {
            firstSmallStart = smallRanges[0].start;
            // Second line begins at the first configured small phrase.
            firstLineText = fullText.slice(0, firstSmallStart).trim();
            secondLineText = fullText.slice(firstSmallStart).trim();
        }

        // Rebuild the element so desktop and mobile use exactly the same DOM.
        el.innerHTML = '';
        el.classList.add('school-name-typewriter');
        el.setAttribute('aria-label', fullText);

        var lines = [];
        var firstLine = document.createElement('span');
        firstLine.className = 'school-name-typewriter-line school-name-typewriter-line-main';
        el.appendChild(firstLine);
        lines.push(firstLine);

        var secondLine = null;
        if (hasSmallPart) {
            secondLine = document.createElement('span');
            secondLine.className = 'school-name-typewriter-line school-name-typewriter-line-small';
            el.appendChild(secondLine);
            lines.push(secondLine);
        }

        var chars = [];
        var firstVisibleCount = firstLineText.length;
        var secondFullStart = hasSmallPart ? firstSmallStart : -1;

        for (var i = 0; i < fullText.length; i++) {
            var targetLine = (hasSmallPart && i >= secondFullStart) ? secondLine : firstLine;
            var span = document.createElement('span');
            span.className = 'school-name-char school-name-char-' + styleMap[i];
            span.textContent = fullText.charAt(i);
            span.style.visibility = 'hidden';
            targetLine.appendChild(span);
            chars.push(span);
        }

        var index = 0;
        var typingSpeed = 75;
        var deletingSpeed = 45;
        var completedPause = 3000;
        var restartPause = 700;

        function typeText() {
            if (index < chars.length) {
                chars[index].style.visibility = 'visible';
                index += 1;
                window.setTimeout(typeText, typingSpeed);
                return;
            }
            window.setTimeout(deleteText, completedPause);
        }

        function deleteText() {
            if (index > 0) {
                index -= 1;
                chars[index].style.visibility = 'hidden';
                window.setTimeout(deleteText, deletingSpeed);
                return;
            }
            window.setTimeout(typeText, restartPause);
        }

        window.setTimeout(typeText, 250);
    }

    function loadSchoolNameSettings(callback) {
        fetch('school-settings.json?typewriter=' + Date.now(), {
            cache: 'no-store',
            credentials: 'same-origin'
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Unable to load school settings');
            return response.json();
        })
        .then(function (settings) { callback(settings || {}); })
        .catch(function () { callback({}); });
    }

    function addSchoolNameTypewriterStyles() {
        if (document.getElementById('school-name-typewriter-styles')) return;

        var style = document.createElement('style');
        style.id = 'school-name-typewriter-styles';
        style.textContent = `
            .school-name-typewriter {
                display: inline-flex !important;
                flex-direction: column !important;
                align-items: flex-start !important;
                justify-content: center !important;
                min-height: 1.15em;
                max-width: 100%;
                padding-right: 7px;
                position: relative;
                white-space: normal !important;
                line-height: 1.05 !important;
            }
            .school-name-typewriter::after {
                content: '';
                display: inline-block;
                position: absolute;
                right: 0;
                top: 0.05em;
                width: 2px;
                height: 0.95em;
                background: var(--bs-primary, #FF4880);
                animation: schoolNameCursorBlink .75s step-end infinite;
            }
            .school-name-typewriter-line {
                display: block !important;
                width: max-content;
                max-width: 100%;
                white-space: nowrap !important;
            }
            .school-name-typewriter-line-main {
                font-weight: 800 !important;
            }
            .school-name-typewriter-line-small {
                font-size: .68em !important;
                font-weight: 600 !important;
                line-height: 1.05 !important;
            }
            .school-name-typewriter .school-name-char-bold {
                font-weight: 800 !important;
            }
            .school-name-typewriter .school-name-char-small {
                font-size: inherit !important;
                font-weight: 600 !important;
            }
            .school-name-typewriter .school-name-char-normal {
                font-weight: 700 !important;
            }
            @keyframes schoolNameCursorBlink {
                0%, 45% { opacity: 1; }
                46%, 100% { opacity: 0; }
            }
            @media (max-width: 767.98px) {
                .school-name-typewriter {
                    max-width: 190px;
                }
                .school-name-typewriter-line-main {
                    font-size: 1em !important;
                }
                .school-name-typewriter-line-small {
                    font-size: .62em !important;
                }
            }
        `;
        document.head.appendChild(style);
    }

    function startSchoolNameTypewriter() {
        addSchoolNameTypewriterStyles();

        // Desktop and mobile both use the navbar-brand H1. The explicit
        // class is also supported for pages that already have it.
        var nodeList = document.querySelectorAll('.school-name-header, .navbar-brand h1');
        var elements = Array.prototype.filter.call(nodeList, function (el, index, arr) {
            return arr.indexOf(el) === index;
        });
        if (!elements.length) return;

        loadSchoolNameSettings(function (settings) {
            elements.forEach(function (el) {
                if (el.dataset.typewriterRunning === '1') return;
                el.dataset.typewriterRunning = '1';
                createAnimatedSchoolName(el, settings);
            });
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', startSchoolNameTypewriter);
    } else {
        startSchoolNameTypewriter();
    }

})(jQuery);

/* HARD FIX: logo frame itself must be transparent.
   The current HTML contains inline background:#fff!important on the
   logo wrapper, so CSS alone cannot override it. Remove that inline
   background on both header and footer logo frames. */
(function makeLogoFramesTransparent() {
    function apply() {
        var selectors = '.school-logo-box, .school-footer-logo-box';
        document.querySelectorAll(selectors).forEach(function (box) {
            box.style.setProperty('background', 'transparent', 'important');
            box.style.setProperty('background-color', 'transparent', 'important');
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', apply);
    } else {
        apply();
    }

    window.addEventListener('load', apply);

    /* Catch header/footer markup inserted or rewritten after load. */
    if (window.MutationObserver) {
        var observer = new MutationObserver(function () {
            apply();
        });
        observer.observe(document.documentElement, { childList: true, subtree: true });
    }
})();

/* =========================================================
   FOOTER SOCIAL MEDIA HANDLES
   =========================================================
   Social links are managed from Admin Dashboard > Social Media Handles.
   The setting is stored in school-settings.json so every normal website
   page can use the same footer links without editing each HTML file.
   ========================================================= */
(function () {
    'use strict';

    function applySocialMedia(settings) {
        var social = settings && settings.social_media ? settings.social_media : {};
        var links = document.querySelectorAll('.footer-icon a');

        Array.prototype.forEach.call(links, function (link) {
            var icon = link.querySelector('i');
            if (!icon) return;

            var key = '';
            if (icon.classList.contains('fa-facebook-f')) key = 'facebook';
            else if (icon.classList.contains('fa-twitter')) key = 'twitter';
            else if (icon.classList.contains('fa-instagram')) key = 'instagram';
            else if (icon.classList.contains('fa-linkedin-in')) key = 'linkedin';
            if (!key) return;

            var url = String(social[key] || '').trim();

                if (url) {
                    link.href = url;
                    link.target = '_blank';
                    link.rel = 'noopener noreferrer';
                    link.removeAttribute('aria-disabled');
                    link.style.removeProperty('pointer-events');
                    link.style.removeProperty('opacity');
                } else {
                    link.href = '#';
                    link.removeAttribute('target');
                    link.removeAttribute('rel');
                    link.setAttribute('aria-disabled', 'true');
                    link.style.setProperty('pointer-events', 'none', 'important');
                    link.style.setProperty('opacity', '0.55', 'important');
                }
        });
    }

    function loadSocialMedia() {
        fetch('school-settings.json?_social=' + Date.now(), {
            cache: 'no-store',
            credentials: 'same-origin'
        })
            .then(function (response) {
                if (!response.ok) throw new Error('Unable to load website settings');
                return response.json();
            })
            .then(applySocialMedia)
            .catch(function () {
                /* Keep the existing footer links if settings cannot be loaded. */
            });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadSocialMedia, { once: true });
    } else {
        loadSocialMedia();
    }
})();


/* ==========================================================
   DYNAMIC COPYRIGHT SCHOOL NAME
   Keeps the copyright name synchronized with Super Admin
   school_name from school-settings.json.
   ========================================================== */
(function () {
    'use strict';

    function updateCopyrightSchoolName(settings) {
        var name = String((settings && settings.school_name) || '').trim();
        if (!name) return;

        document.querySelectorAll('.copyright .text-light a').forEach(function (link) {
            var icon = link.querySelector('i.fa-copyright');
            if (!icon) return;

            var updated = false;
            Array.prototype.slice.call(link.childNodes).forEach(function (node) {
                if (node.nodeType === 3 && node.nodeValue.trim() !== '') {
                    node.nodeValue = name;
                    updated = true;
                }
            });

            if (!updated) {
                link.appendChild(document.createTextNode(name));
            }
        });
    }

    function loadCopyrightSchoolName() {
        fetch('school-settings.json?_copyright=' + Date.now(), {
            cache: 'no-store',
            credentials: 'same-origin'
        })
        .then(function (response) {
            if (!response.ok) throw new Error('Unable to load school settings');
            return response.json();
        })
        .then(updateCopyrightSchoolName)
        .catch(function () {
            // Keep the static fallback already present in the HTML.
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', loadCopyrightSchoolName);
    } else {
        loadCopyrightSchoolName();
    }
})();
