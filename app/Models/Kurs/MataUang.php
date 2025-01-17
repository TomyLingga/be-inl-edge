<?php

namespace App\Models\Kurs;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MataUang extends Model
{
    use HasFactory;

    protected $table = 'mata_uang';

    protected $fillable = [
        'name',
        'symbol',
        'remark',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function kurs()
    {
        return $this->hasMany(Kurs::class, 'id_mata_uang');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
