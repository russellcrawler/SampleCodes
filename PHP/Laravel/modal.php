<?php

namespace MTC;

use Illuminate\Database\Eloquent\Model;

class Price extends Model
{
   protected $table = "mtc_vehicle_price";
   protected $fillable = ['vehicle_id', 'city', 'price_for','price_per_km','price_per_day'];  
}
