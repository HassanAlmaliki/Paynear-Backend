<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class SetLocale
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $locale = $request->header('Accept-Language', 'ar');
        $supportedLocales = ['ar', 'en'];
        
        if (!in_array($locale, $supportedLocales)) {
            $locale = 'ar';
        }

        app()->setLocale($locale);

        return $next($request);
    }
}
