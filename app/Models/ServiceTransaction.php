<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\Patient;
use App\Models\Service;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class ServiceTransaction extends Model
{
     use HasFactory;

    protected $fillable = [
        'patient_id', 'service_id', 'doctor_id',
        'price', 'doctor_share', 'hospital_share','booking' , 'arrived' , 'token'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function service()
    {
        return $this->belongsTo(Service::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
