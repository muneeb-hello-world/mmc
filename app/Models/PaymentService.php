<?php

namespace App\Models;

use App\Models\Payment;
use App\Models\ServiceTransaction;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Factories\HasFactory;

class PaymentService extends Model
{
     use HasFactory;

    protected $fillable = ['payment_id', 'service_transaction_id', 'amount'];

    public function payment()
    {
        return $this->belongsTo(Payment::class);
    }

    public function serviceTransaction()
    {
        return $this->belongsTo(ServiceTransaction::class);
    }
}
