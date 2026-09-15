    // Tell people the link is wrong before they submit it, not after a round trip.
    (function () {
        const input = document.getElementById('wetransfer_url');
        if (!input) return;
        const hint = document.getElementById('urlHint');
        const ok = /(^|\.)wetransfer\.com\//i, short = /(^|\/\/)we\.tl\//i;
        input.addEventListener('input', function () {
            const v = input.value.trim();
            const bad = v.length > 8 && !ok.test(v) && !short.test(v);
            hint.classList.toggle('bad', bad);
            hint.textContent = bad
                ? "That does not look like a WeTransfer link. It should start with wetransfer.com or we.tl."
                : "Works with full wetransfer.com links and short we.tl links.";
        });
    })();

    // Start the marquee only once the page has settled, and stop it whenever it
    // scrolls out of view. Keeps Speed Index honest and stops a strip animating
    // off screen from burning a phone's battery.
    (function () {
        const track = document.querySelector('.marquee-track');
        const strip = document.querySelector('.marquee');
        if (!track || !strip) return;
        if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return;

        const start = () => {
            if ('IntersectionObserver' in window) {
                new IntersectionObserver(([e]) => {
                    track.classList.toggle('go', e.isIntersecting);
                }, { threshold: 0 }).observe(strip);
            } else {
                track.classList.add('go');
            }
        };
        if ('requestIdleCallback' in window) requestIdleCallback(start, { timeout: 2500 });
        else window.addEventListener('load', () => setTimeout(start, 1200));
    })();

    // Reveal decorative sections on scroll. Never applied to the transfer form,
    // which must be visible whether or not this script runs.
    (function () {
        const els = document.querySelectorAll('.rise');
        if (!els.length || !('IntersectionObserver' in window)) {
            els.forEach(el => el.classList.add('in'));
            return;
        }
        const io = new IntersectionObserver((entries) => {
            entries.forEach(e => {
                if (e.isIntersecting) { e.target.classList.add('in'); io.unobserve(e.target); }
            });
        }, { threshold: .08, rootMargin: '0px 0px -50px 0px' });
        els.forEach(el => io.observe(el));
    })();
