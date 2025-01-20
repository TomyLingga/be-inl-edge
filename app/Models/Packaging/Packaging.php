<?php

namespace App\Models\Packaging;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Packaging extends Model
{
    use HasFactory;

    protected $table = 'packaging';

    protected $fillable = [
        'nama',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function targetPackaging()
    {
        return $this->hasMany(TargetPackaging::class, 'packaging_id');
    }

    public function laporanPackaging()
    {
        return $this->hasMany(LaporanPackaging::class, 'packaging_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
