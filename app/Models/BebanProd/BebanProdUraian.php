<?php

namespace App\Models\BebanProd;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class BebanProdUraian extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $table = 'beban_prod_uraian';

    protected $fillable = [
        'nama'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function costProd()
    {
        return $this->hasMany(BebanProd::class, 'uraian_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
