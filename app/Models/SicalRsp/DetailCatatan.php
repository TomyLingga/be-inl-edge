<?php

namespace App\Models\SicalRsp;

use App\Models\Master\Log;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class DetailCatatan extends Model
{
    use HasFactory;

    protected $table = 'detail_catatan';

    protected $fillable = [
        'id_catatan',
        'teks'
    ];

    public function catatan()
    {
        return $this->belongsTo(Catatan::class, 'id_catatan');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
