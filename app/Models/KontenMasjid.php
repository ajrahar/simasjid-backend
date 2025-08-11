<?php

// File: app/Models/KontenMasjid.php
// Model untuk KontenMasjid

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class KontenMasjid extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'konten_masjids';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'judul',
        'slug',
        'jenis_konten', // 'artikel', 'gambar', 'video'
        'konten_teks',
        'file_path',
        'status', // 'draft', 'publish'
        'created_by',
    ];

    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        // Automatically set the slug when creating or updating
        static::creating(function ($konten) {
            $konten->slug = Str::slug($konten->judul);
        });

        static::updating(function ($konten) {
            $konten->slug = Str::slug($konten->judul);
        });
    }

    /**
     * Get the user who created the content.
     *
     * @return BelongsTo
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}