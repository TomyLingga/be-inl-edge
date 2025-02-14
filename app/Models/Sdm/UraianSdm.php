<?php

namespace App\Models\Sdm;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class UraianSdm extends Model
{
    use HasFactory;

    protected $primaryKey = 'id';

    protected $table = 'uraian_sdm';

    protected $fillable = [
        'name'
    ];

    protected $hidden = ['created_at', 'updated_at'];

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
