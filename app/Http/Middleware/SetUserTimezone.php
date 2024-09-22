<?php
namespace App\Http\Middleware;

use Closure;
use Illuminate\Support\Carbon;

class SetUserTimezone
{
    public function handle($request, Closure $next)
    {
    
        if (session()->has('timezone')) {
            $timezone = session('timezone');
            Carbon::setTimezone($timezone);
        }
        return $next($request);
    }
}
