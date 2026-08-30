<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiItem extends Model
{
    use HasFactory;

    protected $table = 'transaksi_items';

    protected $fillable = [
        'analysis_run_id',
        'nomor_faktur',
        'tanggal',
        'nama_barang',
    ];

    protected $casts = [
        'tanggal' => 'date',
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}
