<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\PaymentCase;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CaseModel extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'doctor_id', 'title', 'final_price',
        'room_type', 'status', 'scheduled_date', 'notes', 'balance'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }

    public function paymentCases()
    {
        return $this->hasMany(PaymentCase::class);
    }
}
