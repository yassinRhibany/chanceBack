<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class returns extends Model
{
    protected $fillable = [
        'investment_id',
        'amount',
        'return_date',
        'status',
       
    ];
    public function investment()
{
    return $this->belongsTo(investments::class);
}

}
