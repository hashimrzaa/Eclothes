@extends('layouts.admin')

@section('title', 'Categories')

@section('content')
    <p class="max-w-3xl text-sm text-muted">The store launches with a small set of clear departments. Hide a department or sub-category to remove it from the navigation until its suppliers are ready — nothing is deleted, and it can be switched back on at any time.</p>

    <div class="mt-5 space-y-4">
        @foreach ($departments as $dept)
            <section class="card overflow-hidden" x-data="{ open: false }">
                <div class="flex flex-wrap items-center gap-x-4 gap-y-2 p-4 sm:px-5">
                    <button type="button" class="flex min-h-11 flex-1 items-center gap-3 text-left" @click="open = !open" :aria-expanded="open.toString()">
                        <x-lucide-chevron-right class="size-5 shrink-0 text-muted transition-transform" ::class="open && 'rotate-90'" aria-hidden="true" />
                        <span class="grid size-9 shrink-0 place-items-center rounded-full bg-primary-soft text-primary">@svg('lucide-'.($dept->icon ?: 'tag'), 'size-[18px]', ['aria-hidden' => 'true'])</span>
                        <span><span class="font-semibold">{{ $dept->name }}</span>
                            <span class="block text-xs text-muted">{{ $dept->children->count() }} groups · {{ $dept->children->sum(fn ($g) => $g->children->count()) }} sub-categories @if ($dept->kind === 'collection')· automatic collections @endif</span></span>
                    </button>
                    <span class="badge badge-neutral">Phase {{ $dept->phase }}</span>
                    <form method="post" action="{{ route('admin.categories.update', $dept) }}" class="flex items-center gap-2">
                        @csrf @method('patch')
                        <label for="priority-{{ $dept->id }}" class="sr-only">Launch priority</label>
                        <select id="priority-{{ $dept->id }}" name="priority" class="field field-sm w-auto" onchange="this.form.requestSubmit()">
                            @foreach (['High', 'Medium', 'Low'] as $priority)<option @selected($dept->priority === $priority)>{{ $priority }}</option>@endforeach
                        </select>
                    </form>
                    @include('admin.partials.visibility', ['category' => $dept])
                </div>

                <div x-show="open" x-collapse x-cloak class="border-t border-line bg-canvas">
                    @foreach ($dept->children as $group)
                        <div class="border-b border-line px-4 py-4 last:border-b-0 sm:px-5">
                            <div class="flex flex-wrap items-center justify-between gap-3">
                                <p class="flex items-center gap-2 text-sm font-semibold">{{ $group->name }}
                                    @if ($group->is_restricted)<span class="badge badge-neutral"><x-lucide-lock class="size-3" aria-hidden="true" />18+ age gate</span>@endif
                                    @if ($group->kind === 'filter')<span class="badge badge-info">Saved filters</span>@endif
                                </p>
                                @include('admin.partials.visibility', ['category' => $group])
                            </div>
                            <ul class="mt-3 flex flex-wrap gap-2">
                                @foreach ($group->children as $leaf)
                                    <li class="inline-flex items-center gap-2 rounded-full border border-line bg-surface py-1 pr-1 pl-3 text-[13px] {{ $leaf->is_visible ? '' : 'opacity-50' }}">
                                        {{ $leaf->name }}
                                        @if ($leaf->kind === 'normal')<span class="text-muted tabular-nums">{{ $counts[$leaf->id] ?? 0 }}</span>@endif
                                        <form method="post" action="{{ route('admin.categories.update', $leaf) }}">
                                            @csrf @method('patch')
                                            <input type="hidden" name="is_visible" value="{{ $leaf->is_visible ? 0 : 1 }}">
                                            <button class="grid size-7 place-items-center rounded-full text-muted hover:bg-sunken hover:text-ink" title="{{ $leaf->is_visible ? 'Hide' : 'Show' }} {{ $leaf->name }}" aria-label="{{ $leaf->is_visible ? 'Hide' : 'Show' }} {{ $leaf->name }}">
                                                @if ($leaf->is_visible)<x-lucide-eye class="size-3.5" aria-hidden="true" />@else<x-lucide-eye-off class="size-3.5" aria-hidden="true" />@endif
                                            </button>
                                        </form>
                                    </li>
                                @endforeach
                            </ul>
                        </div>
                    @endforeach
                </div>
            </section>
        @endforeach
    </div>
@endsection
