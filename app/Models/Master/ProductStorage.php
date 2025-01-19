<?php

namespace App\Models\Master;

use App\Models\Stock\StockBulk;
use App\Models\Stock\StockCpo;
use App\Models\Stock\StockRetail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ProductStorage extends Model
{
    use HasFactory;

    protected $table = 'product_storage';

    protected $fillable = ['name', 'id_lokasi', 'kapasitas', 'jenis'];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Relation to Lokasi
     */
    public function lokasi()
    {
        return $this->belongsTo(Lokasi::class, 'id_lokasi');
    }

    /**
     * Relation to StokCpo
     */
    public function stokCpo()
    {
        return $this->hasMany(StockCpo::class, 'tanki_id');
    }

    /**
     * Relation to StokBulk
     */
    public function stokBulk()
    {
        return $this->hasMany(StockBulk::class, 'tanki_id');
    }

    /**
     * Relation to StokRitel
     */
    public function stokRitel()
    {
        return $this->hasMany(StockRetail::class, 'warehouse_id');
    }

    /**
     * Polymorphic relation to logs
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
