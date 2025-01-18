<?php

namespace App\Models\OutstandingCpo;

use App\Models\Master\Log;
use App\Models\Partner\Supplier;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class OutstandingCpo extends Model
{
    use HasFactory;

    protected $table = 'outstanding_cpo';

    protected $fillable = [
        'kontrak',
        'supplier_id',
        'qty',
        'harga',
        'status',
    ];


    /**
     * Define the inverse one-to-many relationship with Supplier.
     */
    public function supplier()
    {
        return $this->belongsTo(Supplier::class, 'supplier_id');
    }

    /**
     * Define the polymorphic relationship with logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
