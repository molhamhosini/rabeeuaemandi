<?php

namespace App\Http\Middleware;

use App\Models\CustomerToken;
use Closure;
use Illuminate\Http\Request;

/**
 * Reads "Authorization: Bearer <token>" from the app.
 * customer          -> the request must be signed in (401 otherwise)
 * customer:optional -> signs in when a token is sent, carries on as a guest when not
 */
class CustomerAuth
{
    public function handle(Request $request, Closure $next, string $mode = 'required')
    {
        $token = $request->bearerToken();
        $row = $token ? CustomerToken::with('customer')->where('token_hash', hash('sha256', $token))->first() : null;

        if ($row?->customer) {
            if (! $row->last_used_at || $row->last_used_at->lt(now()->subHour())) {
                $row->forceFill(['last_used_at' => now()])->save();
            }
            $request->attributes->set('customer', $row->customer);
            $request->attributes->set('customer_token', $row);
        } elseif ($mode !== 'optional') {
            return response()->json(['message' => 'Unauthenticated.'], 401);
        }

        return $next($request);
    }
}
