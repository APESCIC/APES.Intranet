<?php

use Modules\Communications\Support\TeamsDeepLink;

it('builds Teams chat links without storing tokens', function () {
    $url = TeamsDeepLink::chatWithEmail('sam@example.test');

    expect($url)->toStartWith('https://teams.microsoft.com/l/chat/')
        ->and($url)->toContain(rawurlencode('sam@example.test'))
        ->and($url)->not->toContain('token');
});
