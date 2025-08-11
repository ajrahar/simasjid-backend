<?php

// File: app/Models/InventarisBarang.php
// Pastikan model ini berada di namespace App\Models

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class InventarisBarang extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'inventaris_barangs';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama_barang',
        'kategori_id',
        'tanggal_masuk',
        'jumlah',
        'kondisi',
        'deskripsi',
        'gambar_path',
        'created_by',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array
     */
    protected $casts = [
        'tanggal_masuk' => 'date',
    ];

    /**
     * Get the kategori that owns the inventaris barang.
     *
     * @return BelongsTo
     */
    public function kategori(): BelongsTo
    {
        return $this->belongsTo(KategoriBarang::class);
    }

    /**
     * Get the user who created the inventaris barang.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}