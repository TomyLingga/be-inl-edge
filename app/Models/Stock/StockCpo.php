<?php

namespace App\Models\Stock;

use App\Models\Master\Log;
use App\Models\Master\ProductStorage;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class StockCpo extends Model
{
    use HasFactory;

    protected $table = 'stok_cpo';

    protected $fillable = ['tanki_id', 'tanggal', 'qty', 'umur', 'remarks'];

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
