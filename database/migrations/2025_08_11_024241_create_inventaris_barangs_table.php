<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     *
     * @return void
     */
    public function up(): void
    {
        Schema::create('inventaris_barangs', function (Blueprint $table) {
            $table->id();
            $table->string('nama_barang');
            // Menunjuk secara eksplisit ke tabel 'kategori_barang' (tunggal)
            $table->foreignId('kategori_id')->constrained('kategori_barang')->cascadeOnDelete();
            $table->date('tanggal_masuk');
            $table->integer('jumlah');
            $table->string('kondisi'); // 'Baik', 'Rusak Ringan', 'Rusak Berat'
            $table->text('deskripsi')->nullable();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     *
     * @return void
     */
    public function down(): void
    {
        Schema::dropIfExists('inventaris_masjids');
    }
};
