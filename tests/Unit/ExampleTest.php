<?php

it('runs on PHP 8.3 or newer', function () {
    expect(version_compare(PHP_VERSION, '8.3.0', '>='))->toBeTrue();
});
