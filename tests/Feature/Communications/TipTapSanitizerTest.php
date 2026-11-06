<?php

use App\Core\Support\HtmlSanitizer;

it('strips script tags and event attributes from rich text', function () {
    $sanitizer = new HtmlSanitizer;

    $dirty = '<p onclick="alert(1)">Hello<script>alert(2)</script></p><img src=x onerror=alert(3)><a href="javascript:alert(4)">bad</a><a href="https://apes.org.uk">good</a>';
    $clean = $sanitizer->clean($dirty);

    expect($clean)->not->toContain('<script')
        ->and($clean)->not->toContain('onclick')
        ->and($clean)->not->toContain('onerror')
        ->and($clean)->not->toContain('javascript:')
        ->and($clean)->toContain('Hello')
        ->and($clean)->toContain('https://apes.org.uk');
});
