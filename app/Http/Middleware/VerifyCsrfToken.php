<?php

namespace App\Http\Middleware;

use Illuminate\Foundation\Http\Middleware\VerifyCsrfToken as Middleware;

class VerifyCsrfToken extends Middleware
{
    /**
     * The URIs that should be excluded from CSRF verification.
     *
     * @var array<int, string>
     */
    protected $except = [
        
    ];
   /* public function handle($request, \Closure $next)

{
    // تعيين CSRF Token ثابت
    $fixedToken = 'xS2fT6j8LmN0PqR4vW5yZk1AaBbCcDdEeFfGg';
    
    // إجبار Laravel على استخدام هذا التوكن في الطلبات
    $request->session()->put('_token', $fixedToken);
    $request->headers->set('X-CSRF-TOKEN', $fixedToken);

    return parent::handle($request, $next);
}*/
}
