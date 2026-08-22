<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamScoringRule extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'academic_group',
        'category',
        'points_correct',
    ];

    protected $casts = [
        'points_correct' => 'float',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
