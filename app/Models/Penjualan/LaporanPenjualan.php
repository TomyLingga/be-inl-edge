<?php

namespace App\Models\Penjualan;

use App\Models\Master\Log;
use App\Models\Master\Product;
use App\Models\Partner\Customer;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LaporanPenjualan extends Model
{
    use HasFactory;

    protected $table = 'laporan_penjualan';

    protected $fillable = [
        'product_id',
        'customer_id',
        'tanggal',
        'qty',
        'harga_satuan',
        'margin_percent',
        'kontrak'
    ];

    /**
     * Relation to Product.
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'product_id');
    }

    /**
     * Relation to Customer.
     */
    public function customer()
    {
        return $this->belongsTo(Customer::class, 'customer_id');
    }

    /**
     * Polymorphic relation to logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
