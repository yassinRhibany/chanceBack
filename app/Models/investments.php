<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class investments extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'opprtunty_id',
        'amount',  
    ];
    public function opprtunty(){
        return $this->belongsTo(investment_opprtunities::class,'opprtunty_id');
    }

    public function investment_offers(){
        return $this->hasMany(investment_offers::class);
    }
    
}
