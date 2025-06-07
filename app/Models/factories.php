<?php

namespace App\Models;

use App\factoryStatus;
use Illuminate\Database\Eloquent\Model;

class factories extends Model
{
    protected $fillable = [
        'name',
        'address',
        'feasibility_pdf',
        'user_id',
        'category_id',
        'is_active',
    ];
    public function category(){
        return $this->belongsTo(categories::class);
    }
        public function user(){
        return $this->belongsTo(User::class);
    }
        public function opprtunities(){
        return $this->hasMany(investment_opprtunities::class);
    }

            public function images(){
        return $this->hasMany(opprtunity_images::class);
    }
    protected $casts = [
        'status' => factoryStatus::class,
    ];
}
