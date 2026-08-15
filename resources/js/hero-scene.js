/**
 * Hero background: a continuous stream of particles flowing left to right,
 * for the "you move files all day" audience.
 *
 * Everything is computed on the GPU from a per-particle seed, so the CPU cost
 * per frame is one uniform write. That matters: this runs on the page carrying
 * most of the site's search traffic, so it must not compete with the transfer
 * form for main-thread time.
 *
 * Named imports only — a namespace import (`import * as THREE`) defeats Vite's
 * tree shaking and roughly triples the bundle.
 */
import {
    Scene,
    PerspectiveCamera,
    WebGLRenderer,
    BufferGeometry,
    BufferAttribute,
    Points,
    ShaderMaterial,
    AdditiveBlending,
    Color,
} from 'three';

const COUNT = 5200;   // points; cheap because there is one draw call
const SPAN = 46;      // world units the stream travels before wrapping

const vert = /* glsl */ `
    attribute vec4 aSeed;      // x: offset along the stream, y: lane, z: depth, w: speed
    attribute float aSize;
    uniform float uTime;
    uniform float uSpan;
    uniform float uPixelRatio;
    varying float vFade;
    varying float vDepth;
    varying float vStretch;

    void main() {
        // Advance along x and wrap, so the stream never ends.
        float x = mod(aSeed.x + uTime * aSeed.w, uSpan) - uSpan * 0.5;

        // A slow vertical drift keeps it organic rather than mechanical.
        float y = aSeed.y + sin(uTime * 0.35 + aSeed.x * 0.55) * 0.42;

        vec3 pos = vec3(x, y, aSeed.z);
        vec4 mv = modelViewMatrix * vec4(pos, 1.0);

        // Fade in and out at the ends so particles do not pop as they wrap.
        vFade = smoothstep(0.0, 7.0, uSpan * 0.5 - abs(x));
        vDepth = clamp((aSeed.z + 9.0) / 18.0, 0.0, 1.0);

        // Faster particles draw as longer streaks. This is what makes the field
        // read as a directional flow rather than as a starfield.
        vStretch = clamp((aSeed.w - 1.1) / 3.4, 0.0, 1.0);

        gl_PointSize = aSize * uPixelRatio * (26.0 / -mv.z) * (1.0 + vStretch * 2.6);
        gl_Position = projectionMatrix * mv;
    }
`;

const frag = /* glsl */ `
    uniform vec3 uNear;
    uniform vec3 uFar;
    varying float vFade;
    varying float vDepth;
    varying float vStretch;

    void main() {
        vec2 c = gl_PointCoord - vec2(0.5);

        // Squash the sprite vertically so it draws as a horizontal streak. Slow
        // particles stay round; fast ones stretch into a dash travelling with
        // the flow.
        float squash = 1.0 + vStretch * 5.0;
        vec2 p = vec2(c.x, c.y * squash);
        float d = dot(p, p);
        if (d > 0.25) discard;

        // Trailing edge fades, so each streak reads as a comet rather than a bar.
        float tail = mix(1.0, smoothstep(-0.5, 0.35, c.x), vStretch);

        float alpha = smoothstep(0.25, 0.0, d) * vFade * tail * mix(0.35, 1.0, vDepth);
        gl_FragColor = vec4(mix(uFar, uNear, vDepth), alpha);
    }
`;

export function initHero(canvas) {
    // Honour the OS setting, and bail on anything without WebGL rather than throw.
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) return null;

    let renderer;
    try {
        renderer = new WebGLRenderer({ canvas, alpha: true, antialias: false, powerPreference: 'low-power' });
    } catch (e) {
        return null; // no WebGL: the CSS gradient behind the canvas is the fallback
    }

    const dpr = Math.min(window.devicePixelRatio || 1, 1.75);
    renderer.setPixelRatio(dpr);

    const scene = new Scene();
    const camera = new PerspectiveCamera(58, 1, 0.1, 100);
    camera.position.set(0, 0, 15);

    const seeds = new Float32Array(COUNT * 4);
    const sizes = new Float32Array(COUNT);
    for (let i = 0; i < COUNT; i++) {
        seeds[i * 4 + 0] = Math.random() * SPAN;            // offset
        seeds[i * 4 + 1] = (Math.random() - 0.5) * 15.5;    // lane
        seeds[i * 4 + 2] = (Math.random() - 0.5) * 17.0;    // depth
        seeds[i * 4 + 3] = 1.1 + Math.random() * 3.4;       // speed

        // A few larger glints read as individual files in the stream.
        sizes[i] = Math.random() < 0.04 ? 3.1 + Math.random() * 2.2 : 0.5 + Math.random() * 1.15;
    }

    const geo = new BufferGeometry();
    geo.setAttribute('position', new BufferAttribute(new Float32Array(COUNT * 3), 3));
    geo.setAttribute('aSeed', new BufferAttribute(seeds, 4));
    geo.setAttribute('aSize', new BufferAttribute(sizes, 1));

    const mat = new ShaderMaterial({
        vertexShader: vert,
        fragmentShader: frag,
        transparent: true,
        depthWrite: false,
        blending: AdditiveBlending,
        uniforms: {
            uTime: { value: 0 },
            uSpan: { value: SPAN },
            uPixelRatio: { value: dpr },
            uNear: { value: new Color('#EAF0FF') },
            uFar: { value: new Color('#5B7CFF') },
        },
    });

    const points = new Points(geo, mat);
    scene.add(points);

    function resize() {
        const w = canvas.clientWidth || window.innerWidth;
        const h = canvas.clientHeight || 520;
        renderer.setSize(w, h, false);
        camera.aspect = w / h;
        camera.updateProjectionMatrix();
    }
    resize();
    window.addEventListener('resize', resize, { passive: true });

    // Gentle parallax. Kept small so it reads as depth, not as a toy.
    let px = 0, py = 0, tx = 0, ty = 0;
    window.addEventListener('pointermove', (e) => {
        tx = (e.clientX / window.innerWidth - 0.5) * 0.7;
        ty = (e.clientY / window.innerHeight - 0.5) * 0.42;
    }, { passive: true });

    // Stop rendering when the hero is off screen or the tab is hidden. A
    // background canvas burning a phone battery below the fold is indefensible.
    let onScreen = true;
    const io = new IntersectionObserver(([e]) => { onScreen = e.isIntersecting; }, { threshold: 0 });
    io.observe(canvas);

    let raf = 0;
    const clock = { t: 0, last: performance.now() };

    function frame(now) {
        raf = requestAnimationFrame(frame);

        const dt = Math.min((now - clock.last) / 1000, 0.05); // clamp after a tab switch
        clock.last = now;
        if (!onScreen || document.hidden) return;

        clock.t += dt;
        mat.uniforms.uTime.value = clock.t;

        px += (tx - px) * 0.045;
        py += (ty - py) * 0.045;
        camera.position.x = px * 2.4;
        camera.position.y = -py * 2.0;
        camera.lookAt(0, 0, 0);

        renderer.render(scene, camera);
    }
    raf = requestAnimationFrame(frame);

    return function destroy() {
        cancelAnimationFrame(raf);
        io.disconnect();
        window.removeEventListener('resize', resize);
        geo.dispose();
        mat.dispose();
        renderer.dispose();
    };
}
