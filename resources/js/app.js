import Alpine from 'alpinejs';
import collapse from '@alpinejs/collapse';
import { initMotion } from './motion';

Alpine.plugin(collapse);

const csrf = () => document.querySelector('meta[name="csrf-token"]')?.content ?? '';

/* Header search with autocomplete (debounced, keyboard navigable). */
Alpine.data('searchBox', (endpoint) => ({
    q: '',
    open: false,
    loading: false,
    products: [],
    categories: [],
    active: -1,
    timer: null,
    controller: null,

    get items() {
        return [...this.categories.map((c) => c.url), ...this.products.map((p) => p.url)];
    },
    onInput() {
        clearTimeout(this.timer);
        this.active = -1;
        if (this.q.trim().length < 2) {
            this.open = false;
            return;
        }
        this.loading = true;
        this.timer = setTimeout(() => this.fetch(), 220);
    },
    async fetch() {
        this.controller?.abort();
        this.controller = new AbortController();
        try {
            const res = await fetch(`${endpoint}?q=${encodeURIComponent(this.q.trim())}`, {
                headers: { Accept: 'application/json' },
                signal: this.controller.signal,
            });
            const data = await res.json();
            this.products = data.products;
            this.categories = data.categories;
            this.open = true;
        } catch (e) {
            if (e.name !== 'AbortError') this.open = false;
        } finally {
            this.loading = false;
        }
    },
    move(step) {
        if (!this.open || !this.items.length) return;
        this.active = (this.active + step + this.items.length) % this.items.length;
    },
    go(event) {
        if (this.open && this.active >= 0) {
            event.preventDefault();
            window.location.href = this.items[this.active];
        }
    },
}));

/* Wishlist heart — toggles without a page reload and keeps the header count in sync. */
Alpine.data('wishlistButton', (url, initial) => ({
    active: initial,
    busy: false,
    async toggle() {
        if (this.busy) return;
        this.busy = true;
        this.active = !this.active;
        try {
            const res = await fetch(url, {
                method: 'POST',
                headers: { Accept: 'application/json', 'X-CSRF-TOKEN': csrf(), 'X-Requested-With': 'XMLHttpRequest' },
            });
            if (!res.ok) throw new Error();
            const data = await res.json();
            this.active = data.active;
            window.dispatchEvent(new CustomEvent('wishlist-count', { detail: data.count }));
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: data.active ? 'Saved to your wishlist.' : 'Removed from your wishlist.' } }));
        } catch {
            this.active = !this.active;
            window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Could not update your wishlist. Please try again.', error: true } }));
        } finally {
            this.busy = false;
        }
    },
}));

/* Toast stack — confirms every action so the shopper is never left guessing. */
Alpine.data('toasts', (initial = []) => ({
    list: [],
    init() {
        initial.forEach((t) => this.push(t));
    },
    push(toast) {
        const id = Date.now() + Math.random();
        this.list.push({ id, ...toast });
        setTimeout(() => this.dismiss(id), toast.error ? 7000 : 4500);
    },
    dismiss(id) {
        this.list = this.list.filter((t) => t.id !== id);
    },
}));

/* Share: native share sheet on phones, copy-link fallback on desktop. */
window.shareProduct = async (title, url) => {
    if (navigator.share) {
        try {
            await navigator.share({ title, url });
        } catch {
            /* dismissed */
        }
        return;
    }
    try {
        await navigator.clipboard.writeText(url);
        window.dispatchEvent(new CustomEvent('toast', { detail: { message: 'Link copied to clipboard.' } }));
    } catch {
        window.prompt('Copy this link', url);
    }
};

/* Prevent double submits on slow connections. */
document.addEventListener('submit', (event) => {
    const form = event.target;
    if (form.dataset.once === undefined) return;
    if (form.dataset.submitted) {
        event.preventDefault();
        return;
    }
    form.dataset.submitted = '1';
    form.querySelectorAll('[type="submit"]').forEach((button) => button.setAttribute('aria-disabled', 'true'));
});

window.Alpine = Alpine;
Alpine.start();
initMotion();
