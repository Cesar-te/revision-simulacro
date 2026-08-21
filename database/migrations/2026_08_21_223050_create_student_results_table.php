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
        Schema::create('student_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->string('dni', 20)->nullable()->index();
            $table->string('full_name')->index();
            $table->string('email')->nullable();
            $table->string('career')->index();
            $table->string('academic_group', 10)->index(); // 'A', 'BCD', 'EF'
            $table->string('group_label')->nullable(); // 'Biomédicas', 'Letras', 'Ingenierías'
            $table->integer('correct_count')->default(0);
            $table->integer('incorrect_count')->default(0);
            $table->integer('blank_count')->default(0);
            $table->decimal('total_score', 10, 4)->default(0.0000);
            $table->integer('general_rank')->nullable()->index();
            $table->integer('career_rank')->nullable()->index();
            $table->integer('group_rank')->nullable()->index();
            $table->json('answers_json')->nullable();
            $table->json('scores_detail_json')->nullable();
            $table->timestamp('submitted_at')->nullable();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('student_results');
    }
};
