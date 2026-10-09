{{-- Phone landing: built like an app home screen rather than a shrunken desktop page. --}}
@php
    $fanSlots = [['-5.9rem', '1.1rem', '-11deg', 0], ['0rem', '0rem', '0deg', 10], ['5.9rem', '1.1rem', '11deg', 0]];
    $fan = $deck->take(3)->values();
    // Middle card on top: reorder so the best seller sits in the centre slot.
    $fan = $fan->count() === 3 ? collect([$fan[1], $fan[0], $fan[2]]) : $fan;
@endphp
<div class="lg:hidden">
    {{-- Department "stories" --}}
    <nav aria-label="Departments" class="pt-4">
        <ul class="no-scrollbar flex gap-3.5 overflow-x-auto px-4 pb-1" data-reveal-group>
            @foreach ($departments as $dept)
                @php $photo = $deptPhotos[$dept->slug] ?? null; $deals = $dept->kind === 'collection'; @endphp
                <li class="w-[4.5rem] shrink-0">
                    <a href="{{ $dept->url }}" class="flex flex-col items-center gap-1.5 text-center">
                        <span class="block rounded-full p-[2.5px] {{ $deals ? 'bg-linear-to-tr from-accent to-amber-400' : 'bg-linear-to-tr from-primary to-lime' }}">
                            <span class="block size-[4.1rem] overflow-hidden rounded-full border-2 border-canvas bg-sunken">
                                @if ($photo)
                                    <img src="{{ $photo }}" alt="" class="size-full object-cover" decoding="async">
                                @else
                                    <span class="grid size-full place-items-center {{ $deals ? 'bg-accent-soft text-accent' : 'bg-primary-soft text-primary' }}">@svg('lucide-'.($dept->icon ?: 'tag'), 'size-6', ['stroke-width' => '1.75', 'aria-hidden' => 'true'])</span>
                                @endif
                            </span>
                        </span>
                        <span class="line-clamp-1 w-full text-[11px] leading-tight font-semibold">{{ Str::before($dept->name, ' &') }}</span>
                    </a>
                </li>
            @endforeach
        </ul>
    </nav>

    {{-- Hero card with a fanned stack of real products --}}
    <section class="px-4 pt-4" aria-labelledby="m-hero-title">
        <div class="hero relative isolate overflow-hidden rounded-[30px] px-6 pt-7 text-white">
            <div class="hero-grid absolute inset-0 -z-10" aria-hidden="true"></div>
            <div data-glow class="glow absolute -top-10 -right-10 -z-10 size-56 bg-primary/70" aria-hidden="true"></div>

            <p data-hero-fade class="inline-flex items-center gap-2 rounded-full border border-white/15 bg-white/[0.07] py-1 pr-3 pl-1.5 text-xs font-medium">
                <span class="grid size-5 place-items-center rounded-full bg-lime text-night"><x-lucide-banknote class="size-3" aria-hidden="true" /></span>Cash on Delivery
            </p>
            <h1 id="m-hero-title" class="display mt-4 text-[2.6rem] leading-[0.98]" data-split data-delay="0.1">{{ $hero?->title ?? config('store.tagline') }}</h1>
            @if ($hero?->subtitle)
                <p data-hero-fade class="mt-3 text-[15px] text-white/70">{{ $hero->subtitle }}</p>
            @endif
            <div data-hero-fade class="mt-5 flex gap-2.5">
                <a href="{{ $hero?->cta_url ?: route('shop') }}" class="btn flex-1 bg-lime text-night">{{ $hero?->cta_label ?: 'Shop now' }}<x-lucide-arrow-right class="size-4" aria-hidden="true" /></a>
                <a href="{{ route('category', 'deals-trending') }}" class="btn border border-white/25 px-4 text-white"><x-lucide-flame class="size-4 text-lime" aria-hidden="true" />Deals</a>
            </div>

            @if ($fan->count() === 3)
                <ul data-fan class="fan mt-9" aria-label="Featured products">
                    @foreach ($fan as $i => $product)
                        @php [$tx, $ty, $rot, $z] = $fanSlots[$i]; @endphp
                        <li class="fan-card" style="--tx: {{ $tx }}; --ty: {{ $ty }}; --rot: {{ $rot }}; z-index: {{ $z }};">
                            <div><div>
                                <a href="{{ $product->url }}" class="block overflow-hidden rounded-[18px] bg-white p-1.5 text-ink shadow-[0_24px_50px_-18px_rgb(0_0_0/0.8)]">
                                    <span class="relative block aspect-[4/5] overflow-hidden rounded-[13px] bg-sunken">
                                        <x-product-image :product="$product" :eager="true" />
                                        @if ($product->on_sale)<span class="badge absolute top-1.5 left-1.5 bg-accent px-2 text-[10px] text-white">−{{ $product->discount_pct }}%</span>@endif
                                    </span>
                                    <span class="block px-1 pt-2 pb-1">
                                        <span class="line-clamp-1 text-xs font-semibold">{{ $product->title }}</span>
                                        <span class="text-xs font-bold">{{ money($product->price) }}</span>
                                    </span>
                                </a>
                            </div></div>
                        </li>
                    @endforeach
                </ul>
            @else
                <div class="h-7"></div>
            @endif
        </div>
    </section>

    {{-- Promises as swipeable chips --}}
    <ul class="no-scrollbar mt-4 flex gap-2 overflow-x-auto px-4" aria-label="Why shop with us">
        @foreach ([['truck', "{$minDays}–{$maxDays} day delivery"], ['refresh-ccw', '7-day exchange'], ['star', $avgRating.' average rating'], ['headset', 'WhatsApp support']] as [$icon, $label])
            <li class="flex shrink-0 items-center gap-2 rounded-full border border-line bg-surface py-2 pr-3.5 pl-2 text-[13px] font-medium">
                <span class="grid size-7 place-items-center rounded-full bg-ink text-lime">@svg('lucide-'.$icon, 'size-3.5', ['aria-hidden' => 'true'])</span>{{ $label }}
            </li>
        @endforeach
    </ul>
</div>
