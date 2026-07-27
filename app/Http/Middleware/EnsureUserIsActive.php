<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserIsActive
{
    /**
     * Los usuarios desactivados por administración pierden acceso de inmediato.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        // Comparación estricta: si el atributo no vino en la consulta (null),
        // no se debe tratar al usuario como desactivado.
        if ($user && $user->getAttribute('is_active') === false) {
            auth()->guard('web')->logout();
            $request->session()->invalidate();
            $request->session()->regenerateToken();

            return redirect()->route('login')->withErrors([
                'email' => 'Tu cuenta está desactivada; contacta al administrador.',
            ]);
        }

        return $next($request);
    }
}
