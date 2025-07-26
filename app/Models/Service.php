<?php

namespace App\Models;

use App\Models\DoctorServiceShare;
use App\Models\ServiceTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Service extends Model
{
    protected $casts = [
    'is_doctor_related' => 'boolean',
];
     use HasFactory;

    protected $fillable = ['name', 'is_doctor_related', 'default_price'];

    public function doctorShares()
    {
        return $this->hasMany(DoctorServiceShare::class);
    }

    public function transactions()
    {
        return $this->hasMany(ServiceTransaction::class);
    }
}
