<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('label_note', function (Blueprint $table) {
            $table->id();
            $table->foreignId('label_id')->constrained()->onDelete('cascade');
            $table->foreignId('note_id')->constrained()->onDelete('cascade');
            $table->timestamps();

            $table->unique(['label_id', 'note_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('label_note');
    }
};
