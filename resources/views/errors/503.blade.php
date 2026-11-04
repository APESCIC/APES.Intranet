@php
    $message = isset($exception) ? $exception->getMessage() : '';
    $message = in_array($message, ['', 'Service Unavailable'], true)
        ? 'We are updating or fixing the intranet. Please try again in a few minutes.'
        : $message;
@endphp
<x-layouts.error code="503" title="The intranet is briefly unavailable">
    <p>{{ $message }}</p>
    <p>For anything urgent, use Microsoft Teams.</p>
</x-layouts.error>
