<?php

namespace App\Models\Harga;

use App\Models\Master\Log;
use App\Models\Master\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Harga extends Model
{
    use HasFactory;

    protected $table = 'harga';

    protected $fillable = ['id_product', 'tanggal', 'spot', 'inventory'];

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
