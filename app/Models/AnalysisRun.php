<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AnalysisRun extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'nama_file_upload',
        'periode_awal',
        'periode_akhir',
        'min_support',
        'max_len',
        'min_confidence',
        'total_baris_raw',
        'total_baris_clean',
        'total_faktur_unik',
        'total_produk_unik',
        'total_frequent_itemsets',
        'total_association_rules',
        'status',
    ];

    protected $casts = [
        'periode_awal' => 'date',
        'periode_akhir' => 'date',
        'min_support' => 'decimal:4',
        'min_confidence' => 'decimal:4',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function transaksiItems(): HasMany
    {
        return $this->hasMany(TransaksiItem::class);
    }

    public function frequentItemsets(): HasMany
    {
        return $this->hasMany(FrequentItemset::class);
    }

    public function associationRules(): HasMany
    {
        return $this->hasMany(AssociationRule::class);
    }
}
