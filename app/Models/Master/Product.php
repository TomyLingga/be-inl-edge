<?php

namespace App\Models\Master;

use App\Models\LevyReuter\LevyDuty;
use App\Models\LevyReuter\MarketReuters;
use App\Models\Penjualan\TargetPenjualan;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Product extends Model
{
    use HasFactory;

    protected $table = 'product';

    protected $fillable = ['name', 'jenis', 'konversi_ton', 'konversi_pallet', 'konversi_pouch'];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Relation to LevyDuty
     */
    public function levyDuties()
    {
        return $this->hasMany(LevyDuty::class, 'id_bulky');
    }

    public function targetPenjualan()
    {
        return $this->hasMany(TargetPenjualan::class, 'product_id');
    }

    /**
     * Relation to MarketRouters
     */
    public function marketRouters()
    {
        return $this->hasMany(MarketReuters::class, 'id_bulky');
    }

    /**
     * Polymorphic relation to logs
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
