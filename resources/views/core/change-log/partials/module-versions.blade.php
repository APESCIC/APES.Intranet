@php($rows = $release->moduleVersionRows())
@if ($rows !== [])
    <section class="changelog-release__module-versions" aria-labelledby="{{ $headingId }}-modules">
        <h3 id="{{ $headingId }}-modules" class="changelog-release__type">Module versions</h3>
        <table class="sg-table changelog-module-versions">
            <thead>
                <tr>
                    <th scope="col">Component</th>
                    <th scope="col">Version</th>
                </tr>
            </thead>
            <tbody>
                @foreach ($rows as $row)
                    <tr @if ($row['changed']) class="changelog-module-versions__row--changed" @endif>
                        <th scope="row">{{ $row['label'] }}</th>
                        <td>
                            <code>{{ $row['version'] }}</code>
                            @if ($row['changed'])
                                <span class="changelog-badge changelog-badge--changed">Changed</span>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </section>
@endif