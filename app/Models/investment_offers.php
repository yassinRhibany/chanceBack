<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class investment_offers extends Model
{
     
    protected $fillable = [
        'investment_id',
        'seller_id',
        'offred_amount',
        'price',
        'status',
        'buyer_id',
        'sold_at',
    ];
    public function investment(){
        return $this->belongsTo(investments::class);
    }
}
