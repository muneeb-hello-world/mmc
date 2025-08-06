<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ShiftSummary extends Model
{
    protected $fillable = [
        'from',
        'to',
        'shift_name',
        'services',
        'labs',
        'doctor_payouts',
        'expenses',
        'returns',
        'final_cash',
        'created_by',
    ];

    protected $casts = [
        'from' => 'datetime',
        'to' => 'datetime',
    ];

    // Optional: Relationship with user (if using auth)
    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
