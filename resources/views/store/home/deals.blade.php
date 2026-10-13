{{-- Shortcuts for deal seekers --}}
<section class="container-page mt-12 lg:mt-16" aria-label="Deals">
    <ul class="grid grid-cols-2 gap-3 lg:grid-cols-4 lg:grid-rows-2 lg:gap-4" data-reveal-group>
        <li class="col-span-2 lg:row-span-2">
            <a href="{{ route('category', 'deals-trending/deals/under-rs-499') }}" data-tilt class="tilt-glare group relative flex h-full min-h-52 flex-col justify-between overflow-hidden rounded-[1.75rem] bg-night p-6 text-white sm:p-8 lg:min-h-64">
                <span class="pointer-events-none absolute -top-16 -right-16 size-72 rounded-full bg-accent/50 blur-3xl" data-parallax="18" aria-hidden="true"></span>
                <span class="eyebrow relative text-lime">Budget finds</span>
                <span class="relative">
                    <span class="block text-sm text-white/60">Everything</span>
                    <span class="display block text-5xl sm:text-7xl">Under<br>Rs. 499</span>
                    <span class="mt-5 inline-flex items-center gap-2 text-sm font-semibold">Shop the edit <span class="grid size-9 place-items-center rounded-full bg-lime text-night transition-transform duration-300 group-hover:-rotate-45"><x-lucide-arrow-right class="size-4" aria-hidden="true" /></span></span>
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
                <a href="{{ route('category', $path) }}" data-tilt class="tilt-glare group relative flex h-full min-h-28 flex-col justify-between overflow-hidden rounded-3xl p-4 sm:p-6 lg:min-h-0 lg:rounded-[1.75rem] {{ $tone }}">
                    <span class="flex items-start justify-between">
                        <span class="text-[0.8125rem] font-semibold opacity-70">{{ $kicker }}</span>
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
