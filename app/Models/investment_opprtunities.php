<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;


class investment_opprtunities extends Model
{
    public $timestamps = false;
    protected $fillable = [
        'user_id',
        'target_amount',
        'collected_amount',
        'factory_id',
        'minimum_target',
        'strtup',
        'payout_frequency',
        'profit_percentage',  
        'descrption'
    ];
    public function factory(){
        return $this->belongsTo(factories::class);
    }
    public function investment(){
        return $this->hasMany(investments::class);
    }

}
