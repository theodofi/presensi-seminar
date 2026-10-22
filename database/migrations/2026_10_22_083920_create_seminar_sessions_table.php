<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('seminar_sessions', function (Blueprint $table) {
            $table->id();
            $table->unsignedTinyInteger('number');
            $table->string('name');
            $table->dateTime('opens_at')->nullable();
            $table->dateTime('closes_at')->nullable();
            // auto = ikut jam, open = paksa buka, closed = paksa tutup
            $table->string('mode', 10)->default('auto');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('seminar_sessions');
    }
};
