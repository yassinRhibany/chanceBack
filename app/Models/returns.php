<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class returns extends Model
{
    protected $fillable = [
        'user_id',
        'opprtunty_id',
        'amount',
        'return_date',
       
    ];
   public function opprtunty(){
        return $this->belongsTo(investment_opprtunities::class,'opprtunty_id');
    }
 public function user()
{
    return $this->belongsTo(User::class, 'user_id');
}

}
