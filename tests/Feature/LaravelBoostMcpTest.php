<?php

use Illuminate\Support\Facades\Route;

it('registers the Laravel Boost web MCP server', function () {
    $route = collect(Route::getRoutes()->getRoutes())
        ->first(fn ($route): bool => $route->uri() === 'mcp/laravel-boost'
            && in_array('POST', $route->methods(), true));

    expect($route)->not->toBeNull();
});
