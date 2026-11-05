<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/** Absen manual oleh admin tidak punya foto dan GPS. */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->decimal('lat', 10, 7)->nullable()->change();
            $table->decimal('lng', 10, 7)->nullable()->change();
            $table->unsignedInteger('jarak')->nullable()->change();
            $table->string('foto')->nullable()->change();
            $table->foreignId('dibuat_oleh')->nullable()->after('ditinjau_oleh')->constrained('users')->nullOnDelete(); // terisi = absen manual
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropConstrainedForeignId('dibuat_oleh');
        });
    }
};
