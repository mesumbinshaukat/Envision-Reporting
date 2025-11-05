<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Expense extends Model
{
    use SoftDeletes;

    protected $fillable = [
        'user_id',
        'currency_id',
        'description',
        'amount',
        'date',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    /**
     * Get expense amount converted to base currency
     */
    public function getAmountInBaseCurrency()
    {
        if (!$this->currency) {
            return $this->amount;
        }
        return $this->currency->toBase($this->amount);
    }
}
