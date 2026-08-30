<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class FrequentItemset extends Model
{
    use HasFactory;

    protected $table = 'frequent_itemsets';

    protected $fillable = [
        'analysis_run_id',
        'itemset',
        'length',
        'support',
    ];

    protected $casts = [
        'support' => 'decimal:6',
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}
