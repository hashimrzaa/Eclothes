@extends('layouts.store')

@php
    $hero = $heroes->first();
    [$minDays, $maxDays] = config('store.delivery_days');
    // Resting positions of the five cards in the 3D deck: x, y, z (px) and tilt (deg).
    $slots = [[10, 10, 150, -2], [-200, 70, 10, -9], [215, -40, 40, 8], [-95, -150, -120, 6], [150, 165, -100, -7]];
    $ticker = ['Cash on Delivery', 'Nationwide delivery', '7-day easy exchange', 'Order confirmation on WhatsApp', 'New arrivals every week', 'Free delivery over '.money(config('store.free_shipping_over'))];
    $eyebrows = ['Trending now', 'Most ordered', 'For her', 'For him', 'For little ones', 'For your home', 'Just landed', 'More for less'];
@endphp

@section('content')
    @include('store.partials.home-mobile')

    {{-- ============ Hero: kinetic headline + floating 3D product deck ============ --}}
    <section data-hero class="hero relative isolate -mt-px hidden overflow-hidden text-white lg:block">
        <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
        <div data-glow class="glow absolute top-[8%] right-[12%] -z-10 size-80 bg-primary/60" aria-hidden="true"></div>
        <div data-glow class="glow absolute bottom-[5%] left-[30%] -z-10 size-72 bg-lime/15" aria-hidden="true"></div>

        <div class="container-page grid min-h-[min(820px,calc(100svh-6.5rem))] items-center gap-10 py-14 lg:grid-cols-[1.05fr_1fr] lg:py-20">
            <div data-hero-copy class="relative z-10">
                <p data-hero-fade class="inline-flex items-center gap-2.5 rounded-full border border-white/15 bg-white/[0.06] py-1.5 pr-4 pl-2 text-[13px] font-medium backdrop-blur">
                    <span class="grid size-6 place-items-center rounded-full bg-lime text-night"><x-lucide-banknote class="size-3.5" aria-hidden="true" /></span>
                    Pay when it reaches your door
                </p>
                <h1 class="display mt-6 text-[clamp(2.75rem,7.2vw,6rem)]" data-split data-delay="0.1">{{ $hero?->title ?? config('store.tagline') }}</h1>
                @if ($hero?->subtitle)
                    <p data-hero-fade class="mt-6 max-w-md text-base text-white/70 sm:text-lg">{{ $hero->subtitle }}</p>
                @endif
                <div data-hero-fade class="mt-8 flex flex-wrap gap-3">
                    <a href="{{ $hero?->cta_url ?: route('shop') }}" class="btn btn-lg group bg-lime pr-2 text-night hover:bg-white">
                        {{ $hero?->cta_label ?: 'Shop now' }}
                        <span class="grid size-9 place-items-center rounded-full bg-night text-white transition-transform duration-300 group-hover:-rotate-45"><x-lucide-arrow-right class="size-4" aria-hidden="true" /></span>
                    </a>
                    <a href="{{ route('shop') }}" class="btn btn-lg border border-white/25 text-white hover:bg-white/10">Browse everything</a>
                </div>

                <dl data-hero-fade class="mt-12 grid max-w-md grid-cols-3 gap-6 border-t border-white/12 pt-6">
                    <div><dd class="font-display text-3xl font-extrabold tracking-tight sm:text-4xl"><span data-count="{{ $productCount }}">{{ $productCount }}</span>+</dd><dt class="mt-1 text-[13px] text-white/60">Products in store</dt></div>
                    <div><dd class="font-display text-3xl font-extrabold tracking-tight sm:text-4xl">{{ $minDays }}–{{ $maxDays }}</dd><dt class="mt-1 text-[13px] text-white/60">Day delivery</dt></div>
                    <div><dd class="font-display text-3xl font-extrabold tracking-tight sm:text-4xl"><span data-count="7">7</span></dd><dt class="mt-1 text-[13px] text-white/60">Day easy exchange</dt></div>
                </dl>
            </div>

            {{-- 3D deck of real, in-stock products --}}
            @if ($deck->count() >= 3)
                <div data-deck-stage class="deck-stage relative hidden h-[620px] lg:block" aria-label="Featured products">
                    <ul data-deck class="deck absolute inset-0">
                        @foreach ($deck as $i => $product)
                            @php [$x, $y, $z, $r] = $slots[$i]; @endphp
                            <li data-deck-card class="deck-card" style="--x: {{ $x }}px; --y: {{ $y }}px; --z: {{ $z }}px; --r: {{ $r }}deg;">
                                <div><div>
                                    <a href="{{ $product->url }}" class="deck-face block p-2.5 text-ink">
                                        <span class="relative block aspect-[4/5] overflow-hidden rounded-[14px] bg-sunken">
                                            <x-product-image :product="$product" :eager="true" />
                                            @if ($product->on_sale)<span class="badge absolute top-2 left-2 bg-accent text-white">−{{ $product->discount_pct }}%</span>@endif
                                        </span>
                                        <span class="block px-1.5 pt-3 pb-1.5">
                                            <span class="line-clamp-1 text-sm font-semibold">{{ $product->title }}</span>
                                            <span class="mt-0.5 flex items-center justify-between text-[13px]">
                                                <span class="font-bold">{{ money($product->price) }}</span>
                                                @if ($product->rating_count)<span class="flex items-center gap-1 text-muted"><x-lucide-star class="size-3.5 fill-amber-500 text-amber-500" aria-hidden="true" />{{ number_format($product->rating_avg, 1) }}</span>@endif
                                            </span>
                                        </span>
                                    </a>
                                </div></div>
                            </li>
                        @endforeach
                    </ul>
                    <div class="absolute top-4 left-2" style="transform: translateZ(120px)"><p data-hero-fade data-parallax="-30" class="flex items-center gap-2.5 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold backdrop-blur-md">
                        <span class="grid size-9 place-items-center rounded-full bg-lime text-night"><x-lucide-star class="size-4 fill-current" aria-hidden="true" /></span>
                        <span>{{ $avgRating }} / 5<span class="block text-xs font-normal text-white/60">from {{ number_format($reviewCount) }} reviews</span></span>
                    </p></div>
                    <div class="absolute bottom-20 left-0" style="transform: translateZ(120px)"><p data-hero-fade data-parallax="-45" class="flex items-center gap-2.5 rounded-2xl border border-white/15 bg-white/10 px-4 py-3 text-sm font-semibold backdrop-blur-md">
                        <span class="grid size-9 place-items-center rounded-full bg-white text-night"><x-lucide-truck class="size-4" aria-hidden="true" /></span>
                        <span>Delivered in {{ $minDays }}–{{ $maxDays }} days<span class="block text-xs font-normal text-white/60">Pay at your door</span></span>
                    </p></div>
                </div>
            @endif
        </div>

    </section>

    {{-- ============ Marquee ============ --}}
    <div data-marquee class="mt-6 border-y border-ink bg-lime py-3 text-night lg:mt-0 lg:py-4" aria-label="{{ implode(', ', $ticker) }}">
        <ul class="font-display text-lg font-bold tracking-tight sm:text-xl">
            @foreach ($ticker as $item)
                <li class="flex items-center gap-8 pr-8 whitespace-nowrap">{{ $item }}<x-lucide-asterisk class="size-5" aria-hidden="true" /></li>
            @endforeach
        </ul>
    </div>

    {{-- ============ Departments: scroll-driven horizontal 3D gallery ============ --}}
    <section id="departments" data-hscroll class="hidden overflow-hidden py-10 lg:block">
        <div class="container-page mb-8 flex items-end justify-between gap-6">
            <div>
                <p class="eyebrow mb-3" data-reveal>Shop by category</p>
                <h2 class="display text-4xl sm:text-5xl lg:text-6xl" data-split>Eight departments. One store.</h2>
            </div>
            <div class="hidden w-48 shrink-0 lg:block" aria-hidden="true">
                <div class="h-0.5 overflow-hidden rounded-full bg-line-strong"><div data-hscroll-progress class="h-full origin-left scale-x-0 bg-ink"></div></div>
                <p class="mt-2 text-right text-xs text-muted">Keep scrolling</p>
            </div>
        </div>
        <ul data-hscroll-track class="px-4 sm:px-6 lg:px-[max(2rem,calc((100vw-1280px)/2+2rem))]">
            @foreach ($departments as $i => $dept)
                @php $deals = $dept->kind === 'collection'; @endphp
                <li class="w-[78vw] max-w-[380px] shrink-0 sm:w-[380px]">
                    <a href="{{ $dept->url }}" class="group/panel relative flex h-[440px] flex-col overflow-hidden rounded-[28px] p-7 transition-shadow duration-500 hover:shadow-e3 {{ $deals ? 'bg-night text-white' : ($i % 2 ? 'bg-primary-soft' : 'border border-line bg-surface') }}">
                        <span class="font-display text-sm font-bold tabular-nums opacity-50">{{ str_pad($i + 1, 2, '0', STR_PAD_LEFT) }} / {{ str_pad($departments->count(), 2, '0', STR_PAD_LEFT) }}</span>
                        <span class="pointer-events-none absolute -right-10 -bottom-10 opacity-[0.07] transition-transform duration-700 ease-out group-hover/panel:scale-110 group-hover/panel:-rotate-6 {{ $deals ? 'text-lime opacity-20' : '' }}" aria-hidden="true">
                            @svg('lucide-'.($dept->icon ?: 'tag'), 'size-72', ['stroke-width' => '0.75'])
                        </span>
                        <span class="mt-auto">
                            <span class="grid size-14 place-items-center rounded-2xl {{ $deals ? 'bg-lime text-night' : 'bg-ink text-white' }}">@svg('lucide-'.($dept->icon ?: 'tag'), 'size-6', ['stroke-width' => '1.75', 'aria-hidden' => 'true'])</span>
                            <span class="display mt-5 block text-4xl">{{ $dept->name }}</span>
                            <span class="mt-3 flex flex-wrap gap-1.5">
                                @foreach ($dept->children->where('is_restricted', false)->take(3) as $group)
                                    <span class="rounded-full px-2.5 py-1 text-xs font-medium {{ $deals ? 'bg-white/10 text-white/80' : 'bg-ink/[0.06] text-ink-2' }}">{{ $group->name }}</span>
                                @endforeach
                            </span>
                            <span class="mt-6 flex items-center justify-between border-t pt-4 text-sm font-semibold {{ $deals ? 'border-white/15' : 'border-ink/10' }}">
                                Explore
                                <span class="grid size-9 place-items-center rounded-full transition-transform duration-300 group-hover/panel:-rotate-45 {{ $deals ? 'bg-white text-night' : 'bg-ink text-white' }}"><x-lucide-arrow-right class="size-4" aria-hidden="true" /></span>
                            </span>
                        </span>
                    </a>
                </li>
            @endforeach
        </ul>
    </section>

    {{-- ============ Product rails (order follows the homepage spec) ============ --}}
    @foreach ($rails as $n => [$title, $subtitle, $path, $products])
        <x-rail :title="$title" :subtitle="$subtitle" :eyebrow="$eyebrows[$n] ?? null" :href="route('category', $path)" :products="$products->take(5)" class="mt-14 lg:mt-28" />

        @if ($loop->iteration === 2)
            {{-- Bento of shortcuts for deal seekers --}}
            <section class="container-page mt-14 lg:mt-28">
                <ul class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:grid-rows-2 lg:gap-4" data-reveal-group>
                    <li class="col-span-2 lg:row-span-2">
                        <a href="{{ route('category', 'deals-trending/deals/under-rs-499') }}" data-tilt class="tilt-glare group relative flex h-full min-h-60 flex-col justify-between overflow-hidden rounded-[28px] bg-night p-6 text-white sm:p-8 lg:min-h-72">
                            <span class="pointer-events-none absolute -top-16 -right-16 size-72 rounded-full bg-accent/50 blur-3xl" data-parallax="18" aria-hidden="true"></span>
                            <span class="eyebrow relative text-lime">Budget finds</span>
                            <span class="relative">
                                <span class="block text-sm text-white/60">Everything</span>
                                <span class="display block text-5xl sm:text-7xl">Under<br>Rs. 499</span>
                                <span class="mt-6 inline-flex items-center gap-2 text-sm font-semibold">Shop the edit <span class="grid size-9 place-items-center rounded-full bg-lime text-night transition-transform duration-300 group-hover:-rotate-45"><x-lucide-arrow-right class="size-4" aria-hidden="true" /></span></span>
                            </span>
                        </a>
                    </li>
                    @foreach ([
                        ['deals-trending/deals/under-rs-999', 'Everyday value', 'Under Rs. 999', 'bg-primary text-white', 'tag'],
                        ['deals-trending/deals/flash-deals', 'Limited time', 'Flash deals', 'bg-accent-soft', 'zap'],
                        ['deals-trending/deals/bundle-deals', 'Save more', 'Bundle deals', 'border border-line bg-surface', 'boxes'],
                        ['deals-trending/discovery/limited-stock', 'Almost gone', 'Limited stock', 'bg-lime text-night', 'flame'],
                    ] as [$path, $kicker, $label, $tone, $icon])
                        <li>
                            <a href="{{ route('category', $path) }}" data-tilt class="tilt-glare group relative flex h-full min-h-32 flex-col justify-between overflow-hidden rounded-3xl p-4 sm:p-6 lg:min-h-40 lg:rounded-[28px] {{ $tone }}">
                                <span class="flex items-start justify-between">
                                    <span class="text-[13px] font-semibold opacity-70">{{ $kicker }}</span>
                                    @svg('lucide-'.$icon, 'size-6 opacity-60', ['stroke-width' => '1.5', 'aria-hidden' => 'true'])
                                </span>
                                <span class="flex items-end justify-between gap-3">
                                    <span class="display text-xl sm:text-3xl">{{ $label }}</span>
                                    <x-lucide-arrow-up-right class="size-6 shrink-0 transition-transform duration-300 group-hover:translate-x-1 group-hover:-translate-y-1" aria-hidden="true" />
                                </span>
                            </a>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif

        @if ($loop->iteration === 4)
            {{-- Promise statement that lights up as it is read --}}
            <section class="container-page mt-24 lg:mt-36">
                <p class="eyebrow mb-6" data-reveal>Why shop with us</p>
                <p class="display max-w-5xl text-[clamp(2rem,5vw,4.5rem)] leading-[1.02]" data-scrub-text>Pay only when it arrives. Exchange within seven days. And talk to real people on WhatsApp, every step of the way.</p>
                <ul class="mt-12 grid gap-4 sm:grid-cols-2 lg:grid-cols-4" data-reveal-group>
                    @foreach ([
                        ['banknote', 'Cash on Delivery', 'No advance payment. Pay the courier at your door.'],
                        ['truck', 'Nationwide delivery', "Delivered in {$minDays}–{$maxDays} working days."],
                        ['refresh-ccw', 'Easy exchange', 'Wrong size or damaged item? We fix it.'],
                        ['headset', 'WhatsApp support', 'We confirm and track every order with you.'],
                    ] as [$icon, $heading, $text])
                        <li class="card p-6">
                            <span class="grid size-12 place-items-center rounded-2xl bg-ink text-lime">@svg('lucide-'.$icon, 'size-5', ['aria-hidden' => 'true'])</span>
                            <h3 class="mt-5 text-lg font-bold">{{ $heading }}</h3>
                            <p class="mt-1 text-sm text-muted">{{ $text }}</p>
                        </li>
                    @endforeach
                </ul>
            </section>
        @endif
    @endforeach

    {{-- ============ Customer reviews ============ --}}
    @if ($reviews->isNotEmpty())
        <section class="container-page mt-24 lg:mt-36">
            <p class="eyebrow mb-3" data-reveal>Customer reviews</p>
            <h2 class="display text-4xl sm:text-5xl lg:text-6xl" data-split>Loved across Pakistan.</h2>
            <div class="scroll-row -mx-4 mt-10 px-4 sm:-mx-6 sm:px-6 lg:mx-0 lg:grid lg:grid-cols-3 lg:overflow-visible lg:px-0" data-reveal-group>
                @foreach ($reviews as $review)
                    <figure data-tilt class="card flex w-[85%] shrink-0 snap-start flex-col p-7 sm:w-[46%] lg:w-auto">
                        <x-lucide-quote class="size-8 text-primary/30" aria-hidden="true" />
                        <blockquote class="mt-4 flex-1 text-lg leading-snug font-medium text-ink">{{ $review->body }}</blockquote>
                        <figcaption class="mt-6 flex items-center gap-3 border-t border-line pt-5 text-sm">
                            <span class="grid size-10 shrink-0 place-items-center rounded-full bg-ink font-display font-bold text-lime">{{ mb_substr($review->name, 0, 1) }}</span>
                            <span class="min-w-0 flex-1">
                                <span class="block font-semibold">{{ $review->name }}@if ($review->city)<span class="font-normal text-muted"> · {{ $review->city }}</span>@endif</span>
                                <a href="{{ $review->product->url }}" class="block truncate text-[13px] text-muted hover:text-primary">{{ $review->product->title }}</a>
                            </span>
                            <x-rating :value="$review->rating" />
                        </figcaption>
                    </figure>
                @endforeach
            </div>
        </section>
    @endif

    {{-- ============ Instagram / TikTok ============ --}}
    <section class="container-page mt-24 lg:mt-36">
        <div class="hero relative isolate overflow-hidden rounded-[32px] px-6 py-16 text-center text-white sm:px-12 sm:py-24" data-reveal>
            <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
            <p class="eyebrow justify-center text-lime">Follow along</p>
            <h2 class="display mx-auto mt-5 max-w-3xl text-4xl sm:text-6xl">See it before you buy it.</h2>
            <p class="mx-auto mt-5 max-w-md text-white/70">Product reels, try-ons and new arrival drops every week.</p>
            <div class="mt-9 flex flex-wrap justify-center gap-3">
                <a href="{{ config('store.socials.instagram') }}" target="_blank" rel="noopener" class="btn btn-lg bg-white text-night hover:bg-lime"><x-brand-icon name="instagram" class="size-5" />Instagram</a>
                <a href="{{ config('store.socials.tiktok') }}" target="_blank" rel="noopener" class="btn btn-lg border border-white/25 text-white hover:bg-white/10"><x-brand-icon name="tiktok" class="size-5" />TikTok</a>
            </div>
        </div>
    </section>
@endsection
