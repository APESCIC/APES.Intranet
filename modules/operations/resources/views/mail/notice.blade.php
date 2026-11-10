@foreach ($lines as $line)
{{ $line }}

@endforeach
@if ($url)
Open the intranet: {{ $url }}
@endif
