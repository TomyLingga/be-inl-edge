<?php

namespace App\Models\Master;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Lokasi extends Model
{
    use HasFactory;

    protected $table = 'lokasi';

    protected $fillable = ['name'];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Relation to ProductStorage
     */
    public function productStorages()
    {
        return $this->hasMany(ProductStorage::class, 'id_lokasi');
    }

    /**
     * Polymorphic relation to logs
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
