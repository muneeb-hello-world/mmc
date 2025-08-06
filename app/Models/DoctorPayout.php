<?php

namespace App\Models;

use App\Models\Doctor;
use App\Models\LabTestTransaction;
use App\Models\ServiceTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DoctorPayout extends Model
{
     use HasFactory;

    protected $fillable = [
        'doctor_id',
        'amount',
        'from_date',
        'to_date',
        'paid_at',
        'method',
        'notes'
    ];

    public function doctor() 
    {
        return $this->belongsTo(Doctor::class);
    }

    public function serviceTransactions()
    {
        return $this->hasMany(ServiceTransaction::class);
    }

    public function labTestTransactions():HasMany
    {
        return $this->hasMany(LabTestTransaction::class);
    }
}
