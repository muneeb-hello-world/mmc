<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\LabTest;
use App\Models\Patient;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabTestTransaction extends Model
{
    use HasFactory;

    protected $fillable = [
        'patient_id', 'lab_test_id', 'doctor_id',
        'amount', 'doctor_share', 'hospital_share','is_returned'
    ];

    public function patient()
    {
        return $this->belongsTo(Patient::class);
    }

    public function labTest()
    {
        return $this->belongsTo(LabTest::class);
    }

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
