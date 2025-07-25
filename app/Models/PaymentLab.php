<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\LabTestTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentLab extends Model
{
     use HasFactory;

    protected $fillable = ['payment_id', 'lab_test_transaction_id', 'amount'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function labTestTransaction()
    {
        return $this->belongsTo(LabTestTransaction::class);
    }
}
