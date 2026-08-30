<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssociationRule extends Model
{
    use HasFactory;

    protected $table = 'association_rules';

    protected $fillable = [
        'analysis_run_id',
        'antecedent',
        'consequent',
        'support',
        'confidence',
        'lift',
    ];

    protected $casts = [
        'support' => 'decimal:6',
        'confidence' => 'decimal:6',
        'lift' => 'decimal:6',
    ];

    public function analysisRun(): BelongsTo
    {
        return $this->belongsTo(AnalysisRun::class);
    }
}
