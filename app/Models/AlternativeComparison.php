<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AlternativeComparison extends Model
{
    use HasFactory;

    protected $fillable = [
        'criterion_id',
        'alternative_left_id',
        'alternative_right_id',
        'value',
    ];

    public function criterion(): BelongsTo
    {
        return $this->belongsTo(Criterion::class);
    }

    public function alternativeLeft(): BelongsTo
    {
        return $this->belongsTo(Alternative::class, 'alternative_left_id');
    }

    public function alternativeRight(): BelongsTo
    {
        return $this->belongsTo(Alternative::class, 'alternative_right_id');
    }
}
