<?php

namespace App\Models;

use App\Models\PaymentRequest;
use App\Models\User;
use Illuminate\Database\Eloquent\Model;

class Customer extends Model
{
    protected $table = 'merchant_customers';

    protected $fillable = [
        'merchant_id',
        'name',
        'email',
        'phone',
    ];

    public function merchant()
    {
        return $this->belongsTo(User::class, 'merchant_id');
    }

    public function paymentRequests()
    {
        return $this->hasMany(PaymentRequest::class, 'customer_id');
    }
}
