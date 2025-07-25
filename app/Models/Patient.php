<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\CaseModel;
use App\Models\LabTestTransaction;
use App\Models\ServiceTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class Patient extends Model
{
    use HasFactory;

    protected $fillable = ['name', 'age', 'gender', 'contact'];

    // Relationships (to be created later)
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

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }
}
