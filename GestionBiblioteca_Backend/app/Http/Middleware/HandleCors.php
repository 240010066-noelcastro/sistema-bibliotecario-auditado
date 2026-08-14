<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class HandleCors
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
public function handle($request, Closure $next)
{
    $origin = $request->header('Origin');
    
    // Inyecta el token de la cookie HttpOnly en la cabecera Authorization si existe
    if (!$request->bearerToken() && $request->hasCookie('token')) {
        $request->headers->set('Authorization', 'Bearer ' . $request->cookie('token'));
    }

    // Lista de dominios autorizados leídos desde la configuración
    $allowedOrigins = array_filter(
        array_map('trim', explode(',', (string) env('CORS_ALLOWED_ORIGINS', '')))
    );

    if ($request->isMethod('OPTIONS')) {
        $response = response('', 200);
    } else {
        $response = $next($request);
    }

    if ($origin && in_array($origin, $allowedOrigins, true)) {
        $response->header('Access-Control-Allow-Origin', $origin);
        $response->header('Access-Control-Allow-Credentials', 'true');
        $response->header('Vary', 'Origin');
    }

    return $response
        ->header('Access-Control-Allow-Methods', 'GET, POST, PUT, DELETE, OPTIONS')
        ->header('Access-Control-Allow-Headers', 'Content-Type, Authorization, X-Requested-With, Accept');
}
}
