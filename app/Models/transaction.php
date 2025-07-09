<?php

namespace App\Models;

use App\TrancationType;
use Illuminate\Database\Eloquent\Model;

class transaction extends Model
{
    protected $table= 'transaction';
    
  
    protected $fillable = [
        'user_id',
        'amount',
        'type',
        'time_operation',
        'opprtunty_id',
    ];
    
    public function user()
{
    return $this->belongsTo(User::class);
}
    protected $casts = [
        'type' => TrancationType::class,
    ];
}
