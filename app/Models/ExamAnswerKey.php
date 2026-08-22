<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ExamAnswerKey extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'academic_group',
        'question_number',
        'subject',
        'correct_key',
        'explanation',
        'is_annulled',
    ];

    protected $casts = [
        'question_number' => 'integer',
        'is_annulled' => 'boolean',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
