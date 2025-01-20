<?php

namespace App\Models\Packaging;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class TargetPackagingUraian extends Model
{
    use HasFactory;

    protected $table = 'target_packaging_uraian';

    protected $fillable = [
        'nama',
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function targetPackaging()
    {
        return $this->hasMany(TargetPackaging::class, 'uraian_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
