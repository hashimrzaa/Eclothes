{{--
    Catalogue filters. Only the facets relevant to this department are passed in (spec §10).
    $mode = 'bar'   → compact dropdown pills above the results (desktop)
    $mode = 'sheet' → full list inside the slide-up sheet (phone / tablet)
--}}
@php
    $bar = $mode === 'bar';
    $swatches = config('store.swatches');
    $priceOn = request('min') || request('max');
    $toggles = array_filter(['in_stock' => 'In stock', 'on_sale' => 'On sale', 'new' => $showFashionToggles ? 'New arrivals' : null]);
@endphp

@if ($bar)
    {{-- Price --}}
    @if ($priceRange[1] > $priceRange[0])
        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" class="chip {{ $priceOn ? 'chip-active' : '' }}" @click="open = !open" :aria-expanded="open.toString()">
                Price <x-lucide-chevron-down class="size-4 transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" />
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.left class="absolute top-full left-0 z-30 mt-2 w-80 rounded-[1.25rem] border border-line bg-surface p-4 shadow-e3">
                @include('partials.price-range', ['idPrefix' => $idPrefix])
                <div class="mt-4 flex justify-end gap-2 border-t border-line pt-3"><button class="btn btn-primary btn-sm">Apply</button></div>
            </div>
        </div>
    @endif

    {{-- One dropdown per facet --}}
    @foreach ($facets as $key => $facet)
        @php $chosen = $selected[$key] ?? []; @endphp
        <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
            <button type="button" class="chip {{ $chosen ? 'chip-active' : '' }}" @click="open = !open" :aria-expanded="open.toString()">
                {{ $facet['label'] }}
                @if ($chosen)<span class="grid size-5 place-items-center rounded-full bg-primary text-[0.6875rem] font-bold text-white">{{ count($chosen) }}</span>@endif
                <x-lucide-chevron-down class="size-4 transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" />
            </button>
            <div x-show="open" x-cloak x-transition.origin.top.left class="absolute top-full left-0 z-30 mt-2 w-72 rounded-[1.25rem] border border-line bg-surface p-2 shadow-e3">
                <fieldset>
                    <legend class="sr-only">{{ $facet['label'] }}</legend>
                    @if ($key === 'colour')
                        <div class="grid grid-cols-6 gap-2 p-2">
                            @foreach ($facet['options'] as $option)
                                <label class="relative" title="{{ $option->value }} ({{ $option->total }})">
                                    <input type="checkbox" name="f[colour][]" value="{{ $option->value }}" class="peer sr-only" @checked(in_array($option->value, $chosen, true))>
                                    <span class="block aspect-square rounded-full border border-line-strong ring-offset-2 transition peer-checked:ring-2 peer-checked:ring-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ink" style="background: {{ $swatches[$option->value] ?? '#e5e7eb' }}"></span>
                                    <span class="sr-only">{{ $option->value }} ({{ $option->total }})</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif ($key === 'size')
                        <div class="flex flex-wrap gap-2 p-2">
                            @foreach ($facet['options'] as $option)
                                <label>
                                    <input type="checkbox" name="f[size][]" value="{{ $option->value }}" class="peer sr-only" @checked(in_array($option->value, $chosen, true))>
                                    <span class="grid h-9 min-w-10 place-items-center rounded-lg border border-line-strong px-2.5 text-sm font-medium transition-colors peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white peer-focus-visible:ring-2 peer-focus-visible:ring-ink hover:border-ink">{{ $option->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="max-h-64 overflow-y-auto overscroll-contain" data-lenis-prevent>
                            @foreach ($facet['options'] as $option)
                                <label class="flex min-h-9 items-center gap-3 rounded-lg px-2 text-sm hover:bg-sunken">
                                    <input type="checkbox" name="f[{{ $key }}][]" value="{{ $option->value }}" class="size-4" @checked(in_array($option->value, $chosen, true))>
                                    <span class="flex-1">{{ $option->value }}</span><span class="text-xs text-muted tabular-nums">{{ $option->total }}</span>
                                </label>
                            @endforeach
                        </div>
                    @endif
                </fieldset>
                <div class="mt-1 flex items-center justify-between gap-2 border-t border-line p-2">
                    <button type="button" class="link text-sm" @click="$el.closest('[x-data]').querySelectorAll('input[type=checkbox]').forEach((i) => i.checked = false); $refs.bar.requestSubmit()">Clear</button>
                    <button class="btn btn-primary btn-sm">Apply</button>
                </div>
            </div>
        </div>
    @endforeach

    {{-- Rating --}}
    <div class="relative" x-data="{ open: false }" @click.outside="open = false" @keydown.escape="open = false">
        <button type="button" class="chip {{ request('rating') ? 'chip-active' : '' }}" @click="open = !open" :aria-expanded="open.toString()">
            Rating <x-lucide-chevron-down class="size-4 transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" />
        </button>
        <div x-show="open" x-cloak x-transition.origin.top.left class="absolute top-full left-0 z-30 mt-2 w-56 rounded-[1.25rem] border border-line bg-surface p-2 shadow-e3">
            @foreach ([4 => '4 stars & up', 3 => '3 stars & up', '' => 'Any rating'] as $stars => $label)
                <label class="flex min-h-9 items-center gap-3 rounded-lg px-2 text-sm hover:bg-sunken">
                    <input type="radio" name="rating" value="{{ $stars }}" class="size-4" @checked((string) request('rating') === (string) $stars) onchange="this.form.requestSubmit()">
                    @if ($stars)<x-rating :value="$stars" />@endif {{ $stars ? '& up' : $label }}
                </label>
            @endforeach
        </div>
    </div>

    {{-- Instant toggles --}}
    @foreach ($toggles as $flag => $label)
        <label class="chip has-checked:border-primary has-checked:bg-primary-soft has-checked:text-primary">
            <input type="checkbox" name="{{ $flag }}" value="1" class="sr-only" @checked(request()->boolean($flag)) onchange="this.form.requestSubmit()">{{ $label }}
        </label>
    @endforeach
@else
    <div class="divide-y divide-line">
        <fieldset class="py-4">
            <legend class="text-sm font-semibold">Show only</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ($toggles as $flag => $label)
                    <label class="chip has-checked:border-primary has-checked:bg-primary-soft has-checked:text-primary">
                        <input type="checkbox" name="{{ $flag }}" value="1" class="sr-only" @checked(request()->boolean($flag))>{{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>

        @if ($priceRange[1] > $priceRange[0])
            <fieldset class="py-4">
                <legend class="text-sm font-semibold">Price</legend>
                <div class="mt-3">@include('partials.price-range', ['idPrefix' => $idPrefix])</div>
            </fieldset>
        @endif

        @foreach ($facets as $key => $facet)
            @php $chosen = $selected[$key] ?? []; $many = $facet['options']->count() > 8 && ! in_array($key, ['colour', 'size'], true); @endphp
            <fieldset class="py-4" x-data="{ open: {{ $loop->index < 2 || $chosen ? 'true' : 'false' }}, all: {{ $chosen ? 'true' : 'false' }} }">
                <legend class="w-full">
                    <button type="button" class="flex min-h-9 w-full items-center justify-between text-sm font-semibold" @click="open = !open" :aria-expanded="open.toString()">
                        <span>{{ $facet['label'] }} @if ($chosen)<span class="ml-1 text-primary">({{ count($chosen) }})</span>@endif</span>
                        <x-lucide-chevron-down class="size-4 text-muted transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" />
                    </button>
                </legend>
                <div x-show="open" x-collapse>
                    @if ($key === 'colour')
                        <div class="mt-2 flex flex-wrap gap-2.5">
                            @foreach ($facet['options'] as $option)
                                <label class="relative" title="{{ $option->value }}">
                                    <input type="checkbox" name="f[colour][]" value="{{ $option->value }}" class="peer sr-only" @checked(in_array($option->value, $chosen, true))>
                                    <span class="block size-10 rounded-full border border-line-strong ring-offset-2 transition peer-checked:ring-2 peer-checked:ring-primary" style="background: {{ $swatches[$option->value] ?? '#e5e7eb' }}"></span>
                                    <span class="sr-only">{{ $option->value }} ({{ $option->total }})</span>
                                </label>
                            @endforeach
                        </div>
                    @elseif ($key === 'size')
                        <div class="mt-2 flex flex-wrap gap-2">
                            @foreach ($facet['options'] as $option)
                                <label>
                                    <input type="checkbox" name="f[size][]" value="{{ $option->value }}" class="peer sr-only" @checked(in_array($option->value, $chosen, true))>
                                    <span class="grid h-11 min-w-12 place-items-center rounded-control border border-line-strong px-3 text-sm font-medium peer-checked:border-primary peer-checked:bg-primary peer-checked:text-white">{{ $option->value }}</span>
                                </label>
                            @endforeach
                        </div>
                    @else
                        <div class="mt-1">
                            @foreach ($facet['options'] as $option)
                                <label class="flex min-h-11 items-center gap-3 text-[0.9375rem] text-ink-2" @if ($many && $loop->index >= 8) x-show="all" @endif>
                                    <input type="checkbox" name="f[{{ $key }}][]" value="{{ $option->value }}" class="size-5" @checked(in_array($option->value, $chosen, true))>
                                    <span class="flex-1">{{ $option->value }}</span><span class="text-xs text-muted tabular-nums">{{ $option->total }}</span>
                                </label>
                            @endforeach
                            @if ($many)
                                <button type="button" class="link mt-1 text-sm" @click="all = !all" x-text="all ? 'Show less' : 'Show all {{ $facet['options']->count() }}'"></button>
                            @endif
                        </div>
                    @endif
                </div>
            </fieldset>
        @endforeach

        <fieldset class="py-4">
            <legend class="text-sm font-semibold">Customer rating</legend>
            <div class="mt-3 flex flex-wrap gap-2">
                @foreach ([4 => '4★ & up', 3 => '3★ & up', '' => 'Any'] as $stars => $label)
                    <label class="chip has-checked:border-primary has-checked:bg-primary-soft has-checked:text-primary">
                        <input type="radio" name="rating" value="{{ $stars }}" class="sr-only" @checked((string) request('rating') === (string) $stars)>{{ $label }}
                    </label>
                @endforeach
            </div>
        </fieldset>
    </div>
@endif
