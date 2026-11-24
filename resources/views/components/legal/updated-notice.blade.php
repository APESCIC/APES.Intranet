@props(['notices' => []])
@if ($notices !== [])
    <h2 class="sr-only">Legal page updates</h2>
    @foreach ($notices as $notice)
        @php
            $page = $notice['page'];
            $version = $notice['version'];
            $routeName = $page->showRouteName();
        @endphp
        <div
            class="mb-4 rounded-sg-field border border-sg-line bg-sg-warn-mist p-4 text-sm"
            role="status"
            data-legal-updated-notice="{{ $page->slug }}"
        >
            <p class="font-medium text-sg-warn">
                {{ $page->title }} has been updated (version {{ $version->number }})
            </p>
            @if ($version->change_summary)
                <p class="mt-1">{{ $version->change_summary }}</p>
            @endif
            <div class="mt-3 flex flex-wrap items-center gap-3">
                <a class="underline" href="{{ route($routeName) }}">Read the {{ strtolower($page->title) }}</a>
                <form method="post" action="{{ route('legal.notice.dismiss', $page) }}">
                    @csrf
                    <button type="submit" class="underline">Dismiss</button>
                </form>
            </div>
        </div>
    @endforeach
@endif