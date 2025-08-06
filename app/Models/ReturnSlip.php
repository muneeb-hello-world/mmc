<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ReturnSlip extends Model
{
    protected $fillable = [
        'type',
        'transaction_id',
        'reason',
        'refunded_by',
    ];

    public function refundedBy()
    {
        return $this->belongsTo(User::class, 'refunded_by');
    }
}
