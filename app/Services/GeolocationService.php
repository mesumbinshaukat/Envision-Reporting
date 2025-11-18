<?php

namespace App\Services;

use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class GeolocationService
{
    /**
     * Normalize coordinates to 10 decimal places for consistency.
     * This ensures all coordinates are stored and compared with the same precision.
     */
    public function normalizeCoordinate(float $coordinate): float
    {
        return round($coordinate, 10);
    }

    /**
     * Normalize a pair of coordinates.
     */
    public function normalizeCoordinates(float $latitude, float $longitude): array
    {
        return [
            'latitude' => $this->normalizeCoordinate($latitude),
            'longitude' => $this->normalizeCoordinate($longitude),
        ];
    }

    /**
     * Calculate distance between two coordinates using Haversine formula.
     * Returns distance in meters.
     * Coordinates are normalized to 10 decimal places before calculation.
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        // Normalize coordinates to 10 decimal places for consistency
        $lat1 = $this->normalizeCoordinate($lat1);
        $lon1 = $this->normalizeCoordinate($lon1);
        $lat2 = $this->normalizeCoordinate($lat2);
        $lon2 = $this->normalizeCoordinate($lon2);

        $earthRadius = 6371000; // Earth's radius in meters

        $latFrom = deg2rad($lat1);
        $lonFrom = deg2rad($lon1);
        $latTo = deg2rad($lat2);
        $lonTo = deg2rad($lon2);

        $latDelta = $latTo - $latFrom;
        $lonDelta = $lonTo - $lonFrom;

        $a = sin($latDelta / 2) * sin($latDelta / 2) +
             cos($latFrom) * cos($latTo) *
             sin($lonDelta / 2) * sin($lonDelta / 2);
        
        $c = 2 * atan2(sqrt($a), sqrt(1 - $a));

        return $earthRadius * $c;
    }

    /**
     * Check if coordinates are within allowed radius.
     */
    public function isWithinRadius(
        float $userLat,
        float $userLon,
        float $officeLat,
        float $officeLon,
        int $radiusMeters
    ): bool {
        $distance = $this->calculateDistance($userLat, $userLon, $officeLat, $officeLon);
        return $distance <= $radiusMeters;
    }

    /**
     * Return both IPv4 and IPv6 addresses detected for the current request.
     *
     * @return array{ipv4: string|null, ipv6: string|null}
     */
    public function getClientIpPair(Request $request): array
    {
        $ipv4 = null;
        $ipv6 = null;
        $detectionError = null;

        // Prefer explicitly supplied public IPs from the client (captured via JS)
        $reportedIpv4 = $request->input('public_ip') ?? $request->input('public_ip_v4');
        if (is_string($reportedIpv4) && filter_var($reportedIpv4, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipv4 = $reportedIpv4;
        }

        $reportedIpv6 = $request->input('public_ip_v6');
        if (is_string($reportedIpv6) && filter_var($reportedIpv6, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipv6 = $reportedIpv6;
        }

        $attempts = 0;

        // Attempt 1: direct server variables ($request->ip() and REMOTE_ADDR)
        $attempts++;
        $this->processIpCandidate($request->ip(), $ipv4, $ipv6);
        $this->processIpCandidate($request->server('REMOTE_ADDR'), $ipv4, $ipv6);

        // Attempt 2: header inspection for first non-private IP
        if (!$ipv4 && !$ipv6) {
            $attempts++;
            $headerValues = [
                $request->header('X-Forwarded-For'),
                $request->header('HTTP_X_FORWARDED_FOR'),
                $request->header('HTTP_CLIENT_IP'),
                $request->header('HTTP_X_REAL_IP'),
                $request->header('X-Real-IP'),
            ];

            foreach ($headerValues as $headerValue) {
                foreach ($this->extractHeaderIps($headerValue) as $candidate) {
                    $this->processIpCandidate($candidate, $ipv4, $ipv6, false);
                    if ($ipv4 || $ipv6) {
                        break 2;
                    }
                }
            }
        }

        // Attempt 3: silent fallback (no further external lookups)
        if (!$ipv4 && !$ipv6) {
            $attempts++;
            $detectionError = sprintf(
                'Unable to detect IP: no valid headers or server vars found after %d attempts',
                $attempts
            );
            $request->attributes->set('ip_detection_error', $detectionError);
        }

        return [
            'ipv4' => $ipv4,
            'ipv6' => $ipv6,
        ];
    }

    /**
     * Get the preferred client IP address (IPv4 first, fallback to IPv6).
     */
    public function getClientIp(Request $request): ?string
    {
        $pair = $this->getClientIpPair($request);

        return $pair['ipv4'] ?? $pair['ipv6'];
    }

    /**
     * Parse user agent and extract device information.
     */
    public function parseUserAgent(Request $request): array
    {
        $agent = new Agent();
        $agent->setUserAgent($request->userAgent());

        return [
            'user_agent' => $request->userAgent(),
            'device_type' => $agent->isDesktop() ? 'Desktop' : ($agent->isTablet() ? 'Tablet' : ($agent->isMobile() ? 'Mobile' : 'Unknown')),
            'browser' => $agent->browser() . ' ' . $agent->version($agent->browser()),
            'os' => $agent->platform() . ' ' . $agent->version($agent->platform()),
        ];
    }

    /**
     * Log attendance attempt.
     */
    public function logAttempt(
        int $employeeUserId,
        ?int $attendanceId,
        string $action,
        ?string $failureReason,
        ?float $latitude,
        ?float $longitude,
        ?float $distanceFromOffice,
        Request $request,
        ?array $additionalInfo = null
    ): AttendanceLog {
        $deviceInfo = $this->parseUserAgent($request);
        $clientIps = $this->getClientIpPair($request);
        $primaryIp = $clientIps['ipv4'] ?? $clientIps['ipv6'];
        $ipDetectionError = $request->attributes->get('ip_detection_error');

        if (!$clientIps['ipv4'] && !$clientIps['ipv6'] && $ipDetectionError) {
            $failureReason = $failureReason ?? 'ip_detection_failed';
            $additionalInfo = array_merge(
                $additionalInfo ?? [],
                ['error' => $ipDetectionError]
            );
        }

        return AttendanceLog::create([
            'employee_user_id' => $employeeUserId,
            'attendance_id' => $attendanceId,
            'action' => $action,
            'failure_reason' => $failureReason,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_from_office' => $distanceFromOffice,
            'ip_address' => $primaryIp,
            'ip_address_v4' => $clientIps['ipv4'],
            'ip_address_v6' => $clientIps['ipv6'],
            'user_agent' => $deviceInfo['user_agent'],
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'additional_info' => $additionalInfo ? json_encode($additionalInfo) : null,
            'attempted_at' => now(),
        ]);
    }

    /**
     * Process a potential IP candidate and assign it to IPv4/IPv6 slots.
     */
    protected function processIpCandidate($candidate, ?string &$ipv4, ?string &$ipv6, bool $allowPrivate = true): void
    {
        if (!is_string($candidate)) {
            return;
        }

        $candidate = trim($candidate);
        if ($candidate === '') {
            return;
        }

        if (stripos($candidate, '::ffff:') === 0) {
            $mapped = substr($candidate, 7);
            if ($mapped !== false) {
                $candidate = $mapped;
            }
        }

        if (!$allowPrivate && $this->isPrivateIp($candidate)) {
            return;
        }

        if (!$ipv4 && filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            $ipv4 = $candidate;
            return;
        }

        if (!$ipv6 && filter_var($candidate, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ipv6 = $candidate;
        }
    }

    /**
     * Extract potential IPs from a header value, handling comma-separated lists.
     */
    protected function extractHeaderIps(?string $value): array
    {
        if (!is_string($value) || $value === '') {
            return [];
        }

        return array_filter(array_map('trim', explode(',', $value)));
    }

    /**
     * Determine if an IP address belongs to a private or reserved range.
     */
    protected function isPrivateIp(string $ip): bool
    {
        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV4)) {
            return preg_match('/^(10\.)|(127\.)|(169\.254\.)|(192\.168\.)|(172\.(1[6-9]|2[0-9]|3[0-1])\.)/', $ip) === 1;
        }

        if (filter_var($ip, FILTER_VALIDATE_IP, FILTER_FLAG_IPV6)) {
            $ip = strtolower($ip);
            return $ip === '::1' || strpos($ip, 'fe80:') === 0 || strpos($ip, 'fc') === 0 || strpos($ip, 'fd') === 0;
        }

        return false;
    }

    /**
     * Validate geolocation data.
     */
    public function validateCoordinates(?float $latitude, ?float $longitude): bool
    {
        if ($latitude === null || $longitude === null) {
            return false;
        }

        // Valid latitude range: -90 to 90
        // Valid longitude range: -180 to 180
        return $latitude >= -90 && $latitude <= 90 &&
               $longitude >= -180 && $longitude <= 180;
    }

    /**
     * Get approximate location from IP (basic implementation).
     * For production, consider using a service like ipapi.co or ipgeolocation.io
     */
    public function getLocationFromIp(string $ip): ?array
    {
        // Skip for local IPs
        if ($ip === '127.0.0.1' || $ip === '::1' || strpos($ip, '192.168.') === 0) {
            return null;
        }

        try {
            // Using a free IP geolocation service
            $response = @file_get_contents("http://ip-api.com/json/{$ip}?fields=status,country,regionName,city,lat,lon");
            
            if ($response) {
                $data = json_decode($response, true);
                
                if ($data && $data['status'] === 'success') {
                    return [
                        'country' => $data['country'] ?? null,
                        'region' => $data['regionName'] ?? null,
                        'city' => $data['city'] ?? null,
                        'latitude' => $data['lat'] ?? null,
                        'longitude' => $data['lon'] ?? null,
                    ];
                }
            }
        } catch (\Exception $e) {
            // Silently fail - geolocation from IP is optional
        }

        return null;
    }
}
