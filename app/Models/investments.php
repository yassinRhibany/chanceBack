<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class investments extends Model
{
    protected $fillable = [
        'user_id',
        'opprtunty_id',
        'amount',  
    ];
    public function opprtunity(){
        return $this->belongsTo(investment_opprtunities::class);
    }

    public function offer(){
        return $this->hasMany(investment_offers::class);
    }
    
}
