<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\CaseModel;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentCase extends Model
{
    use HasFactory;

    protected $fillable = ['payment_id', 'case_model_id', 'amount'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function case()
    {
        return $this->belongsTo(CaseModel::class, 'case_model_id');
    }
}
