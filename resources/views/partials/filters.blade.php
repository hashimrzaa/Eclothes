{{-- Dynamic filters: only the facets relevant to this department are passed in (spec §10). --}}
@if ($q !== null && $q !== '')
    <input type="hidden" name="q" value="{{ $q }}">
@endif
@if (request('sort'))
    <input type="hidden" name="sort" value="{{ request('sort') }}">
@endif

<div class="divide-y divide-line border-y border-line lg:border-t-0">
    <fieldset class="py-4">
        <legend class="text-sm font-semibold">Availability</legend>
        <div class="mt-3 space-y-1">
            <label class="flex min-h-9 items-center gap-3 text-sm text-ink-2"><input type="checkbox" name="in_stock" value="1" class="size-4" @checked(request()->boolean('in_stock'))>In stock only</label>
            <label class="flex min-h-9 items-center gap-3 text-sm text-ink-2"><input type="checkbox" name="on_sale" value="1" class="size-4" @checked(request()->boolean('on_sale'))>On sale</label>
            @if ($showFashionToggles)
                <label class="flex min-h-9 items-center gap-3 text-sm text-ink-2"><input type="checkbox" name="new" value="1" class="size-4" @checked(request()->boolean('new'))>New arrivals</label>
            @endif
        </div>
    </fieldset>

    <fieldset class="py-4">
        <legend class="text-sm font-semibold">Price (Rs.)</legend>
        <div class="mt-3 flex items-center gap-2" @change.stop>
            <label class="sr-only" for="{{ $idPrefix }}-min">Minimum price</label>
            <input id="{{ $idPrefix }}-min" type="number" name="min" inputmode="numeric" min="0" value="{{ request('min') }}" placeholder="{{ $priceRange[0] }}" class="field field-sm">
            <span class="text-muted" aria-hidden="true">–</span>
            <label class="sr-only" for="{{ $idPrefix }}-max">Maximum price</label>
            <input id="{{ $idPrefix }}-max" type="number" name="max" inputmode="numeric" min="0" value="{{ request('max') }}" placeholder="{{ $priceRange[1] }}" class="field field-sm">
            <button class="btn btn-outline btn-sm shrink-0 px-3" aria-label="Apply price range">Go</button>
        </div>
    </fieldset>

    @foreach ($facets as $key => $facet)
        @php $chosen = $selected[$key] ?? []; $many = $facet['options']->count() > 7; @endphp
        <fieldset class="py-4" x-data="{ open: {{ $loop->index < 3 || $chosen ? 'true' : 'false' }}, all: {{ $chosen ? 'true' : 'false' }} }">
            <legend class="w-full">
                <button type="button" class="flex w-full items-center justify-between text-sm font-semibold" @click="open = !open" :aria-expanded="open.toString()">
                    <span>{{ $facet['label'] }} @if ($chosen)<span class="ml-1 text-primary">({{ count($chosen) }})</span>@endif</span>
                    <x-lucide-chevron-down class="size-4 text-muted transition-transform" ::class="open && 'rotate-180'" aria-hidden="true" />
                </button>
            </legend>
            <div x-show="open" x-collapse>
                @if ($key === 'colour')
                    <div class="mt-3 flex flex-wrap gap-2">
                        @foreach ($facet['options'] as $option)
                            <label class="relative" title="{{ $option->value }} ({{ $option->total }})">
                                <input type="checkbox" name="f[colour][]" value="{{ $option->value }}" class="peer sr-only" @checked(in_array($option->value, $chosen, true))>
                                <span class="block size-8 rounded-full border border-line-strong ring-offset-2 transition peer-checked:ring-2 peer-checked:ring-primary peer-focus-visible:ring-2 peer-focus-visible:ring-ink"
                                    style="background: {{ config('store.swatches')[$option->value] ?? '#e5e7eb' }}"></span>
                                <span class="sr-only">{{ $option->value }} ({{ $option->total }})</span>
                            </label>
                        @endforeach
                    </div>
                @else
                    <div class="mt-3 space-y-1">
                        @foreach ($facet['options'] as $option)
                            <label class="flex min-h-9 items-center gap-3 text-sm text-ink-2" @if ($many && $loop->index >= 7) x-show="all" @endif>
                                <input type="checkbox" name="f[{{ $key }}][]" value="{{ $option->value }}" class="size-4" @checked(in_array($option->value, $chosen, true))>
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
        <div class="mt-3 space-y-1">
            @foreach ([4, 3] as $stars)
                <label class="flex min-h-9 items-center gap-3 text-sm text-ink-2">
                    <input type="radio" name="rating" value="{{ $stars }}" class="size-4" @checked(request()->integer('rating') === $stars)>
                    <x-rating :value="$stars" /> <span>&amp; up</span>
                </label>
            @endforeach
            @if (request('rating'))
                <label class="flex min-h-9 items-center gap-3 text-sm text-ink-2"><input type="radio" name="rating" value="" class="size-4">Any rating</label>
            @endif
        </div>
    </fieldset>
</div>
