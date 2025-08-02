<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\LabTest;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class DoctorLabShare extends Model
{
     use HasFactory;

    protected $fillable = [
        'doctor_id',
        'doctor_share_percent',
        'hospital_share_percent',
    ];

    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
