/**
 * Hero entry point. Deliberately tiny.
 *
 * three.js is ~128KB gzipped, and this runs on the page carrying most of the
 * site's search traffic, so it is behind a dynamic import: the chunk is only
 * fetched for visitors who will actually see it. Anyone on reduced-motion,
 * without WebGL, or on a device that never reaches idle pays nothing but the
 * few bytes below, and still gets the gradient hero.
 */

function shouldRender() {
    const canvas = document.getElementById('heroCanvas');
    if (!canvas) return null;
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return null;

    // Probe for WebGL before pulling down the library.
    try {
        const probe = document.createElement('canvas');
        const gl = probe.getContext('webgl') || probe.getContext('experimental-webgl');
        if (!gl) return null;
    } catch (e) {
        return null;
    }

    // Skip the whole thing on phones that advertise little memory or few cores.
    if (navigator.deviceMemory && navigator.deviceMemory < 4) return null;
    if (navigator.hardwareConcurrency && navigator.hardwareConcurrency < 4) return null;

    return canvas;
}

async function boot() {
    const canvas = shouldRender();
    if (!canvas) return;

    try {
        const { initHero } = await import('./hero-scene.js');
        initHero(canvas);
    } catch (e) {
        // A failed chunk must never break the page; the gradient stands on its own.
    }
}

if ('requestIdleCallback' in window) {
    requestIdleCallback(boot, { timeout: 2000 });
} else {
    window.addEventListener('load', () => setTimeout(boot, 300));
}
