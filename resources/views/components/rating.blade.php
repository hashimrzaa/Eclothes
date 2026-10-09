@props(['value' => 0, 'count' => null, 'size' => 'size-3.5'])
@if ($count === null || $count > 0)
    <span {{ $attributes->class('inline-flex items-center gap-1.5 text-[13px] text-muted') }}>
        <span class="relative inline-flex" role="img" aria-label="Rated {{ number_format($value, 1) }} out of 5">
            <span class="flex text-line-strong">
                @for ($i = 0; $i < 5; $i++)
                    <x-lucide-star class="{{ $size }} fill-current" aria-hidden="true" />
                @endfor
            </span>
            <span class="absolute inset-0 flex overflow-hidden text-amber-500" style="width: {{ $value / 5 * 100 }}%">
                @for ($i = 0; $i < 5; $i++)
                    <x-lucide-star class="{{ $size }} shrink-0 fill-current" aria-hidden="true" />
                @endfor
            </span>
        </span>
        @if ($count !== null)
            <span>{{ number_format($value, 1) }} ({{ $count }})</span>
        @endif
    </span>
@endif
