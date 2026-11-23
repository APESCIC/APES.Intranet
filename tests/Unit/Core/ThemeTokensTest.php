<?php

it('shell css has no hard-coded colour literals', function () {
    $css = file_get_contents(dirname(__DIR__, 3).'/resources/css/shell.css');

    expect($css)->not->toMatch('/#[0-9a-fA-F]{3,8}\b/')
        ->and($css)->not->toMatch('/rgba?\(/');
});
