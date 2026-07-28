<?php

use function Pest\Laravel\get;

it('displays the home page', function () {
    $response = get(route('home'));

    $response->assertOk();
});
