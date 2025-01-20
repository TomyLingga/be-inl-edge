<?php

namespace App\Models\Penjualan;

use App\Models\Master\Log;
use App\Models\Master\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetPenjualan extends Model
{
    use HasFactory;

    protected $table = 'target_penjualan';

    protected $fillable = ['product_id', 'uraian_id', 'qty', 'tanggal'];

    /**
     * Relation to Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    public function uraian()
    {
        return $this->belongsTo(TargetPenjualanUraian::class, 'uraian_id');
    }

    /**
     * Polymorphic relation to logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
