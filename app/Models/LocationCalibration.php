<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class LocationCalibration extends Model
{
    protected $fillable = [
        'user_id',
        'label',
        'known_latitude',
        'known_longitude',
        'gps_latitude',
        'gps_longitude',
        'gps_accuracy',
        'latitude_offset',
        'longitude_offset',
        'is_active',
        'device_info',
    ];

    protected $casts = [
        'known_latitude' => 'decimal:8',
        'known_longitude' => 'decimal:8',
        'gps_latitude' => 'decimal:8',
        'gps_longitude' => 'decimal:8',
        'gps_accuracy' => 'decimal:2',
        'latitude_offset' => 'decimal:8',
        'longitude_offset' => 'decimal:8',
        'is_active' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get active calibration for a user
     */
    public static function getActiveCalibration($userId)
    {
        return self::where('user_id', $userId)
            ->where('is_active', true)
            ->latest()
            ->first();
    }

    /**
     * Apply calibration to GPS coordinates
     */
    public function applyCorrectionTo($latitude, $longitude)
    {
        return [
            'latitude' => $latitude + $this->latitude_offset,
            'longitude' => $longitude + $this->longitude_offset,
            'corrected' => true,
            'calibration_id' => $this->id,
        ];
    }
}
