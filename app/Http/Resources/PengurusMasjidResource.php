<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class PengurusMasjidResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'nama_pengurus' => $this->nama_pengurus,
            'jabatan' => [
                'id' => $this->jabatan->id,
                'nama_jabatan' => $this->jabatan->nama_jabatan,
            ],
            'no_telepon' => $this->no_telepon,
            'email' => $this->email,
            'alamat' => $this->alamat,
            'created_by' => [
                'id' => $this->createdBy->id,
                'name' => $this->createdBy->name,
            ],
            'created_at' => $this->created_at->format('Y-m-d H:i:s'),
            'updated_at' => $this->updated_at->format('Y-m-d H:i:s'),
        ];
    }
}