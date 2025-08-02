<?php

namespace App\Models;

use App\Models\DoctorLabShare;
use App\Models\LabTestTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class LabTest extends Model
{
    use HasFactory;

    // App\Models\LabTest.php

    protected $casts = [
        'fromOutsideLab' => 'boolean',
    ];


    protected $fillable = ['name', 'price', 'days_required', 'fromOutsideLab', 'cost_price_percentage'];

    public function doctorShares()
    {
        return $this->hasMany(DoctorLabShare::class);
    }

    public function transactions()
    {
        return $this->hasMany(LabTestTransaction::class);
    }
}
