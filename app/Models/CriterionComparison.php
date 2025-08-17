<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CriterionComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'criterion_left_id',
        'criterion_right_id',
        'value',
    ];

    public function criterionLeft(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_left_id');
    }

    public function criterionRight(): BelongsTo
    {
        return $this->belongsTo(Criterion::class, 'criterion_right_id');
    }
}
