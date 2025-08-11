<?php

// File: app/Models/IdentitasMasjid.php
// Model untuk IdentitasMasjid

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class IdentitasMasjid extends Model
{
    use HasFactory;

    /**
     * The table associated with the model.
     *
     * @var string
     */
    protected $table = 'identitas_masjids';

    /**
     * The attributes that are mass assignable.
     *
     * @var array
     */
    protected $fillable = [
        'nama_masjid',
        'alamat',
        'no_telepon',
        'email',
        'url_website',
        'logo_url',
        'logo_path',
        'deskripsi_singkat',
        'updated_by',
    ];

    /**
     * Get the user who last updated the model.
     *
     * @return BelongsTo
     */
    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}