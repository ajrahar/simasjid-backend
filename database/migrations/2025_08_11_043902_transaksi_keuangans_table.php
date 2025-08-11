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
        Schema::create('transaksi_keuangan', function (Blueprint $table) {
            $table->id();
            $table->date('tanggal_masehi');
            $table->string('tanggal_hijriah');
            $table->string('jenis_transaksi'); // pemasukan atau pengeluaran
            $table->foreignId('kategori_keuangans_id')->constrained('kategori_keuangans')->cascadeOnDelete();
            $table->text('keterangan');
            $table->double('jumlah', 15, 2);
            $table->string('sumber_tujuan_dana');
            $table->string('bukti_transaksi_path')->nullable();
            $table->string('nomor_referensi')->nullable();
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
        Schema::dropIfExists('transaksi_keuangan');
    }
};