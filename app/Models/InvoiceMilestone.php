<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InvoiceMilestone extends Model
{
    protected $fillable = [
        'invoice_id',
        'amount',
        'description',
        'order',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'order' => 'integer',
    ];

    /**
     * Get the invoice that owns the milestone
     */
    public function invoice()
    {
        return $this->belongsTo(Invoice::class);
    }
}
