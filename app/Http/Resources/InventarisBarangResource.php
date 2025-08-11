<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class InventarisBarangResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_barang' => $this->nama_barang,
            'kategori' => [
                'id' => $this->kategori->id,
                'nama_kategori' => $this->kategori->nama_kategori,
            ],
            'tanggal_masuk' => $this->tanggal_masuk->format('Y-m-d'),
            'jumlah' => $this->jumlah,
            'kondisi' => $this->kondisi,
            'deskripsi' => $this->deskripsi,
            'gambar_url' => $this->gambar_path ? asset('storage/' . $this->gambar_path) : null,
            'created_by' => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}