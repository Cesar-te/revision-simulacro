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
        Schema::create('exam_answer_keys', function (Blueprint $table) {
            $table->id();
            $table->foreignId('exam_id')->constrained('exams')->cascadeOnDelete();
            $table->integer('question_number');
            $table->string('subject')->nullable(); // e.g. Habilidad Verbal, Habilidad Matemática, etc.
            $table->string('correct_key', 5); // 'A', 'B', 'C', 'D', 'E', or '*'
            $table->text('explanation')->nullable();
            $table->boolean('is_annulled')->default(false); // Si la pregunta es anulada
            $table->timestamps();

            $table->unique(['exam_id', 'question_number']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_answer_keys');
    }
};
