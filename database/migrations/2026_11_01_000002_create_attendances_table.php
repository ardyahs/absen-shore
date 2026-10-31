<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->date('tanggal');
            $table->string('jam', 5);
            $table->integer('selisih_menit');
            $table->string('kategori', 20);      // reward | tepat | terlambat
            $table->integer('nominal');           // + reward, − pinalti
            $table->decimal('lat', 10, 7);
            $table->decimal('lng', 10, 7);
            $table->unsignedInteger('akurasi')->default(0);
            $table->unsignedInteger('jarak');
            $table->string('foto');
            $table->string('status', 20)->default('valid'); // valid | perlu_ditinjau | ditolak
            $table->string('catatan', 200)->nullable();
            $table->foreignId('ditinjau_oleh')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['user_id', 'tanggal']);
            $table->index(['tanggal', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
