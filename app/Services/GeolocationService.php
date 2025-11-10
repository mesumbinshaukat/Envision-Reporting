<?php

namespace App\Services;

use App\Models\AttendanceLog;
use Illuminate\Http\Request;
use Jenssegers\Agent\Agent;

class GeolocationService
{
    /**
     * Normalize coordinates to 8 decimal places for consistency.
     * This ensures all coordinates are stored and compared with the same precision.
     */
    public function normalizeCoordinate(float $coordinate): float
    {
        return round($coordinate, 8);
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
     * Coordinates are normalized to 8 decimal places before calculation.
     */
    public function calculateDistance(
        float $lat1,
        float $lon1,
        float $lat2,
        float $lon2
    ): float {
        // Normalize coordinates to 8 decimal places for consistency
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
     * Get client IP address (handles proxies and load balancers).
     */
    public function getClientIp(Request $request): ?string
    {
        $ip = $request->ip();
        
        // Check for forwarded IP (behind proxy/load balancer)
        if ($request->header('X-Forwarded-For')) {
            $ips = explode(',', $request->header('X-Forwarded-For'));
            $ip = trim($ips[0]);
        } elseif ($request->header('X-Real-IP')) {
            $ip = $request->header('X-Real-IP');
        }
        
        return $ip;
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
        
        return AttendanceLog::create([
            'employee_user_id' => $employeeUserId,
            'attendance_id' => $attendanceId,
            'action' => $action,
            'failure_reason' => $failureReason,
            'latitude' => $latitude,
            'longitude' => $longitude,
            'distance_from_office' => $distanceFromOffice,
            'ip_address' => $this->getClientIp($request),
            'user_agent' => $deviceInfo['user_agent'],
            'device_type' => $deviceInfo['device_type'],
            'browser' => $deviceInfo['browser'],
            'os' => $deviceInfo['os'],
            'additional_info' => $additionalInfo ? json_encode($additionalInfo) : null,
            'attempted_at' => now(),
        ]);
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
