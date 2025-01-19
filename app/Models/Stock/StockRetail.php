<?php

namespace App\Models\Stock;

use App\Models\Master\Log;
use App\Models\Master\Product;
use App\Models\Master\ProductStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockRetail extends Model
{
    use HasFactory;

    protected $table = 'stok_ritel';

    protected $fillable = ['id_ritel', 'warehouse_id', 'tanggal', 'qty', 'umur', 'remarks'];

    /**
     * Relation to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'id_ritel');
    }

    /**
     * Relation to ProductStorage
     */
    public function warehouse()
    {
        return $this->belongsTo(ProductStorage::class, 'warehouse_id');
    }

    /**
     * Polymorphic relation to logs
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
