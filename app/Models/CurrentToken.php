<?php

namespace App\Models;

use App\Models\Doctor;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class CurrentToken extends Model
{
    use HasFactory;

    protected $fillable = [
        'doctor_id',
        'token_number',
        'patient_name',
    ];

    /**
     * Get the doctor associated with the current token.
     */
    public function doctor()
    {
        return $this->belongsTo(Doctor::class);
    }
}
