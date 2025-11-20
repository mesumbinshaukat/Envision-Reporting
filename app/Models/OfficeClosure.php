<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

class OfficeClosure extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'start_date',
        'end_date',
        'reason',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function scopeActiveBetween($query, Carbon $start, Carbon $end)
    {
        return $query->where(function ($q) use ($start, $end) {
            $q->whereBetween('start_date', [$start->toDateString(), $end->toDateString()])
                ->orWhere(function ($inner) use ($start, $end) {
                    $inner->whereNotNull('end_date')
                        ->where('start_date', '<=', $end->toDateString())
                        ->where('end_date', '>=', $start->toDateString());
                })
                ->orWhere(function ($inner) use ($start, $end) {
                    $inner->whereNull('end_date')
                        ->where('start_date', '<=', $end->toDateString());
                });
        });
    }
}
