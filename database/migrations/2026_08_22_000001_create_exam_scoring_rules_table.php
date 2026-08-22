<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('exam_scoring_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->string('academic_group', 10)->index();
            $table->string('category', 30);
            $table->decimal('points_correct', 10, 4);
            $table->timestamps();

            $table->unique(['exam_id', 'academic_group', 'category'], 'exam_scoring_rule_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_scoring_rules');
    }
};
