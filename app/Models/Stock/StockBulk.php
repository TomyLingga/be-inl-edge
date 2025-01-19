<?php

namespace App\Models\Stock;

use App\Models\Master\Log;
use App\Models\Master\Product;
use App\Models\Master\ProductStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockBulk extends Model
{
    use HasFactory;

    protected $table = 'stok_bulk';

    protected $fillable = ['id_bulky', 'tanki_id', 'tanggal', 'qty', 'umur', 'remarks'];

    /**
     * Relation to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'id_bulky');
    }

    /**
     * Relation to ProductStorage
     */
    public function tanki()
    {
        return $this->belongsTo(ProductStorage::class, 'tanki_id');
    }

    /**
     * Polymorphic relation to logs
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
