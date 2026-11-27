<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class SetLocale
{
    public function handle(Request $request, Closure $next)
    {
        $locale = $request->query('lang')
            ?? $request->header('X-Locale')
            ?? ($request->hasSession() ? $request->session()->get('locale') : null)
            ?? 'ar';
        $locale = in_array($locale, ['ar', 'en']) ? $locale : 'ar';
        if ($request->hasSession() && $request->query('lang')) {
            $request->session()->put('locale', $locale);
        }
        app()->setLocale($locale);
        return $next($request);
    }
}
