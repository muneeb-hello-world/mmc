<?php

namespace App\Models;

use App\Models\CaseModel;
use App\Models\DoctorLabShare;
use App\Models\DoctorServiceShare;
use App\Models\LabTestTransaction;
use App\Models\ServiceTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Doctor extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'user_id',
        'specialization',
        'days',
        'start_time',
        'end_time',
        'is_on_payroll',
        'payout_frequency'
    ];
    protected $casts = [

        'is_on_payroll' => 'boolean'
    ];

    public function serviceShares()
    {
        return $this->hasMany(DoctorServiceShare::class);
    }

    public function labShares()
    {
        return $this->hasMany(DoctorLabShare::class);
    }

    public function serviceTransactions()
    {
        return $this->hasMany(ServiceTransaction::class);
    }

    public function labTestTransactions()
    {
        return $this->hasMany(LabTestTransaction::class);
    }

    public function cases()
    {
        return $this->hasMany(CaseModel::class);
    }

    public function payouts()
    {
        return $this->hasMany(DoctorPayout::class);
    }
}
