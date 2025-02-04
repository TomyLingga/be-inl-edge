<?php

namespace App\Models\Harga;

use App\Models\Master\Log;
use App\Models\Master\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class HargaSpot extends Model
{
    use HasFactory;

    protected $table = 'harga_spot';

    protected $fillable = ['id_product', 'tanggal', 'spot'];

    /**
     * Relation to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'id_product');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
