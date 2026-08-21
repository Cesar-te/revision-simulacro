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
        Schema::create('careers', function (Blueprint $table) {
            $table->id();
            $table->string('name')->unique();
            $table->string('academic_group', 10); // 'A', 'BCD', 'EF' o 'B', 'C', 'D', 'E', 'F'
            $table->string('group_label'); // 'Biomédicas', 'Letras / Sociales', 'Ingenierías'
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('careers');
    }
};
