<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TransaksiKeuangan extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'transaksi_keuangan';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'tanggal_masehi',
        'tanggal_hijriah',
        'jenis_transaksi',
        'kategori_keuangan_id',
        'keterangan',
        'jumlah',
        'sumber_tujuan_dana',
        'bukti_transaksi_path',
        'nomor_referensi',
        'created_by',
    ];

    /**
     * The attributes that should be cast to native types.
     *
     * @var array
     */
    protected $casts = [
        'tanggal_masehi' => 'date',
    ];

    /**
     * Get the kategori that owns the transaction.
     *
     * @return BelongsTo
     */
    public function kategoriKeuangan(): BelongsTo
    {
        return $this->belongsTo(KategoriKeuangan::class);
    }

    /**
     * Get the user who created the transaction.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}