<?php

namespace App\Models\Profitablity;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class KategoriProfitablity extends Model
{
    use HasFactory;

    protected $table = 'kategori_profitablity';

    protected $fillable = ['name'];

    protected $hidden = ['created_at', 'updated_at'];

    /**
     * Define the one-to-many relationship with Profitablity.
     */
    public function profitabilities()
    {
        return $this->hasMany(Profitablity::class, 'kategori_id');
    }

    /**
     * Define the polymorphic relationship with logs.
     */
    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
