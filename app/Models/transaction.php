<?php

namespace App\Models;

use App\TrancationType;
use Illuminate\Database\Eloquent\Model;

class transaction extends Model
{
    protected $fillable = [
        'user_id',
        'stripe_payment_intent_id',
        'currency',
        'amount',
        'time_operation',
    ];
    
    public function user()
{
    return $this->belongsTo(User::class);
}
    protected $casts = [
        'type' => TrancationType::class,
    ];
}
