<?php

namespace App\Services;

use App\Models\EmployeeActivityLog;
use App\Models\EmployeeUser;
use Illuminate\Contracts\Support\Arrayable;
use Illuminate\Http\Request;
use Illuminate\Support\Arr;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Log;
use App\Services\GeolocationService;

class EmployeeActivityLogger
{
    /**
     * Keys that should not be stored in request payloads.
     *
     * @var array<int, string>
     */
    protected array $sensitiveKeys = [
        'password',
        'password_confirmation',
        'current_password',
        'new_password',
        'token',
        '_token',
        '_method',
        'remember',
        'secret',
    ];

    /**
     * Log an employee activity.
     *
     * @param  string  $action
     * @param  array<string, mixed>  $attributes
     */
    public function log(string $action, array $attributes = []): ?EmployeeActivityLog
    {
        $request = $attributes['request'] ?? request();
        $employeeUser = $attributes['employee_user'] ?? $this->resolveEmployeeUser($attributes['employee_user_id'] ?? null);
        $allowWithoutEmployee = (bool) ($attributes['allow_without_employee'] ?? false);

        if (!$employeeUser && !$allowWithoutEmployee) {
            return null;
        }

        $adminId = $attributes['admin_id']
            ?? ($employeeUser instanceof EmployeeUser ? $employeeUser->admin_id : null);

        $ipPair = ['ipv4' => null, 'ipv6' => null];
        $deviceInfo = [
            'device_type' => null,
            'browser' => null,
            'os' => null,
            'user_agent' => null,
        ];

        if ($request instanceof Request) {
            try {
                /** @var GeolocationService $geo */
                $geo = app(GeolocationService::class);
                $ipPair = $geo->getClientIpPair($request);
                $deviceInfo = array_merge($deviceInfo, $geo->parseUserAgent($request));
            } catch (\Throwable $exception) {
                Log::debug('Failed to resolve geolocation metadata for employee activity log', [
                    'action' => $action,
                    'exception' => $exception->getMessage(),
                ]);
            }
        }

        $occurredAt = Carbon::parse($attributes['occurred_at'] ?? now());
        $requestPayload = $attributes['request_payload'] ?? $this->extractRequestPayload($request);
        $metadata = $this->normalizeMetadata($attributes['metadata'] ?? []);

        $relatedUserId = null;
        if ($employeeUser instanceof EmployeeUser) {
            $relatedUserId = $employeeUser->id;
        } elseif (isset($attributes['employee_user_id'])) {
            $relatedUserId = $attributes['employee_user_id'];
        }

        $log = EmployeeActivityLog::create([
            'admin_id' => $adminId,
            'employee_user_id' => $relatedUserId,
            'category' => $attributes['category'] ?? null,
            'action' => $action,
            'summary' => $attributes['summary'] ?? null,
            'description' => $attributes['description'] ?? null,
            'request_method' => $attributes['request_method'] ?? ($request instanceof Request ? $request->method() : null),
            'route_name' => $attributes['route_name'] ?? ($request instanceof Request ? optional($request->route())->getName() : null),
            'request_path' => $attributes['request_path'] ?? ($request instanceof Request ? $request->path() : null),
            'referer' => $attributes['referer'] ?? ($request instanceof Request ? $request->headers->get('referer') : null),
            'response_status' => $attributes['response_status'] ?? null,
            'ip_address' => $attributes['ip_address'] ?? ($ipPair['ipv4'] ?? $ipPair['ipv6']),
            'ip_address_v4' => $attributes['ip_address_v4'] ?? $ipPair['ipv4'],
            'ip_address_v6' => $attributes['ip_address_v6'] ?? $ipPair['ipv6'],
            'device_type' => $attributes['device_type'] ?? $deviceInfo['device_type'],
            'browser' => $attributes['browser'] ?? $deviceInfo['browser'],
            'os' => $attributes['os'] ?? $deviceInfo['os'],
            'user_agent' => $attributes['user_agent'] ?? $deviceInfo['user_agent'],
            'request_payload' => $requestPayload,
            'metadata' => $metadata,
            'occurred_at' => $occurredAt,
        ]);

        $this->broadcastLog($log);

        return $log;
    }

    /**
     * Convenience helper to log a request/response pair.
     *
     * @param  array<string, mixed>  $overrides
     */
    public function logForRequest(Request $request, $response, array $overrides = []): ?EmployeeActivityLog
    {
        $status = method_exists($response, 'getStatusCode') ? $response->getStatusCode() : null;

        $attributes = array_merge([
            'request' => $request,
            'response_status' => $status,
            'summary' => sprintf('%s %s', $request->method(), $request->path()),
            'request_method' => $request->method(),
            'request_path' => $request->path(),
            'route_name' => optional($request->route())->getName(),
            'referer' => $request->headers->get('referer'),
            'metadata' => array_merge(
                $overrides['metadata'] ?? [],
                [
                    'duration_ms' => $overrides['duration_ms'] ?? null,
                    'query' => $request->query(),
                ]
            ),
        ], $overrides);

        return $this->log($overrides['action'] ?? 'request', $attributes);
    }

    protected function resolveEmployeeUser(?int $employeeUserId = null): ?EmployeeUser
    {
        if ($employeeUserId) {
            return EmployeeUser::find($employeeUserId);
        }

        if (auth()->guard('employee')->check()) {
            return auth()->guard('employee')->user();
        }

        return null;
    }

    protected function extractRequestPayload(?Request $request): ?array
    {
        if (!$request instanceof Request) {
            return null;
        }

        $payload = $request->all();

        if (empty($payload)) {
            return null;
        }

        $sanitized = Arr::except($payload, $this->sensitiveKeys);

        return empty($sanitized) ? null : $sanitized;
    }

    /**
     * @param  array<string, mixed>|Arrayable<string, mixed>|null  $metadata
     */
    protected function normalizeMetadata($metadata): ?array
    {
        if ($metadata instanceof Arrayable) {
            $metadata = $metadata->toArray();
        }

        if (!is_array($metadata)) {
            return null;
        }

        return empty($metadata) ? null : $metadata;
    }

    protected function broadcastLog(EmployeeActivityLog $log): void
    {
        try {
            event(new \App\Events\EmployeeActivityCreated($log->fresh(['employeeUser.employee', 'admin'])));
        } catch (\Throwable $exception) {
            Log::debug('Failed to broadcast employee activity log', [
                'log_id' => $log->id,
                'exception' => $exception->getMessage(),
            ]);
        }
    }
}
