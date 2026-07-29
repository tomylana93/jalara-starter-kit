<?php

use Laravel\Boost\Mcp\Boost;
use Laravel\Mcp\Facades\Mcp;

if (app()->environment(['local', 'testing'])) {
    Mcp::web('/mcp/laravel-boost', Boost::class);
}
