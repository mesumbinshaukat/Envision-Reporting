<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Symfony\Component\HttpFoundation\Response;

class WarmupSession
{
    /**
     * Ensure the session is started and a fresh CSRF token exists before rendering the page.
     */
    public function handle(Request $request, Closure $next): Response
    {
        $session = $request->session();

        // Touch the session to make sure a cookie is issued immediately.
        $session->put('_session_warmup_touched', Carbon::now()->timestamp);

        if ($request->isMethod('get') && !$session->has('_csrf_initialized')) {
            // Regenerate the CSRF token once per warm session.
            $session->regenerateToken();
            $session->put('_csrf_initialized', true);
        }

        return $next($request);
    }
}
