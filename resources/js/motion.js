import gsap from 'gsap';
import { ScrollTrigger } from 'gsap/ScrollTrigger';
import Lenis from 'lenis';
import 'lenis/dist/lenis.css';

gsap.registerPlugin(ScrollTrigger);

/*
 | Storefront motion layer: smooth scrolling, scroll-linked 3D and reveals.
 | Everything here is progressive enhancement — the page is complete without it, and
 | visitors who ask for reduced motion get the static layout.
 */
export function initMotion() {
    const root = document.documentElement;
    if (!document.body.hasAttribute('data-motion')) return;

    const mm = gsap.matchMedia();
    const finePointer = window.matchMedia('(hover: hover) and (pointer: fine)').matches;

    mm.add('(prefers-reduced-motion: no-preference)', () => {
        smoothScroll(finePointer);
        header();
        progressBar();
        splitHeadings();
        reveals();
        counters();
        marquee();
        heroDeck(finePointer);
        phoneFan();
        scrubText();
        parallax();
        if (finePointer) tilt();
    });

    // Layout-dependent scenes are rebuilt when the breakpoint changes.
    mm.add('(prefers-reduced-motion: no-preference) and (min-width: 1024px)', () => horizontalGallery());

    window.__motionReady = true;
    root.classList.add('motion-ready');
    window.addEventListener('load', () => ScrollTrigger.refresh());
}

/* ---------- Smooth scroll ---------- */

function smoothScroll(finePointer) {
    // Touch devices already scroll smoothly; hijacking them only adds latency.
    if (!finePointer) return;

    const lenis = new Lenis({
        lerp: 0.11,
        anchors: { offset: -120 },
        // Leave scrolling to open drawers, sheets and dialogs.
        prevent: (node) => !!node.closest('[data-lenis-prevent], [role="dialog"], [role="listbox"]'),
    });
    lenis.on('scroll', ScrollTrigger.update);
    const tick = (time) => lenis.raf(time * 1000);
    gsap.ticker.add(tick);
    gsap.ticker.lagSmoothing(0);
    window.__lenis = lenis;

    return () => {
        gsap.ticker.remove(tick);
        lenis.destroy();
    };
}

/* ---------- Header: gets out of the way on the way down, returns on the way up ---------- */

function header() {
    const el = document.querySelector('[data-header]');
    if (!el) return;
    ScrollTrigger.create({
        start: 0,
        end: 'max',
        onUpdate(self) {
            const y = self.scroll();
            el.classList.toggle('is-scrolled', y > 24);
            const busy = el.querySelector('[aria-expanded="true"]') || el.contains(document.activeElement);
            el.classList.toggle('is-hidden', self.direction === 1 && y > 420 && !busy);
        },
    });
}

function progressBar() {
    const bar = document.querySelector('[data-scroll-progress]');
    if (!bar) return;
    gsap.to(bar, { scaleX: 1, ease: 'none', scrollTrigger: { start: 0, end: 'max', scrub: 0.3 } });
}

/* ---------- Headings rise out of a mask, word by word ---------- */

function splitWords(el) {
    if (el.dataset.splitDone) return [...el.querySelectorAll('.word > span')];
    const text = el.textContent.trim().replace(/\s+/g, ' ');
    el.setAttribute('aria-label', text);
    el.textContent = '';
    const inner = [];
    text.split(' ').forEach((word, i, all) => {
        const mask = document.createElement('span');
        mask.className = 'word';
        mask.setAttribute('aria-hidden', 'true');
        const span = document.createElement('span');
        span.textContent = word;
        mask.append(span);
        el.append(mask);
        if (i < all.length - 1) el.append(' ');
        inner.push(span);
    });
    el.dataset.splitDone = '1';
    return inner;
}

function splitHeadings() {
    gsap.utils.toArray('[data-split]').forEach((el) => {
        const words = splitWords(el);
        gsap.set(el, { autoAlpha: 1 });
        gsap.from(words, {
            yPercent: 115,
            rotate: 4,
            duration: 0.9,
            ease: 'power4.out',
            stagger: 0.045,
            delay: Number(el.dataset.delay || 0),
            scrollTrigger: { trigger: el, start: 'top 90%', once: true },
        });
    });
}

/* ---------- Reveals ---------- */

function reveals() {
    // Single blocks
    gsap.utils.toArray('[data-reveal]').forEach((el) => {
        gsap.fromTo(el, { autoAlpha: 0, y: 36 }, {
            autoAlpha: 1, y: 0, duration: 0.9, ease: 'power3.out', delay: Number(el.dataset.delay || 0),
            scrollTrigger: { trigger: el, start: 'top 90%', once: true },
        });
    });

    // Product cards tip up into place in rows, wherever they appear.
    const cards = gsap.utils.toArray('.product-card');
    if (cards.length) {
        gsap.set(cards, { autoAlpha: 0, y: 48, rotateX: -14, transformPerspective: 900, transformOrigin: '50% 100%' });
        ScrollTrigger.batch(cards, {
            start: 'top 94%',
            once: true,
            onEnter: (batch) => gsap.to(batch, {
                autoAlpha: 1, y: 0, rotateX: 0, duration: 0.85, ease: 'power3.out', stagger: 0.07, overwrite: true,
                clearProps: 'transform,transformPerspective',
            }),
        });
    }

    // Any other group of siblings
    gsap.utils.toArray('[data-reveal-group]').forEach((group) => {
        const items = [...group.children];
        gsap.set(items, { autoAlpha: 0, y: 32 });
        ScrollTrigger.create({
            trigger: group,
            start: 'top 88%',
            once: true,
            onEnter: () => gsap.to(items, { autoAlpha: 1, y: 0, duration: 0.8, ease: 'power3.out', stagger: 0.08 }),
        });
    });
}

function counters() {
    gsap.utils.toArray('[data-count]').forEach((el) => {
        const end = Number(el.dataset.count);
        const state = { value: 0 };
        gsap.to(state, {
            value: end, duration: 1.6, ease: 'power2.out',
            scrollTrigger: { trigger: el, start: 'top 95%', once: true },
            onUpdate: () => (el.textContent = Math.round(state.value).toLocaleString('en-PK')),
        });
    });
}

/* ---------- Marquee that leans into the scroll direction ---------- */

function marquee() {
    gsap.utils.toArray('[data-marquee]').forEach((el) => {
        const track = el.firstElementChild;
        if (!track) return;
        track.append(...[...track.children].map((node) => {
            const copy = node.cloneNode(true);
            copy.setAttribute('aria-hidden', 'true');
            return copy;
        }));
        const loop = gsap.to(track, { xPercent: -50, duration: 32, ease: 'none', repeat: -1 });
        const skew = gsap.quickTo(track, 'skewX', { duration: 0.5, ease: 'power3.out' });
        ScrollTrigger.create({
            trigger: el,
            start: 'top bottom',
            end: 'bottom top',
            onUpdate(self) {
                const v = gsap.utils.clamp(-1, 1, self.getVelocity() / 1800);
                loop.timeScale(1 + Math.abs(v) * 3.5);
                skew(v * -10);
            },
            onLeave: () => loop.pause(),
            onEnterBack: () => loop.play(),
            onLeaveBack: () => loop.pause(),
            onEnter: () => loop.play(),
        });
    });
}

/* ---------- Hero: a deck of real products floating in 3D ---------- */

function heroDeck(finePointer) {
    const hero = document.querySelector('[data-hero]');
    const deck = hero?.querySelector('[data-deck]');
    if (!hero || !hero.offsetParent) return;

    gsap.from(hero.querySelectorAll('[data-hero-fade]'), { autoAlpha: 0, y: 28, duration: 0.9, ease: 'power3.out', stagger: 0.09, delay: 0.35 });
    gsap.to(hero.querySelectorAll('[data-glow]'), { x: 'random(-60, 60)', y: 'random(-40, 40)', scale: 'random(0.9, 1.2)', duration: 9, ease: 'sine.inOut', repeat: -1, yoyo: true, stagger: 1.5 });
    if (!deck) return;

    const cards = gsap.utils.toArray('[data-deck-card]', deck);

    // Entrance: cards fly in from depth.
    gsap.set(deck, { rotateX: 10, rotateY: -22 });
    gsap.from(cards.map((card) => card.firstElementChild), { autoAlpha: 0, z: -900, rotateY: 50, rotateX: -20, duration: 1.5, ease: 'expo.out', stagger: { each: 0.09, from: 'end' }, delay: 0.25 });

    // Idle float, each card on its own rhythm.
    cards.forEach((card, i) => {
        gsap.to(card.firstElementChild.firstElementChild, { y: i % 2 ? 16 : -16, rotateZ: i % 2 ? 1.5 : -1.5, duration: 3.2 + i * 0.45, ease: 'sine.inOut', repeat: -1, yoyo: true });
    });

    // Scroll: the deck turns and fans out while the copy drifts away.
    gsap.timeline({ scrollTrigger: { trigger: hero, start: 'top top', end: 'bottom top', scrub: 0.8 } })
        .to(deck, { rotateY: 14, rotateX: 16, '--spread': 1.75, ease: 'none' }, 0)
        .to(hero.querySelector('[data-hero-copy]'), { yPercent: -18, autoAlpha: 0.15, ease: 'none' }, 0)
        .to(hero.querySelector('[data-deck-stage]'), { yPercent: 10, ease: 'none' }, 0);

    // Pointer: the whole stage leans toward the cursor.
    if (finePointer) {
        const stage = hero.querySelector('[data-deck-stage]');
        const rx = gsap.quickTo(stage, 'rotateX', { duration: 0.9, ease: 'power3.out' });
        const ry = gsap.quickTo(stage, 'rotateY', { duration: 0.9, ease: 'power3.out' });
        const move = (e) => {
            const r = hero.getBoundingClientRect();
            ry(((e.clientX - r.left) / r.width - 0.5) * 16);
            rx(((e.clientY - r.top) / r.height - 0.5) * -12);
        };
        const leave = () => { rx(0); ry(0); };
        hero.addEventListener('pointermove', move);
        hero.addEventListener('pointerleave', leave);
        return () => {
            hero.removeEventListener('pointermove', move);
            hero.removeEventListener('pointerleave', leave);
        };
    }
}

/* ---------- Phone hero: the card fan deals itself in, then opens as you scroll ---------- */

function phoneFan() {
    const fan = document.querySelector('[data-fan]');
    if (!fan || !fan.offsetParent) return;
    const cards = [...fan.children].map((card) => card.firstElementChild);

    gsap.from(fan.closest('section').querySelectorAll('[data-hero-fade]'), { autoAlpha: 0, y: 20, duration: 0.8, ease: 'power3.out', stagger: 0.08, delay: 0.3 });
    gsap.from(cards, { autoAlpha: 0, y: 200, rotate: (i) => (i - 1) * -18, duration: 1.1, ease: 'back.out(1.5)', stagger: 0.1, delay: 0.45 });
    cards.forEach((card, i) => {
        gsap.to(card.firstElementChild, { y: i % 2 ? 7 : -7, duration: 2.6 + i * 0.5, ease: 'sine.inOut', repeat: -1, yoyo: true });
    });
    gsap.to(fan, { '--fan': 1.55, ease: 'none', scrollTrigger: { trigger: fan, start: 'top 45%', end: 'bottom top', scrub: 0.5 } });
}

/* ---------- Departments: vertical scroll drives a horizontal 3D gallery ---------- */

function horizontalGallery() {
    gsap.utils.toArray('[data-hscroll]').forEach((section) => {
        const track = section.querySelector('[data-hscroll-track]');
        const panels = gsap.utils.toArray(track.children);
        const distance = () => Math.max(track.scrollWidth - section.clientWidth, 0);
        section.classList.add('is-pinned');

        const tween = gsap.to(track, {
            x: () => -distance(),
            ease: 'none',
            scrollTrigger: {
                trigger: section,
                start: 'center center',
                end: () => '+=' + distance(),
                pin: true,
                scrub: 0.9,
                invalidateOnRefresh: true,
                anticipatePin: 1,
            },
        });

        // Each panel swings to face the viewer as it passes the centre.
        panels.forEach((panel) => {
            gsap.timeline({ scrollTrigger: { trigger: panel, containerAnimation: tween, start: 'left right', end: 'right left', scrub: true } })
                .fromTo(panel, { rotateY: 26, z: -150, opacity: 0.5, transformPerspective: 1200 }, { rotateY: 0, z: 0, opacity: 1, ease: 'none' })
                .to(panel, { rotateY: -26, z: -150, opacity: 0.5, ease: 'none' });
        });

        const bar = section.querySelector('[data-hscroll-progress]');
        if (bar) gsap.to(bar, { scaleX: 1, ease: 'none', scrollTrigger: { trigger: section, start: 'center center', end: () => '+=' + distance(), scrub: 0.9 } });
    });

    return () => document.querySelectorAll('[data-hscroll]').forEach((section) => section.classList.remove('is-pinned'));
}

/* ---------- Statement text that lights up as it is read ---------- */

function scrubText() {
    gsap.utils.toArray('[data-scrub-text]').forEach((el) => {
        const words = splitWords(el);
        el.classList.add('is-scrub');
        gsap.fromTo(words, { opacity: 0.14 }, {
            opacity: 1, ease: 'none', stagger: 0.12,
            scrollTrigger: { trigger: el, start: 'top 82%', end: 'bottom 48%', scrub: 0.6 },
        });
    });
}

function parallax() {
    gsap.utils.toArray('[data-parallax]').forEach((el) => {
        const amount = Number(el.dataset.parallax) || 12;
        gsap.fromTo(el, { yPercent: amount }, {
            yPercent: -amount, ease: 'none',
            scrollTrigger: { trigger: el.closest('section') || el, start: 'top bottom', end: 'bottom top', scrub: true },
        });
    });
}

/* ---------- Pointer tilt with a moving highlight ---------- */

function tilt() {
    const max = 9;
    const enter = (e) => {
        const el = e.currentTarget;
        el._rect = el.getBoundingClientRect();
        gsap.to(el, { scale: 1.02, duration: 0.4, ease: 'power3.out' });
    };
    const move = (e) => {
        const el = e.currentTarget;
        const r = el._rect || el.getBoundingClientRect();
        const px = (e.clientX - r.left) / r.width;
        const py = (e.clientY - r.top) / r.height;
        gsap.to(el, { rotateY: (px - 0.5) * 2 * max, rotateX: (0.5 - py) * 2 * max, transformPerspective: 900, duration: 0.5, ease: 'power3.out' });
        el.style.setProperty('--gx', `${px * 100}%`);
        el.style.setProperty('--gy', `${py * 100}%`);
    };
    const leave = (e) => gsap.to(e.currentTarget, { rotateX: 0, rotateY: 0, scale: 1, duration: 0.8, ease: 'elastic.out(1, 0.6)' });

    const nodes = gsap.utils.toArray('[data-tilt]');
    nodes.forEach((el) => {
        el.addEventListener('pointerenter', enter);
        el.addEventListener('pointermove', move);
        el.addEventListener('pointerleave', leave);
    });
    return () => nodes.forEach((el) => {
        el.removeEventListener('pointerenter', enter);
        el.removeEventListener('pointermove', move);
        el.removeEventListener('pointerleave', leave);
    });
}
