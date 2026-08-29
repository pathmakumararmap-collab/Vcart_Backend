<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class Invoice extends Model
{
    protected $fillable = ['invoice_no', 'order_id', 'path', 'issued_at', 'due_at', 'total_amount', 'status'];

    protected function casts(): array
    {
        return [
            'issued_at' => 'datetime',
            'due_at' => 'datetime',
            'total_amount' => 'decimal:2',
        ];
    }

    /**
     * @return BelongsTo<Order, $this>
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }
}
