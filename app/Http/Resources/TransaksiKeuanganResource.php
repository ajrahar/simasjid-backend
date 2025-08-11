<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class TransaksiKeuanganResource extends JsonResource
{
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'tanggal_masehi' => $this->tanggal_masehi->format('Y-m-d'),
            'tanggal_hijriah' => $this->tanggal_hijriah,
            'jenis_transaksi' => $this->jenis_transaksi,
            'kategori_keuangan' => [
                'id' => $this->kategoriKeuangan->id,
                'nama_kategori' => $this->kategoriKeuangan->nama_kategori,
                'jenis_kategori' => $this->kategoriKeuangan->jenis_kategori,
            ],
            'keterangan' => $this->keterangan,
            'jumlah' => (float) $this->jumlah,
            'jumlah_formatted' => 'Rp ' . number_format($this->jumlah, 0, ',', '.'),
            'sumber_tujuan_dana' => $this->sumber_tujuan_dana,
            'bukti_transaksi_url' => $this->bukti_transaksi_path ? asset('storage/' . $this->bukti_transaksi_path) : null,
            'nomor_referensi' => $this->nomor_referensi,
            'created_by' => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}