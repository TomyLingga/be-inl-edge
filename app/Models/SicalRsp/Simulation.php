<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use App\Models\Master\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Simulation extends Model
{
    use HasFactory;

    protected $table = 'simulation';

    protected $fillable = [
        'product_id',
        'name',
        'date',
        'kurs',
        'expected_margin',
        'id_dmo'
    ];

    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function dmo()
    {
        return $this->belongsTo(Dmo::class, 'id_dmo');
    }

    public function offer()
    {
        return $this->hasOne(Offer::class, 'simulation_id');
    }

    public function catatan()
    {
        return $this->hasMany(Catatan::class, 'id_simulation');
    }

    public function cost()
    {
        return $this->hasMany(Cost::class, 'id_simulation');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
