<?php

namespace App\Models\Partner;

use App\Models\Master\Log;
use App\Models\OutstandingCpo\OutstandingCpo;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Supplier extends Model
{
    use HasFactory;

    protected $table = 'supplier';

    protected $fillable = [
        'name',
        'email',
        'kontak',
        'negara',
        'provinsi',
        'kota',
        'alamat',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Define the one-to-many relationship with OutstandingCpo.
     */
    public function outstandingCpos()
    {
        return $this->hasMany(OutstandingCpo::class, 'supplier_id');
    }

    /**
     * Define the polymorphic relationship with logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
