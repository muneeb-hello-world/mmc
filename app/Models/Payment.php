<?php

namespace App\Models;

use App\Models\Patient;
use App\Models\PaymentLab;
use App\Models\PaymentCase;
use App\Models\PaymentService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Payment extends Model
{
     use HasFactory;

    protected $fillable = ['patient_id', 'amount', 'method', 'remarks'];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function paymentServices()
    {
        return $this->hasMany(PaymentService::class);
    }

    public function paymentLabs()
    {
        return $this->hasMany(PaymentLab::class);
    }

    public function paymentCases()
    {
        return $this->hasMany(PaymentCase::class);
    }
}
