@props(['product'])
@php
    $availability = $product->availability;
    $saved = in_array($product->id, session('wishlist', []), true);
    $canBuy = in_array($availability, ['in_stock', 'low_stock'], true);
    $hasOptions = $product->colours || $product->sizes;
@endphp
<article {{ $attributes->class('product-card group relative flex flex-col') }}>
    <div data-tilt class="tilt-glare relative aspect-[4/5] overflow-hidden rounded-card bg-sunken">
        <a href="{{ $product->url }}" tabindex="-1" aria-hidden="true" class="block size-full">
            <x-product-image :product="$product" class="transition-transform duration-700 ease-out group-hover:scale-[1.07]" />
        </a>

        <div class="pointer-events-none absolute top-2.5 left-2.5 flex flex-col items-start gap-1.5">
            @if ($availability === 'sold_out')
                <span class="badge bg-ink text-white">Sold out</span>
            @elseif ($product->on_sale)
                <span class="badge bg-accent text-white">−{{ $product->discount_pct }}%</span>
            @elseif ($product->is_new)
                <span class="badge bg-surface text-ink shadow-e1">New</span>
            @endif
        </div>

        <button type="button" x-data="wishlistButton('{{ route('wishlist.toggle', $product) }}', {{ $saved ? 'true' : 'false' }})" @click="toggle"
            :aria-pressed="active.toString()" :aria-label="active ? 'Remove from wishlist' : 'Save to wishlist'"
            class="absolute top-1.5 right-1.5 z-10 grid size-10 place-items-center rounded-full bg-surface/90 text-ink-2 shadow-e1 backdrop-blur transition-colors hover:text-accent">
            <x-lucide-heart class="size-[1.125rem]" ::class="active && 'fill-accent text-accent'" aria-hidden="true" />
        </button>

        {{-- One-tap add when there is nothing to choose; otherwise go and pick a size or colour --}}
        @if ($canBuy && ! $hasOptions)
            <form method="post" action="{{ route('cart.add') }}" data-cart-form class="absolute right-2 bottom-2 z-10">
                @csrf
                <input type="hidden" name="product_id" value="{{ $product->id }}">
                <button class="quick-add grid size-11 place-items-center rounded-full bg-night text-white shadow-e2 transition duration-300 hover:scale-110 hover:bg-primary" aria-label="Add {{ $product->title }} to cart" title="Add to cart">
                    <x-lucide-plus class="size-5" aria-hidden="true" />
                </button>
            </form>
        @elseif ($canBuy)
            <a href="{{ $product->url }}" class="quick-add absolute right-2 bottom-2 z-10 grid size-11 place-items-center rounded-full bg-surface text-ink shadow-e2 transition duration-300 hover:scale-110 hover:bg-night hover:text-white" aria-label="Choose options for {{ $product->title }}" title="Choose options">
                <x-lucide-shopping-bag class="size-[1.125rem]" aria-hidden="true" />
            </a>
        @endif
    </div>

    <div class="flex flex-1 flex-col px-0.5 pt-3">
        @if ($product->brand)
            <p class="text-xs font-medium tracking-wide text-muted uppercase">{{ $product->brand }}</p>
        @endif
        <h3 class="mt-0.5 font-sans text-[0.9375rem] leading-snug font-medium tracking-normal text-ink">
            <a href="{{ $product->url }}" class="line-clamp-2 after:absolute after:inset-0 after:z-0 hover:text-primary">{{ $product->title }}</a>
        </h3>
        <x-rating :value="$product->rating_avg" :count="$product->rating_count" class="mt-1" />

        <div class="mt-auto flex flex-wrap items-baseline gap-x-2 pt-2">
            <span class="text-base font-bold {{ $product->on_sale ? 'text-accent' : 'text-ink' }}">{{ money($product->price) }}</span>
            @if ($product->on_sale)
                <span class="text-sm text-muted line-through">{{ money($product->compare_at_price) }}</span>
            @endif
        </div>
        @if ($availability === 'low_stock')
            <p class="mt-1 text-xs font-semibold text-accent">{{ $product->availability_label }}</p>
        @elseif ($availability === 'unconfirmed')
            <p class="mt-1 text-xs text-muted">Availability being confirmed</p>
        @endif
    </div>
</article>
