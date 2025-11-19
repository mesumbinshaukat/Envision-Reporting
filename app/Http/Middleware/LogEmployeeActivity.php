<?php

namespace App\Http\Middleware;

use App\Services\EmployeeActivityLogger;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class LogEmployeeActivity
{
    public function __construct(private EmployeeActivityLogger $logger)
    {
    }

    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        /** @var Response $response */
        $response = $next($request);

        if (!auth()->guard('employee')->check()) {
            return $response;
        }

        $employeeUser = auth()->guard('employee')->user();
        $this->logger->logForRequest($request, $response, [
            'action' => $this->deriveAction($request),
            'category' => $request->isMethod('get') ? 'navigation' : 'interaction',
            'employee_user' => $employeeUser,
            'metadata' => [
                'route_parameters' => $request->route()?->parameters() ?? [],
                'response_status' => method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null,
            ],
        ]);

        return $response;
    }

    protected function deriveAction(Request $request): string
    {
        $routeName = $request->route()?->getName();
        if ($routeName) {
            return 'route_' . Str::replace('.', '_', $routeName);
        }

        $path = trim(Str::replace('/', '_', $request->path()), '_');
        if ($path === '') {
            $path = 'home';
        }

        return strtolower($request->method()) . '_' . $path;
    }
}
