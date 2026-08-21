<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class StudentResult extends Model
{
    use HasFactory;

    protected $fillable = [
        'exam_id',
        'dni',
        'full_name',
        'email',
        'career',
        'academic_group',
        'group_label',
        'correct_count',
        'incorrect_count',
        'blank_count',
        'total_score',
        'general_rank',
        'career_rank',
        'group_rank',
        'answers_json',
        'scores_detail_json',
        'submitted_at',
    ];

    protected $casts = [
        'answers_json' => 'array',
        'scores_detail_json' => 'array',
        'total_score' => 'float',
        'correct_count' => 'integer',
        'incorrect_count' => 'integer',
        'blank_count' => 'integer',
        'general_rank' => 'integer',
        'career_rank' => 'integer',
        'group_rank' => 'integer',
        'submitted_at' => 'datetime',
    ];

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class);
    }
}
