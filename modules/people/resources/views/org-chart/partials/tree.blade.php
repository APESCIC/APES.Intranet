@if ($nodes->isEmpty())
    <p class="text-sg-muted">No manager links yet.</p>
@else
    <ul class="space-y-2 pl-0">
        @foreach ($nodes as $node)
            <li class="rounded-sg-field border border-sg-info-mist/70 p-2">
                <details open>
                    <summary>
                        <a class="font-semibold underline" href="{{ route('people.directory.show', $node['person']) }}">{{ $node['person']->name }}</a>
                        @if ($node['person']->job_title)
                            <span class="text-sg-muted"> — {{ $node['person']->job_title }}</span>
                        @endif
                    </summary>
                    @if ($node['reports']->isNotEmpty())
                        <div class="mt-2 border-l border-sg-info-mist pl-4">
                            @include('people::org-chart.partials.tree', ['nodes' => $node['reports']])
                        </div>
                    @endif
                </details>
            </li>
        @endforeach
    </ul>
@endif
