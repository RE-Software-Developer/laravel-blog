<?php

namespace BinshopsBlog\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Config;
use Illuminate\Support\Facades\DB;

class DbConnection
{
    /**
     * Handle an incoming request.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  \Closure  $next
     * @param  string  $connection  The name of the DB connection to use
     * @return mixed
     */
    public function handle(Request $request, Closure $next, string $connection)
    {
        // Override the default connection for this request
        Config::set('database.default', $connection);

        return $next($request);
    }
}