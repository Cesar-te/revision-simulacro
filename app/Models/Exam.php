<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Exam extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'description',
        'incorrect_penalty',
        'blank_score',
        'total_questions',
        'status',
    ];

    protected $casts = [
        'incorrect_penalty' => 'float',
        'blank_score' => 'float',
        'total_questions' => 'integer',
    ];

    public function answerKeys(): HasMany
    {
        return $this->hasMany(ExamAnswerKey::class)->orderBy('question_number');
    }

    public function studentResults(): HasMany
    {
        return $this->hasMany(StudentResult::class)->orderBy('general_rank');
    }

    public function scoringRules(): HasMany
    {
        return $this->hasMany(ExamScoringRule::class)
            ->orderBy('academic_group')
            ->orderBy('category');
    }
}
