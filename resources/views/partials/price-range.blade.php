{{-- Two-thumb price slider. The values are only sent when the shopper actually narrows the range. --}}
<div x-data="priceRange({{ $priceRange[0] }}, {{ $priceRange[1] }}, {{ request('min') ? (int) request('min') : 'null' }}, {{ request('max') ? (int) request('max') : 'null' }})">
    <p class="flex items-baseline justify-between text-sm">
        <span class="font-semibold tabular-nums" x-text="'Rs. ' + Number(lo).toLocaleString('en-PK')">{{ money(request('min') ?: $priceRange[0]) }}</span>
        <span class="text-muted">to</span>
        <span class="font-semibold tabular-nums" x-text="'Rs. ' + Number(hi).toLocaleString('en-PK')">{{ money(request('max') ?: $priceRange[1]) }}</span>
    </p>
    <div class="range-dual mt-4">
        <div class="range-dual-fill" :style="`left: ${left}%; right: ${right}%`"></div>
        <label class="sr-only" for="{{ $idPrefix }}-lo">Minimum price</label>
        <input id="{{ $idPrefix }}-lo" type="range" :min="floor" :max="ceil" step="10" x-model="lo" @input="clamp('lo')">
        <label class="sr-only" for="{{ $idPrefix }}-hi">Maximum price</label>
        <input id="{{ $idPrefix }}-hi" type="range" :min="floor" :max="ceil" step="10" x-model="hi" @input="clamp('hi')">
    </div>
    <input type="hidden" name="min" :value="lo" :disabled="lo <= floor">
    <input type="hidden" name="max" :value="hi" :disabled="hi >= ceil">
    <div class="mt-4 flex flex-wrap gap-2">
        @foreach ([499, 999, 1999] as $cap)
            @if ($cap > $priceRange[0] && $cap < $priceRange[1])
                <button type="button" class="chip h-8 px-3 text-[0.8125rem]" @click="lo = floor; hi = {{ $cap }}">Under Rs. {{ number_format($cap) }}</button>
            @endif
        @endforeach
    </div>
</div>
