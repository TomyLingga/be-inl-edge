<?php

namespace App\Models\LevyReuter;

use App\Models\Kurs\MataUang;
use App\Models\Master\Log;
use App\Models\Master\Product;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LevyDuty extends Model
{
    use HasFactory;

    protected $table = 'levy_duty';

    protected $fillable = ['id_bulky', 'tanggal', 'nilai', 'id_mata_uang'];

    /**
     * Relation to Product
     */
    public function product()
    {
        return $this->belongsTo(Product::class, 'id_bulky');
    }

    /**
     * Relation to MataUang
     */
    public function mataUang()
    {
        return $this->belongsTo(MataUang::class, 'id_mata_uang');
    }

    /**
     * Polymorphic relation to logs
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
