<?php

namespace App\Http\Middleware;

use App\Models\ConnectorToken;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class AuthenticateConnector
{
    public function handle(Request $request, Closure $next): Response
    {
        $plainToken = $request->bearerToken();

        if (! is_string($plainToken) || strlen($plainToken) < 40) {
            return response()->json(['message' => 'Connecteur non authentifié.'], 401);
        }

        $token = ConnectorToken::query()
            ->with('user')
            ->where('token_hash', hash('sha256', $plainToken))
            ->first();

        if (! $token?->isUsable()) {
            return response()->json(['message' => 'Jeton du connecteur invalide, expiré ou révoqué.'], 401);
        }

        $request->setUserResolver(fn () => $token->user);
        $request->attributes->set('connector_token', $token);

        if ($token->last_used_at === null || $token->last_used_at->lt(now()->subMinutes(5))) {
            $token->forceFill(['last_used_at' => now()])->save();
        }

        return $next($request);
    }
}
