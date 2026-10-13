<!doctype html>
<html lang="en">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <meta name="csrf-token" content="{{ csrf_token() }}">
    <meta name="theme-color" content="#0b6b4f">
    <title>@hasSection('title')@yield('title') — @endif{{ config('store.name') }}</title>
    <meta name="description" content="@yield('description', config('store.tagline').'. Cash on Delivery and easy exchange.')">
    <link rel="icon" type="image/svg+xml" href="{{ asset('favicon.svg') }}">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Bricolage+Grotesque:opsz,wght@12..96,500..800&family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    {{-- Enables scroll animations only for visitors who have not asked for reduced motion; the timeout is a failsafe. --}}
    <script>if (!matchMedia('(prefers-reduced-motion: reduce)').matches) { document.documentElement.classList.add('js-motion'); setTimeout(function () { document.documentElement.classList.add('motion-ready'); }, 3500); }</script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body class="flex min-h-screen flex-col" data-motion x-data="{ menu: false }" :class="menu && 'overflow-hidden'">
    <a href="#main" class="sr-only focus:not-sr-only focus:fixed focus:top-2 focus:left-2 focus:z-[60] focus:rounded-control focus:bg-ink focus:px-4 focus:py-2 focus:text-white">Skip to content</a>

    {{-- Announcement bar: COD / nationwide delivery / current promotion --}}
    @if ($announcements->isNotEmpty())
        <div class="bg-ink text-white">
            <div class="container-page flex h-9 items-center justify-center gap-6 overflow-hidden text-[0.8125rem]">
                @foreach ($announcements as $i => $text)
                    <p class="{{ $i > 0 ? 'hidden md:flex' : 'flex' }} items-center gap-1.5 whitespace-nowrap">
                        <x-lucide-check class="size-3.5 text-emerald-400" aria-hidden="true" />{{ $text }}
                    </p>
                @endforeach
            </div>
        </div>
    @endif

    <header data-header class="sticky top-0 z-40 border-b border-line bg-surface">
        <div data-scroll-progress class="absolute inset-x-0 bottom-[-2px] z-10 h-0.5 bg-primary" aria-hidden="true"></div>
        <div class="container-page flex h-16 items-center gap-2 lg:gap-6">
            <button type="button" class="icon-btn -ml-2 lg:hidden" @click="menu = true" aria-label="Open menu"><x-lucide-menu class="size-6" aria-hidden="true" /></button>

            <a href="{{ route('home') }}" class="flex shrink-0 items-center gap-2" aria-label="{{ config('store.name') }} home">
                <span class="grid size-9 place-items-center rounded-lg bg-primary font-display text-lg font-extrabold text-white">{{ mb_substr(config('store.name'), 0, 1) }}</span>
                <span class="font-display text-xl font-extrabold tracking-tight">{{ config('store.name') }}</span>
            </a>

            {{-- Search with autocomplete --}}
            <form action="{{ route('search') }}" role="search" class="relative ml-auto hidden max-w-xl flex-1 md:block lg:ml-4"
                x-data="searchBox('{{ route('search.suggest') }}')" x-init="q = @js(request()->routeIs('search') ? (string) request('q') : '')" @click.outside="open = false" @keydown.escape="open = false">
                <label for="site-search" class="sr-only">Search products</label>
                <x-lucide-search class="pointer-events-none absolute top-1/2 left-3.5 size-[1.125rem] -translate-y-1/2 text-muted" aria-hidden="true" />
                <input id="site-search" type="search" name="q" x-model="q" @input="onInput" @focus="q.trim().length > 1 && (open = true)"
                    @keydown.arrow-down.prevent="move(1)" @keydown.arrow-up.prevent="move(-1)" @keydown.enter="go($event)"
                    placeholder="Search for kurtis, bags, kitchen organisers…" autocomplete="off"
                    role="combobox" aria-autocomplete="list" aria-controls="search-suggestions" :aria-expanded="open.toString()"
                    class="field h-11 rounded-full bg-sunken pr-4 pl-11 focus:bg-surface">
                @include('partials.search-suggestions')
            </form>

            <nav class="ml-auto flex items-center md:ml-0" aria-label="Account and cart">
                <a href="{{ auth()->check() ? (auth()->user()->isStaff() ? route('admin.dashboard') : route('account')) : route('login') }}" class="icon-btn hidden sm:inline-flex" aria-label="{{ auth()->check() ? 'My account' : 'Sign in' }}">
                    <x-lucide-user class="size-[1.375rem]" aria-hidden="true" />
                </a>
                <a href="{{ route('wishlist') }}" class="icon-btn relative" aria-label="Wishlist" x-data="{ count: {{ $wishlistCount }} }" @wishlist-count.window="count = $event.detail">
                    <x-lucide-heart class="size-[1.375rem]" aria-hidden="true" />
                    <span x-show="count > 0" x-text="count" x-cloak class="absolute top-0.5 right-0.5 grid h-[1.125rem] min-w-[1.125rem] place-items-center rounded-full bg-ink px-1 text-[0.6875rem] font-bold text-white"></span>
                </a>
                <a href="{{ route('cart') }}" class="icon-btn relative" aria-label="Cart, {{ $cartCount }} {{ Str::plural('item', $cartCount) }}">
                    <x-lucide-shopping-bag class="size-[1.375rem]" aria-hidden="true" />
                    @if ($cartCount > 0)
                        <span class="absolute top-0.5 right-0.5 grid h-[1.125rem] min-w-[1.125rem] place-items-center rounded-full bg-primary px-1 text-[0.6875rem] font-bold text-white">{{ $cartCount }}</span>
                    @endif
                </a>
            </nav>
        </div>

        {{-- Phones: search is always one tap away --}}
        <form action="{{ route('search') }}" role="search" class="container-page relative pb-3 md:hidden">
            <label for="m-search" class="sr-only">Search products</label>
            <x-lucide-search class="pointer-events-none absolute top-[calc(50%-0.375rem)] left-8 size-[1.125rem] -translate-y-1/2 text-muted" aria-hidden="true" />
            <input id="m-search" type="search" name="q" value="{{ request()->routeIs('search') ? request('q') : '' }}" placeholder="Search kurtis, bags, organisers…" autocomplete="off" enterkeyhint="search"
                class="field h-11 rounded-full border-transparent bg-sunken pr-4 pl-11 focus:bg-surface">
        </form>

        {{-- Main navigation: a small number of clear departments, details on hover/focus --}}
        <nav class="hidden border-t border-line lg:block" aria-label="Departments" x-data="{ open: null }" @mouseleave="open = null" @keydown.escape="open = null">
            <div class="container-page relative">
                <ul class="flex h-12 items-stretch gap-1">
                    @foreach ($nav as $dept)
                        @php $isDeals = $dept->kind === 'collection'; @endphp
                        <li class="flex" @mouseenter="open = '{{ $dept->slug }}'">
                            <a href="{{ $dept->url }}" @focus="open = '{{ $dept->slug }}'"
                                class="relative flex items-center gap-1.5 px-3 text-sm font-semibold whitespace-nowrap transition-colors {{ $isDeals ? 'text-accent' : 'text-ink-2 hover:text-ink' }}"
                                :class="open === '{{ $dept->slug }}' && 'text-ink after:absolute after:inset-x-3 after:bottom-0 after:h-0.5 after:bg-primary'"
                                :aria-expanded="(open === '{{ $dept->slug }}').toString()" aria-haspopup="true">
                                @if ($isDeals)<x-lucide-flame class="size-4" aria-hidden="true" />@endif
                                {{ $dept->name }}
                            </a>
                        </li>
                    @endforeach
                    <li class="ml-auto flex"><a href="{{ route('shop') }}" class="flex items-center px-3 text-sm font-medium text-muted hover:text-ink">Shop All</a></li>
                </ul>

                @foreach ($nav as $dept)
                    <div x-show="open === '{{ $dept->slug }}'" x-cloak x-transition.opacity.duration.150ms
                        class="absolute inset-x-4 top-full z-40 rounded-b-card border border-t-0 border-line bg-surface p-7 shadow-e3 sm:inset-x-6 lg:inset-x-8">
                        <div class="grid grid-cols-4 gap-x-8 gap-y-6">
                            @foreach ($dept->children->where('is_restricted', false) as $group)
                                <div>
                                    <a href="{{ $group->url }}" class="text-sm font-bold text-ink hover:text-primary">{{ $group->name }}</a>
                                    <ul class="mt-2.5 space-y-1.5">
                                        @foreach ($group->children->take(8) as $leaf)
                                            <li><a href="{{ $leaf->url }}" class="text-sm text-ink-2 hover:text-primary hover:underline">{{ $leaf->name }}</a></li>
                                        @endforeach
                                        @if ($group->children->count() > 8)
                                            <li><a href="{{ $group->url }}" class="link text-sm">View all {{ $group->children->count() }}</a></li>
                                        @endif
                                    </ul>
                                </div>
                            @endforeach
                        </div>
                        {{-- Private & Intimate stays a discreet link inside Women (spec §12) --}}
                        @foreach ($dept->children->where('is_restricted', true) as $group)
                            <div class="mt-6 flex items-center justify-between border-t border-line pt-4">
                                <a href="{{ $group->url }}" class="inline-flex items-center gap-2 text-sm text-muted hover:text-ink">
                                    <x-lucide-lock class="size-3.5" aria-hidden="true" />{{ $group->name }} <span class="badge badge-neutral">18+</span>
                                </a>
                            </div>
                        @endforeach
                    </div>
                @endforeach
            </div>
        </nav>
    </header>

    {{-- Mobile navigation drawer --}}
    <div x-show="menu" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Menu" @keydown.escape.window="menu = false">
        <div class="absolute inset-0 bg-ink/50" x-show="menu" x-transition.opacity @click="menu = false"></div>
        <div x-show="menu" x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="-translate-x-full" x-transition:enter-end="translate-x-0"
            x-transition:leave="transition duration-150 ease-in" x-transition:leave-end="-translate-x-full"
            class="absolute inset-y-0 left-0 flex w-[86%] max-w-sm flex-col bg-surface shadow-e3">
            <div class="flex h-16 items-center justify-between border-b border-line px-4">
                <span class="font-display text-lg font-extrabold">Menu</span>
                <button type="button" class="icon-btn -mr-2" @click="menu = false" aria-label="Close menu"><x-lucide-x class="size-6" aria-hidden="true" /></button>
            </div>
            <div class="flex-1 overflow-y-auto" x-data="{ dept: null }">
                @foreach ($nav as $dept)
                    <div class="border-b border-line">
                        <button type="button" class="flex min-h-[3.25rem] w-full items-center justify-between px-4 text-left text-[0.9375rem] font-semibold {{ $dept->kind === 'collection' ? 'text-accent' : '' }}"
                            @click="dept = dept === '{{ $dept->slug }}' ? null : '{{ $dept->slug }}'" :aria-expanded="(dept === '{{ $dept->slug }}').toString()">
                            {{ $dept->name }}
                            <x-lucide-chevron-down class="size-5 text-muted transition-transform" ::class="dept === '{{ $dept->slug }}' && 'rotate-180'" aria-hidden="true" />
                        </button>
                        <div x-show="dept === '{{ $dept->slug }}'" x-collapse x-cloak>
                            <ul class="bg-canvas px-4 py-2">
                                <li><a href="{{ $dept->url }}" class="flex min-h-11 items-center text-sm font-semibold text-primary">Shop all {{ $dept->name }}</a></li>
                                @foreach ($dept->children as $group)
                                    <li>
                                        <a href="{{ $group->url }}" class="flex min-h-11 items-center gap-2 text-sm {{ $group->is_restricted ? 'text-muted' : 'text-ink-2' }}">
                                            {{ $group->name }} @if ($group->is_restricted)<span class="badge badge-neutral">18+</span>@endif
                                        </a>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    </div>
                @endforeach
                <ul class="px-4 py-3 text-[0.9375rem]">
                    <li><a href="{{ route('shop') }}" class="flex min-h-11 items-center gap-3"><x-lucide-layout-grid class="size-5 text-muted" aria-hidden="true" />Shop All</a></li>
                    <li><a href="{{ route('track') }}" class="flex min-h-11 items-center gap-3"><x-lucide-package-search class="size-5 text-muted" aria-hidden="true" />Track Order</a></li>
                    <li><a href="{{ auth()->check() ? route('account') : route('login') }}" class="flex min-h-11 items-center gap-3"><x-lucide-user class="size-5 text-muted" aria-hidden="true" />{{ auth()->check() ? 'My Account' : 'Sign in / Register' }}</a></li>
                    <li><a href="{{ route('page', 'contact') }}" class="flex min-h-11 items-center gap-3"><x-lucide-headset class="size-5 text-muted" aria-hidden="true" />Contact Us</a></li>
                </ul>
            </div>
        </div>
    </div>

    <main id="main" class="flex-1">
        @yield('content')
    </main>

    @include('partials.footer')

    @hasSection('hide-tabbar')
    @else
        @include('partials.tabbar')
        <div class="h-[3.75rem] lg:hidden" aria-hidden="true"></div>
    @endif

    {{-- WhatsApp contact button --}}
    <a href="{{ wa_link('Assalam o Alaikum! I need help with '.config('store.name').'.') }}" target="_blank" rel="noopener"
        class="fixed right-4 bottom-4 z-30 grid size-12 place-items-center rounded-full bg-[#1faa53] text-white shadow-e3 transition-transform hover:scale-105 lg:size-14 @yield('wa-offset', 'max-lg:bottom-[4.75rem]')"
        aria-label="Chat with us on WhatsApp">
        <x-brand-icon name="whatsapp" class="size-7" />
    </a>

    @include('partials.toasts')
    @stack('scripts')
</body>
</html>
