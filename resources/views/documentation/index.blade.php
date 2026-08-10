@extends('layouts.dashboard')

@php
    function documentationLinkify(string $text): string
    {
        return preg_replace_callback(
            '/(?P<path>\/[A-Za-z0-9_\-\/]+)/',
            static function (array $matches): string {
                $path = htmlspecialchars($matches['path'], ENT_QUOTES, 'UTF-8');

                return sprintf(
                    '<a href="%s" class="inline-flex items-center gap-1 font-semibold text-indigo-700 underline decoration-indigo-300 decoration-2 underline-offset-4 transition hover:text-indigo-600 dark:text-indigo-300 dark:hover:text-indigo-200">%s</a>',
                    $path,
                    $path
                );
            },
            $text
        ) ?? $text;
    }
@endphp

@section('title', $type === 'merchant' ? __('documentation.title_merchant') : __('documentation.title_user'))
@section('page-title', $type === 'merchant' ? __('documentation.title_merchant') : __('documentation.title_user'))
@section('page-subtitle', $catalog['description'])

@section('content')
<div class="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8 lg:py-8">
    <div class="overflow-hidden rounded-3xl border border-slate-200 bg-white shadow-sm dark:border-slate-700 dark:bg-slate-900">
        <div class="flex flex-col lg:grid lg:grid-cols-[280px_minmax(0,1fr)]">
            <aside class="border-b border-slate-200 bg-slate-50/80 p-4 dark:border-slate-700 dark:bg-slate-800/70 lg:border-b-0 lg:border-e">
                <div class="flex items-center justify-between lg:hidden">
                    <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ __('documentation.sidebar_title') }}</h3>
                    <button type="button" class="rounded-full border border-slate-200 px-3 py-1 text-sm font-medium text-slate-600 dark:border-slate-600 dark:text-slate-300" x-on:click="sidebarOpen = false">
                        {{ __('documentation.close_menu') }}
                    </button>
                </div>

                <div class="mt-4 space-y-4">
                    <div class="rounded-2xl border border-slate-200 bg-white p-3 shadow-sm dark:border-slate-700 dark:bg-slate-900">
                        <label for="documentation-search" class="mb-2 block text-sm font-medium text-slate-700 dark:text-slate-200">{{ __('documentation.search_placeholder') }}</label>
                        <input id="documentation-search" type="search" data-doc-search-input class="w-full rounded-xl border border-slate-200 bg-slate-50 px-3 py-2 text-sm text-slate-700 outline-none ring-0 transition focus:border-indigo-500 focus:bg-white dark:border-slate-700 dark:bg-slate-800 dark:text-slate-100" placeholder="{{ __('documentation.search_placeholder') }}">
                        <p class="mt-2 text-xs text-slate-500 dark:text-slate-400" data-doc-results-count>{{ __('documentation.search_results', ['count' => count($allArticles)]) }}</p>
                    </div>

                    <div class="space-y-2" data-doc-category-list>
                        @foreach($catalog['categories'] as $category)
                            <div class="rounded-2xl border border-slate-200 bg-white/80 p-2 shadow-sm dark:border-slate-700 dark:bg-slate-800/80" data-doc-category-section x-data="{ open: true }">
                                <button type="button" class="flex w-full items-center justify-between rounded-xl px-3 py-3 text-sm font-semibold text-slate-700 transition hover:bg-slate-50 dark:text-slate-200 dark:hover:bg-slate-700" x-on:click="open = !open" :aria-expanded="open">
                                    <span>{{ $category['title'] }}</span>
                                    <i class="fas fa-chevron-down text-xs transition" :class="open ? 'rotate-180' : ''"></i>
                                </button>
                                <div x-show="open" x-collapse class="pt-2">
                                    <div class="space-y-1">
                                        @php $categoryRoute = route('documentation.category', ['type' => $type, 'category' => $category['slug']]); @endphp
                                        <a href="{{ $categoryRoute }}" class="flex items-start justify-between rounded-xl px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-indigo-300" data-doc-search-item data-doc-search-text="{{ \Illuminate\Support\Str::lower($category['title'] . ' ' . $category['description'] . ' ' . implode(' ', $category['topics'])) }}">
                                            <span>{{ __('documentation.overview') }}</span>
                                            <i class="fas fa-book-open text-xs"></i>
                                        </a>
                                        @foreach($category['articles'] as $article)
                                            @php
                                                $articleSearchText = \Illuminate\Support\Str::lower(
                                                    $article['title'] . ' ' . $article['summary'] . ' ' . implode(' ', $category['topics'])
                                                );

                                                foreach ($article['content'] as $block) {
                                                    if (($block['type'] ?? null) === 'paragraph' || ($block['type'] ?? null) === 'heading') {
                                                        $articleSearchText .= ' ' . ($block['text'] ?? '');
                                                    }

                                                    if (($block['type'] ?? null) === 'list') {
                                                        $articleSearchText .= ' ' . ($block['title'] ?? '') . ' ' . implode(' ', $block['items'] ?? []);
                                                    }

                                                    if (($block['type'] ?? null) === 'steps') {
                                                        $articleSearchText .= ' ' . ($block['title'] ?? '') . ' ' . implode(' ', $block['items'] ?? []);
                                                    }

                                                    if (($block['type'] ?? null) === 'code') {
                                                        $articleSearchText .= ' ' . ($block['label'] ?? '') . ' ' . ($block['value'] ?? '');
                                                    }

                                                    if (($block['type'] ?? null) === 'callout') {
                                                        $articleSearchText .= ' ' . ($block['title'] ?? '') . ' ' . ($block['text'] ?? '');
                                                    }
                                                }

                                                $articleRoute = route('documentation.article', ['type' => $type, 'category' => $category['slug'], 'article' => $article['slug']]);
                                            @endphp
                                            <a href="{{ $articleRoute }}" class="flex items-start justify-between rounded-xl px-3 py-2 text-sm text-slate-600 transition hover:bg-slate-50 hover:text-indigo-700 dark:text-slate-300 dark:hover:bg-slate-700 dark:hover:text-indigo-300" data-doc-search-item data-doc-search-text="{{ $articleSearchText }}">
                                                <span>{{ $article['title'] }}</span>
                                                <i class="fas fa-file-lines text-xs"></i>
                                            </a>
                                        @endforeach
                                    </div>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </aside>

            <div class="min-w-0 p-4 sm:p-6 lg:p-8">
                <div class="mb-6 flex flex-wrap items-center gap-2 text-sm text-slate-500 dark:text-slate-400">
                    @foreach($breadcrumbs as $index => $crumb)
                        @if($loop->last)
                            <span class="font-semibold text-slate-700 dark:text-slate-200">{{ $crumb['label'] }}</span>
                        @else
                            <a href="{{ $crumb['url'] ?? '#' }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">{{ $crumb['label'] }}</a>
                            <span>/</span>
                        @endif
                    @endforeach
                </div>

                @if($currentArticle)
                    <div class="rounded-3xl border border-indigo-100 bg-gradient-to-br from-indigo-50 to-white p-6 shadow-sm dark:border-indigo-500/20 dark:from-slate-800 dark:to-slate-900">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="rounded-full bg-indigo-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-indigo-700 dark:bg-indigo-500/20 dark:text-indigo-300">{{ $currentCategory['title'] ?? '' }}</span>
                            <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-semibold uppercase tracking-[0.2em] text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $type === 'merchant' ? __('documentation.merchant_variant') : __('documentation.user_variant') }}</span>
                        </div>
                        <h2 class="mt-4 text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $currentArticle['title'] }}</h2>
                        <p class="mt-3 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $currentArticle['summary'] }}</p>
                    </div>

                    <div class="mt-8 grid gap-8 xl:grid-cols-[minmax(0,1fr)_260px]">
                        <article class="space-y-6">
                            @foreach($currentArticle['content'] as $block)
                                @switch($block['type'])
                                    @case('paragraph')
                                        <p class="text-base leading-8 text-slate-700 dark:text-slate-300">{!! documentationLinkify($block['text']) !!}</p>
                                        @break
                                    @case('heading')
                                        @php $anchor = \Illuminate\Support\Str::slug($block['text']); @endphp
                                        <h3 id="{{ $anchor }}" class="mt-4 text-xl font-semibold text-slate-900 dark:text-slate-100">{{ $block['text'] }}</h3>
                                        @break
                                    @case('list')
                                        <div class="rounded-2xl border border-slate-200 bg-slate-50 p-5 dark:border-slate-700 dark:bg-slate-800/80">
                                            <h3 class="mb-3 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $block['title'] }}</h3>
                                            <ul class="space-y-2 text-sm leading-7 text-slate-700 dark:text-slate-300">
                                                @foreach($block['items'] as $item)
                                                    <li class="flex items-start gap-2"><span class="mt-2 h-2.5 w-2.5 rounded-full bg-indigo-500"></span><span>{!! documentationLinkify($item) !!}</span></li>
                                                @endforeach
                                            </ul>
                                        </div>
                                        @break
                                    @case('steps')
                                        <div class="rounded-2xl border border-amber-200 bg-amber-50 p-5 dark:border-amber-500/20 dark:bg-amber-500/10">
                                            <h3 class="mb-3 text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $block['title'] }}</h3>
                                            <ol class="space-y-2 text-sm leading-7 text-slate-700 dark:text-slate-300">
                                                @foreach($block['items'] as $index => $item)
                                                    <li class="flex items-start gap-2"><span class="flex h-7 w-7 items-center justify-center rounded-full bg-amber-500 text-xs font-semibold text-white">{{ $index + 1 }}</span><span>{!! documentationLinkify($item) !!}</span></li>
                                                @endforeach
                                            </ol>
                                        </div>
                                        @break
                                    @case('code')
                                        <div class="rounded-2xl border border-slate-200 bg-slate-950 p-4 text-sm text-slate-100 dark:border-slate-700">
                                            <div class="mb-2 text-xs uppercase tracking-[0.25em] text-slate-400">{{ $block['label'] }}</div>
                                            <code class="break-all font-mono">{{ $block['value'] }}</code>
                                        </div>
                                        @break
                                    @case('callout')
                                        <div class="rounded-2xl border p-5 @if($block['variant'] === 'warning') border-amber-200 bg-amber-50 text-amber-900 dark:border-amber-500/20 dark:bg-amber-500/10 dark:text-amber-200 @elseif($block['variant'] === 'important') border-rose-200 bg-rose-50 text-rose-900 dark:border-rose-500/20 dark:bg-rose-500/10 dark:text-rose-200 @elseif($block['variant'] === 'note') border-sky-200 bg-sky-50 text-sky-900 dark:border-sky-500/20 dark:bg-sky-500/10 dark:text-sky-200 @else border-emerald-200 bg-emerald-50 text-emerald-900 dark:border-emerald-500/20 dark:bg-emerald-500/10 dark:text-emerald-200 @endif">
                                            <div class="mb-2 text-sm font-semibold uppercase tracking-[0.2em]">{{ $block['title'] }}</div>
                                            <p class="text-sm leading-7">{!! documentationLinkify($block['text']) !!}</p>
                                        </div>
                                        @break
                                @endswitch
                            @endforeach
                        </article>

                        <aside class="space-y-4">
                            <div class="rounded-2xl border border-slate-200 bg-slate-50 p-4 dark:border-slate-700 dark:bg-slate-800/80">
                                <h3 class="text-sm font-semibold uppercase tracking-[0.2em] text-slate-500 dark:text-slate-400">{{ __('documentation.table_of_contents') }}</h3>
                                <ul class="mt-4 space-y-2 text-sm text-slate-600 dark:text-slate-300">
                                    @foreach($currentArticle['content'] as $block)
                                        @if($block['type'] === 'heading')
                                            @php $anchor = \Illuminate\Support\Str::slug($block['text']); @endphp
                                            <li><a href="#{{ $anchor }}" class="transition hover:text-indigo-600 dark:hover:text-indigo-400">{{ $block['text'] }}</a></li>
                                        @endif
                                    @endforeach
                                </ul>
                            </div>
                        </aside>
                    </div>

                    <div class="mt-8 flex flex-col gap-3 border-t border-slate-200 pt-6 sm:flex-row sm:items-center sm:justify-between dark:border-slate-700">
                        @if($prevArticle)
                            <a href="{{ route('documentation.article', ['type' => $prevArticle['type'], 'category' => $prevArticle['category'], 'article' => $prevArticle['article']]) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-500 hover:text-indigo-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-indigo-400 dark:hover:text-indigo-300">
                                <i class="fas fa-arrow-right"></i>
                                <span>{{ __('documentation.previous_article') }}: {{ $prevArticle['title'] }}</span>
                            </a>
                        @else
                            <span></span>
                        @endif
                        @if($nextArticle)
                            <a href="{{ route('documentation.article', ['type' => $nextArticle['type'], 'category' => $nextArticle['category'], 'article' => $nextArticle['article']]) }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-500 hover:text-indigo-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-indigo-400 dark:hover:text-indigo-300">
                                <span>{{ __('documentation.next_article') }}: {{ $nextArticle['title'] }}</span>
                                <i class="fas fa-arrow-left"></i>
                            </a>
                        @endif
                    </div>
                @else
                    <div class="rounded-3xl border border-slate-200 bg-slate-50/70 p-6 dark:border-slate-700 dark:bg-slate-800/60">
                        <div class="flex flex-wrap items-center justify-between gap-4">
                            <div>
                                <h2 class="text-2xl font-semibold text-slate-900 dark:text-slate-100">{{ $currentCategory ? $currentCategory['title'] : $catalog['title'] }}</h2>
                                <p class="mt-2 max-w-3xl text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $currentCategory ? $currentCategory['description'] : $catalog['description'] }}</p>
                            </div>
                            @if($currentCategory)
                                <a href="{{ route('documentation.type', ['type' => $type]) }}" class="rounded-full border border-slate-200 px-4 py-2 text-sm font-medium text-slate-700 transition hover:border-indigo-500 hover:text-indigo-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-indigo-400 dark:hover:text-indigo-300">
                                    {{ __('documentation.back_to_overview') }}
                                </a>
                            @endif
                        </div>

                        <div class="mt-8 grid gap-4 md:grid-cols-2">
                            @if($currentCategory)
                                @foreach($currentCategory['articles'] as $article)
                                    @php
                                        $articleRoute = route('documentation.article', ['type' => $type, 'category' => $currentCategory['slug'], 'article' => $article['slug']]);
                                    @endphp
                                    <div class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-indigo-400 dark:border-slate-700 dark:bg-slate-900">
                                        <div class="mb-3 flex items-center justify-between gap-2">
                                            <div class="flex items-center gap-2 text-indigo-600 dark:text-indigo-400">
                                                <i class="fas fa-bookmark"></i>
                                                <span class="text-sm font-semibold">{{ __('documentation.topic') }}</span>
                                            </div>
                                            <a href="{{ $articleRoute }}" class="text-xs font-semibold uppercase tracking-[0.2em] text-indigo-600 transition hover:text-indigo-500 dark:text-indigo-300 dark:hover:text-indigo-200">
                                                {{ __('documentation.read_article') }}
                                            </a>
                                        </div>
                                        <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $article['title'] }}</h3>
                                        <p class="mt-2 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $article['summary'] }}</p>
                                        <div class="mt-4 flex items-center justify-between">
                                            <div class="flex flex-wrap gap-2">
                                                @foreach($currentCategory['topics'] as $topic)
                                                    <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $topic }}</span>
                                                @endforeach
                                            </div>
                                            <a href="{{ $articleRoute }}" class="inline-flex items-center gap-2 rounded-full border border-slate-200 px-3 py-1 text-xs font-semibold text-slate-700 transition hover:border-indigo-500 hover:text-indigo-700 dark:border-slate-700 dark:text-slate-300 dark:hover:border-indigo-400 dark:hover:text-indigo-300">
                                                <i class="fas fa-arrow-left"></i>
                                                <span>{{ __('documentation.read_article') }}</span>
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            @else
                                @foreach($catalog['categories'] as $category)
                                    <a href="{{ route('documentation.category', ['type' => $type, 'category' => $category['slug']]) }}" class="rounded-2xl border border-slate-200 bg-white p-5 shadow-sm transition hover:-translate-y-1 hover:border-indigo-400 dark:border-slate-700 dark:bg-slate-900">
                                        <div class="flex items-center justify-between">
                                            <h3 class="text-lg font-semibold text-slate-900 dark:text-slate-100">{{ $category['title'] }}</h3>
                                            <i class="fas fa-chevron-left text-sm text-indigo-500"></i>
                                        </div>
                                        <p class="mt-3 text-sm leading-7 text-slate-600 dark:text-slate-300">{{ $category['description'] }}</p>
                                        <div class="mt-4 flex flex-wrap gap-2">
                                            @foreach($category['topics'] as $topic)
                                                <span class="rounded-full bg-slate-100 px-3 py-1 text-xs font-medium text-slate-600 dark:bg-slate-800 dark:text-slate-300">{{ $topic }}</span>
                                            @endforeach
                                        </div>
                                    </a>
                                @endforeach
                            @endif
                        </div>
                    </div>
                @endif
            </div>
        </div>
    </div>
</div>

<script>
    document.addEventListener('DOMContentLoaded', function () {
        const searchInput = document.querySelector('[data-doc-search-input]');
        const resultCount = document.querySelector('[data-doc-results-count]');
        const items = Array.from(document.querySelectorAll('[data-doc-search-item]'));
        const sections = Array.from(document.querySelectorAll('[data-doc-category-section]'));

        if (!searchInput) {
            return;
        }

        const updateResults = () => {
            const query = (searchInput.value || '').trim().toLowerCase();
            let visibleCount = 0;

            items.forEach((item) => {
                const text = (item.getAttribute('data-doc-search-text') || '').toLowerCase();
                const match = query === '' || text.includes(query);
                item.style.display = match ? '' : 'none';
                if (match) {
                    visibleCount += 1;
                }
            });

            sections.forEach((section) => {
                const visibleChildren = section.querySelectorAll('[data-doc-search-item]').length > 0 ? Array.from(section.querySelectorAll('[data-doc-search-item]')).filter((item) => item.style.display !== 'none') : [];
                section.style.display = visibleChildren.length > 0 || query === '' ? '' : 'none';
            });

            if (resultCount) {
                resultCount.textContent = query === ''
                    ? '{{ __('documentation.search_results', ['count' => count($allArticles)]) }}'
                    : '{{ __('documentation.search_results_filtered') }}' + ' ' + visibleCount;
            }
        };

        searchInput.addEventListener('input', updateResults);
        updateResults();
    });
</script>
@endsection
