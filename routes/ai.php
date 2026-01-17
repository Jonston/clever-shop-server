<?php

use App\Mcp\Servers\ProductServer;
use App\Mcp\Servers\WeatherServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::web('/mcp/weather', WeatherServer::class);
Mcp::web('/mcp/products', ProductServer::class);
