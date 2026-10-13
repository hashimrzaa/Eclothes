@extends('layouts.store')

@section('title', $title)

@php
    $crumbs = [];
    foreach ($trail as $node) { $crumbs[$node->name] = $node->url; }
    if (! $trail) { $crumbs[$q !== null ? 'Search' : 'Shop All'] = null; }

    $here = url()->current();
    $query = request()->except('page');
    $resetUrl = $here.($q !== null && $q !== '' ? '?q='.urlencode($q) : '');
    $without = fn (array $q2) => $here.($q2 ? '?'.http_build_query($q2) : '');

    // One removable chip per applied filter.
    $chips = [];
    foreach ($selected as $key => $values) {
        foreach ($values as $value) {
            $next = $query;
            $next['f'][$key] = array_values(array_diff($next['f'][$key] ?? [], [$value]));
            $chips[] = [config("store.filter_labels.$key").': '.$value, $without($next)];
        }
    }
    if (request('min') || request('max')) {
        $chips[] = ['Price: '.money(request('min') ?: $priceRange[0]).' – '.money(request('max') ?: $priceRange[1]), $without(Arr::except($query, ['min', 'max']))];
    }
    foreach (['in_stock' => 'In stock', 'on_sale' => 'On sale', 'new' => 'New arrivals'] as $flag => $label) {
        if (request()->boolean($flag)) { $chips[] = [$label, $without(Arr::except($query, [$flag]))]; }
    }
    if (request('rating')) { $chips[] = [request('rating').'★ & up', $without(Arr::except($query, ['rating']))]; }
@endphp

@section('content')
    <div class="container-page pt-4 lg:pt-5">
        <x-breadcrumbs :items="$crumbs" />

        <header class="mt-3 flex flex-wrap items-end justify-between gap-x-6 gap-y-2">
            <h1 class="display flex items-center gap-2.5 text-3xl sm:text-4xl lg:text-5xl">
                <span data-split>{{ $title }}</span>
                @if ($category?->is_restricted)<span class="badge badge-neutral">18+</span>@endif
            </h1>
            @if ($category?->is_restricted)
                <p class="flex items-center gap-2 text-[0.8125rem] text-muted"><x-lucide-package class="size-4" aria-hidden="true" />Delivered in plain, discreet packaging</p>
            @endif
        </header>

        {{-- Search page: popular places to start --}}
        @if ($q !== null && $popular->isNotEmpty() && ($q === '' || $products->isEmpty()))
            <div class="mt-4">
                <p class="text-sm font-semibold">Popular categories</p>
                <ul class="mt-2.5 flex flex-wrap gap-2">
                    @foreach ($popular as $cat)<li><a href="{{ $cat->url }}" class="chip">{{ $cat->name }}</a></li>@endforeach
                </ul>
            </div>
        @endif

        {{-- Sub-category shortcuts --}}
        @if ($q === null && $browse['links']->isNotEmpty())
            <nav class="no-scrollbar -mx-4 mt-4 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="{{ $browse['heading'] }}">
                @isset($browse['parent'])
                    <a href="{{ $browse['parent']->url }}" class="chip shrink-0 {{ $category->is($browse['parent']) ? 'chip-active' : '' }}">All</a>
                @endisset
                @foreach ($browse['links'] as $link)
                    <a href="{{ $link->url }}" class="chip shrink-0 {{ $category?->is($link) ? 'chip-active' : '' }}" @if ($category?->is($link)) aria-current="page" @endif>{{ $link->name }}</a>
                @endforeach
            </nav>
        @endif

        {{-- Only this frame is replaced when a filter, the sort order or the page changes --}}
        <turbo-frame id="results" target="_top" data-turbo-action="advance" class="mt-4 block" x-data="{ sheet: false }">
            {{-- Filter bar --}}
            <div class="filter-bar sticky top-0 z-20 -mx-4 border-y border-line bg-canvas/90 px-4 py-2.5 backdrop-blur-xl sm:-mx-6 sm:px-6 lg:mx-0 lg:rounded-2xl lg:border lg:bg-surface/90 lg:px-3">
                {{-- Desktop: every filter is one click away, right above the results --}}
                <form method="get" action="{{ $here }}" data-turbo-frame="results" class="hidden items-start gap-2 lg:flex" x-ref="bar">
                    @if ($q !== null && $q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
                    <div class="flex flex-1 flex-wrap items-center gap-2">
                        @include('partials.filters', ['mode' => 'bar', 'idPrefix' => 'b'])
                    </div>
                    <div class="flex shrink-0 items-center gap-2">
                        <label for="sort-d" class="hidden text-sm text-muted sm:block">Sort</label>
                        <select id="sort-d" name="sort" class="field field-sm h-9 w-auto max-w-[8.5rem] rounded-full pr-8 font-medium sm:max-w-none" onchange="this.form.requestSubmit()">
                            @foreach ($sorts as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>

                {{-- Phones and tablets: a Filters button, quick toggles and the sort order --}}
                <form method="get" action="{{ $here }}" data-turbo-frame="results" class="flex items-center gap-2 lg:hidden">
                    @if ($q !== null && $q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
                    <button type="button" class="chip shrink-0 border-night bg-night text-white hover:bg-ink-2" @click="sheet = true" aria-haspopup="dialog">
                        <x-lucide-sliders-horizontal class="size-4" aria-hidden="true" />Filters
                        @if ($activeCount)<span class="grid size-5 place-items-center rounded-full bg-lime text-[0.6875rem] font-bold text-night">{{ $activeCount }}</span>@endif
                    </button>
                    <div class="no-scrollbar flex flex-1 gap-2 overflow-x-auto">
                        @foreach (['in_stock' => 'In stock', 'on_sale' => 'On sale'] as $flag => $label)
                            <label class="chip shrink-0 has-checked:border-primary has-checked:bg-primary-soft has-checked:text-primary">
                                <input type="checkbox" name="{{ $flag }}" value="1" class="sr-only" @checked(request()->boolean($flag)) onchange="this.form.requestSubmit()">{{ $label }}
                            </label>
                        @endforeach
                    </div>
                    {{-- Keep what was chosen in the sheet when a quick toggle or the sort order changes --}}
                    @foreach (Arr::dot(request()->except(['sort', 'page', 'in_stock', 'on_sale', 'q'])) as $key => $value)
                        <input type="hidden" name="{{ preg_replace('/\.(\w+)/', '[$1]', preg_replace('/\.\d+$/', '[]', $key)) }}" value="{{ $value }}">
                    @endforeach
                    <div class="flex shrink-0 items-center gap-2">
                        <label for="sort-m" class="hidden text-sm text-muted sm:block">Sort</label>
                        <select id="sort-m" name="sort" class="field field-sm h-9 w-auto max-w-[8.5rem] rounded-full pr-8 font-medium sm:max-w-none" onchange="this.form.requestSubmit()">
                            @foreach ($sorts as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </div>
                </form>
            </div>

            {{-- Result count + applied filters --}}
            <div class="mt-3 flex flex-wrap items-center gap-2">
                <p class="mr-1 text-sm text-muted" aria-live="polite"><strong class="font-semibold text-ink">{{ number_format($products->total()) }}</strong> {{ Str::plural('product', $products->total()) }}</p>
                @foreach ($chips as [$label, $url])
                    <a href="{{ $url }}" data-turbo-frame="results" class="inline-flex h-8 items-center gap-1.5 rounded-full bg-night pr-2 pl-3 text-[0.8125rem] font-medium text-white hover:bg-ink-2" aria-label="Remove filter {{ $label }}">
                        {{ $label }}<x-lucide-x class="size-3.5" aria-hidden="true" />
                    </a>
                @endforeach
                @if ($chips)
                    <a href="{{ $resetUrl }}" data-turbo-frame="results" class="link ml-1 text-sm">Clear all</a>
                @endif
            </div>

            @if ($products->isEmpty())
                <div class="mx-auto max-w-md py-14 text-center">
                    <span class="mx-auto grid size-16 place-items-center rounded-full bg-sunken text-muted"><x-lucide-search-x class="size-7" aria-hidden="true" /></span>
                    <h2 class="mt-5 text-lg font-bold">
                        @if ($q) Nothing found for “{{ $q }}” @elseif ($q === '') Search the whole store @elseif ($activeCount) No products match these filters @else Products are being added here @endif
                    </h2>
                    <p class="mt-2 text-sm text-muted">
                        @if ($q) Check the spelling, use fewer words, or try something general such as “kurti” or “organiser”.
                        @elseif ($q === '') Type a product, brand, colour or category in the search bar above.
                        @elseif ($activeCount) Remove a filter or two to see more products.
                        @else We are confirming stock with our suppliers. Please check back soon. @endif
                    </p>
                    @if ($activeCount)
                        <a href="{{ $resetUrl }}" data-turbo-frame="results" class="btn btn-primary mt-6">Clear all filters</a>
                    @elseif ($q === null)
                        <a href="{{ route('shop') }}" class="btn btn-primary mt-6">Browse all products</a>
                    @endif
                </div>
            @else
                <ul class="results-grid mt-4 grid grid-cols-2 gap-x-3 gap-y-7 sm:grid-cols-3 sm:gap-x-4 lg:grid-cols-5 lg:gap-x-5 lg:gap-y-9">
                    @foreach ($products as $product)
                        <li><x-product-card :product="$product" class="h-full" /></li>
                    @endforeach
                </ul>
                <div class="mt-9">{{ $products->links() }}</div>
            @endif

            {{-- Phone and tablet filter sheet --}}
            <div x-show="sheet" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Filters" @keydown.escape.window="sheet = false">
                <div class="absolute inset-0 bg-ink/55" @click="sheet = false" x-show="sheet" x-transition.opacity></div>
                <form method="get" action="{{ $here }}" data-turbo-frame="results" x-show="sheet" @submit="sheet = false"
                    x-transition:enter="transition duration-300 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                    x-transition:leave="transition duration-200 ease-in" x-transition:leave-end="translate-y-full"
                    class="absolute inset-x-0 bottom-0 flex max-h-[88dvh] flex-col rounded-t-[1.75rem] bg-surface md:inset-x-auto md:inset-y-0 md:right-0 md:max-h-none md:w-[26rem] md:rounded-l-[1.75rem] md:rounded-tr-none">
                    <div class="mx-auto mt-2.5 h-1 w-10 rounded-full bg-line-strong md:hidden" aria-hidden="true"></div>
                    <div class="flex items-center justify-between border-b border-line px-5 py-3">
                        <h2 class="text-lg font-bold">Filters</h2>
                        <button type="button" class="icon-btn -mr-2" @click="sheet = false" aria-label="Close filters"><x-lucide-x class="size-5" aria-hidden="true" /></button>
                    </div>
                    <div class="flex-1 overflow-y-auto overscroll-contain px-5" data-lenis-prevent>
                        @if ($q !== null && $q !== '')<input type="hidden" name="q" value="{{ $q }}">@endif
                        @if (request('sort'))<input type="hidden" name="sort" value="{{ request('sort') }}">@endif
                        @include('partials.filters', ['mode' => 'sheet', 'idPrefix' => 's'])
                    </div>
                    <div class="flex gap-3 border-t border-line p-4 pb-[max(1rem,env(safe-area-inset-bottom))]">
                        <a href="{{ $resetUrl }}" data-turbo-frame="results" class="btn btn-outline flex-1" @click="sheet = false">Clear all</a>
                        <button class="btn btn-primary flex-[2]">Show results</button>
                    </div>
                </form>
            </div>
        </turbo-frame>
    </div>

    @if ($suggested->isNotEmpty())
        <x-rail title="Popular right now" eyebrow="You might like" :products="$suggested->take(5)" class="mt-14" />
    @endif
@endsection
