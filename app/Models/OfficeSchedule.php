<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OfficeSchedule extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_time',
        'end_time',
        'working_days',
        'timezone',
    ];

    protected $casts = [
        'start_time' => 'string',
        'end_time' => 'string',
        'working_days' => 'array',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getWorkingDaysAttribute($value): array
    {
        $days = $this->castAttribute('working_days', $value);

        if (empty($days)) {
            return ['monday', 'tuesday', 'wednesday', 'thursday', 'friday'];
        }

        return array_map('strtolower', $days);
    }

    public function setWorkingDaysAttribute($value): void
    {
        $days = collect($value ?? [])
            ->map(fn ($day) => strtolower((string) $day))
            ->unique()
            ->values()
            ->all();

        $this->attributes['working_days'] = json_encode($days);
    }

    public function getTimezoneAttribute($value): string
    {
        return $value ?: config('app.timezone');
    }
}
