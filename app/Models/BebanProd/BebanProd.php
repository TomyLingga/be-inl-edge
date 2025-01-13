<?php

namespace App\Models\BebanProd;

use App\Models\Master\Log;
use App\Models\Master\Pmg;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Notifications\Notifiable;

class BebanProd extends Model
{
    use HasFactory, Notifiable;

    protected $primaryKey = 'id';

    protected $table = 'beban_prod';

    protected $fillable = [
        'uraian_id',
        'pmg_id',
        'tanggal',
        'value'
    ];

    public function uraian()
    {
        return $this->belongsTo(BebanProdUraian::class, 'uraian_id');
    }

    public function pmg()
    {
        return $this->belongsTo(Pmg::class, 'pmg_id');
    }

    public function logs()
    {
        return $this->morphMany(Log::class, 'model');
    }
}
