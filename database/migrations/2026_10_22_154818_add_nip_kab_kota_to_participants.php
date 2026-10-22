<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->renameColumn('institution', 'kab_kota');
            $table->string('nip', 32)->nullable()->after('name')->index();
        });
    }

    public function down(): void
    {
        Schema::table('participants', function (Blueprint $table) {
            $table->dropColumn('nip');
            $table->renameColumn('kab_kota', 'institution');
        });
    }
};
