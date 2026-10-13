@extends('layouts.store')

@section('title', $title)

@php
    $crumbs = [];
    foreach ($trail as $node) { $crumbs[$node->name] = $node->url; }
    if (! $trail) { $crumbs[$q !== null ? 'Search' : 'Shop All'] = null; }
    $resetUrl = url()->current().($q !== null && $q !== '' ? '?q='.urlencode($q) : '');
@endphp

@section('content')
    <div class="container-page pt-5 pb-4" x-data="{ filters: false }" :class="filters && 'overflow-hidden'">
        <x-breadcrumbs :items="$crumbs" />

        <header class="mt-4 flex flex-wrap items-end justify-between gap-4">
            <div>
                <h1 class="display flex items-center gap-2.5 text-4xl sm:text-5xl">
                    <span data-split>{{ $title }}</span>
                    @if ($category?->is_restricted)<span class="badge badge-neutral">18+</span>@endif
                </h1>
                <p class="mt-1 text-sm text-muted" aria-live="polite">{{ number_format($products->total()) }} {{ Str::plural('product', $products->total()) }}</p>
            </div>
            @if ($category?->is_restricted)
                <p class="flex items-center gap-2 text-[0.8125rem] text-muted"><x-lucide-package class="size-4" aria-hidden="true" />Delivered in plain, discreet packaging</p>
            @endif
        </header>

        @if ($q !== null)
            <form action="{{ route('search') }}" class="mt-5 flex max-w-xl gap-2 md:hidden" role="search">
                <label for="page-search" class="sr-only">Search products</label>
                <input id="page-search" type="search" name="q" value="{{ $q }}" class="field" placeholder="Search products" autofocus>
                <button class="btn btn-primary px-4" aria-label="Search"><x-lucide-search class="size-5" aria-hidden="true" /></button>
            </form>
        @endif

        {{-- Sub-category shortcuts --}}
        @if ($browse['links']->isNotEmpty())
            <nav class="no-scrollbar -mx-4 mt-5 flex gap-2 overflow-x-auto px-4 sm:mx-0 sm:flex-wrap sm:px-0" aria-label="{{ $browse['heading'] }}">
                @isset($browse['parent'])
                    <a href="{{ $browse['parent']->url }}" class="chip shrink-0 {{ $category->is($browse['parent']) ? 'chip-active' : '' }}">All</a>
                @endisset
                @foreach ($browse['links'] as $link)
                    <a href="{{ $link->url }}" class="chip shrink-0 {{ $category?->is($link) ? 'chip-active' : '' }}" @if ($category?->is($link)) aria-current="page" @endif>{{ $link->name }}</a>
                @endforeach
            </nav>
        @endif

        <div class="mt-6 grid gap-8 lg:grid-cols-[15.625rem_1fr]">
            {{-- Filters: sidebar on desktop, bottom sheet on mobile --}}
            <aside class="hidden lg:block" aria-label="Filters">
                <form method="get" action="{{ url()->current() }}" class="sticky top-36" x-data @change="$el.requestSubmit()">
                    @include('partials.filters', ['idPrefix' => 'd'])
                </form>
            </aside>

            <div>
                <div class="flex items-center justify-between gap-3 border-b border-line pb-4">
                    <button type="button" class="btn btn-outline btn-sm lg:hidden" @click="filters = true">
                        <x-lucide-sliders-horizontal class="size-4" aria-hidden="true" />Filters
                        @if ($activeCount)<span class="grid size-5 place-items-center rounded-full bg-primary text-[0.6875rem] text-white">{{ $activeCount }}</span>@endif
                    </button>
                    <p class="hidden text-sm text-muted lg:block">
                        @if ($activeCount)
                            {{ $activeCount }} {{ Str::plural('filter', $activeCount) }} applied · <a href="{{ $resetUrl }}" class="link">Clear all</a>
                        @else
                            Page {{ $products->currentPage() }} of {{ $products->lastPage() }}
                        @endif
                    </p>
                    <form method="get" action="{{ url()->current() }}" class="flex items-center gap-2" x-data>
                        @foreach (Arr::dot(request()->except(['sort', 'page'])) as $key => $value)
                            <input type="hidden" name="{{ preg_replace('/\.(\w+)/', '[$1]', preg_replace('/\.\d+$/', '[]', $key)) }}" value="{{ $value }}">
                        @endforeach
                        <label for="sort" class="text-sm text-muted">Sort</label>
                        <select id="sort" name="sort" class="field field-sm w-auto pr-8" @change="$el.form.requestSubmit()">
                            @foreach ($sorts as $key => $label)
                                <option value="{{ $key }}" @selected($sort === $key)>{{ $label }}</option>
                            @endforeach
                        </select>
                    </form>
                </div>

                @if ($products->isEmpty())
                    <div class="mx-auto max-w-md py-20 text-center">
                        <span class="mx-auto grid size-16 place-items-center rounded-full bg-sunken text-muted"><x-lucide-search-x class="size-7" aria-hidden="true" /></span>
                        <h2 class="mt-5 text-lg font-bold">
                            @if ($q) Nothing found for “{{ $q }}” @elseif ($activeCount) No products match these filters @else Products are being added here @endif
                        </h2>
                        <p class="mt-2 text-sm text-muted">
                            @if ($q) Check the spelling or try a more general word such as “kurti” or “organiser”.
                            @elseif ($activeCount) Remove a filter or two to see more products.
                            @else We are confirming stock with our suppliers. Please check back soon. @endif
                        </p>
                        <a href="{{ $activeCount ? $resetUrl : route('shop') }}" class="btn btn-primary mt-6">{{ $activeCount ? 'Clear all filters' : 'Browse all products' }}</a>
                    </div>
                @else
                    <ul class="mt-6 grid grid-cols-2 gap-x-4 gap-y-8 sm:grid-cols-3 xl:grid-cols-4 xl:gap-x-5">
                        @foreach ($products as $product)
                            <li><x-product-card :product="$product" class="h-full" /></li>
                        @endforeach
                    </ul>
                    <div class="mt-10">{{ $products->links() }}</div>
                @endif
            </div>
        </div>

        {{-- Mobile filter sheet --}}
        <div x-show="filters" x-cloak class="fixed inset-0 z-50 lg:hidden" role="dialog" aria-modal="true" aria-label="Filters" @keydown.escape.window="filters = false">
            <div class="absolute inset-0 bg-ink/50" @click="filters = false" x-show="filters" x-transition.opacity></div>
            <form method="get" action="{{ url()->current() }}" x-show="filters"
                x-transition:enter="transition duration-200 ease-out" x-transition:enter-start="translate-y-full" x-transition:enter-end="translate-y-0"
                class="absolute inset-x-0 bottom-0 flex max-h-[88vh] flex-col rounded-t-2xl bg-surface">
                <div class="flex items-center justify-between border-b border-line px-4 py-3">
                    <h2 class="text-base font-bold">Filters</h2>
                    <button type="button" class="icon-btn -mr-2" @click="filters = false" aria-label="Close filters"><x-lucide-x class="size-5" aria-hidden="true" /></button>
                </div>
                <div class="flex-1 overflow-y-auto px-4 py-2">
                    @include('partials.filters', ['idPrefix' => 'm'])
                </div>
                <div class="flex gap-3 border-t border-line p-4">
                    <a href="{{ $resetUrl }}" class="btn btn-outline flex-1">Clear all</a>
                    <button class="btn btn-primary flex-1">Show results</button>
                </div>
            </form>
        </div>
    </div>
@endsection
