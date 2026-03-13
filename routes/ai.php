<?php

declare(strict_types=1);

use App\Mcp\Servers\TenantoServer;
use Laravel\Mcp\Facades\Mcp;

Mcp::local('tenanto', TenantoServer::class);
