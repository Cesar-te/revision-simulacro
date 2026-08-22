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

    /**
     * Retorna el puntaje acumulado por cada asignatura (HV, HM, ARIT, etc.)
     *
     * @return array<string, float>
     */
    public function getSubjectScoresAttribute(): array
    {
        $scores = [];
        /** @var \App\Services\ScoringService $scoringService */
        $scoringService = app(\App\Services\ScoringService::class);

        foreach (\App\Services\ScoringService::SUBJECT_COLUMNS as $code => $info) {
            $scores[$code] = 0.0;
        }

        if (is_array($this->scores_detail_json)) {
            foreach ($this->scores_detail_json as $detail) {
                $sub = $detail['subject'] ?? 'General';
                $code = $scoringService->getSubjectCode($sub);
                $scores[$code] = ($scores[$code] ?? 0.0) + (float) ($detail['points'] ?? 0.0);
            }
        }

        foreach ($scores as $code => $val) {
            $scores[$code] = round($val, 4);
        }

        return $scores;
    }
}
