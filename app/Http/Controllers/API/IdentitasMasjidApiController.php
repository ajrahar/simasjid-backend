<?php

// File: app/Http/Controllers/API/IdentitasMasjidApiController.php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\IdentitasMasjid;
use App\Http\Resources\IdentitasMasjidResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class IdentitasMasjidApiController extends Controller
{
    /**
     * Display the single resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $identitas = IdentitasMasjid::with('updatedBy')->first();

            if (!$identitas) {
                return response()->json([
                    'status' => 'success',
                    'message' => 'Identitas masjid belum diatur',
                    'data' => null
                ], 200);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data identitas masjid berhasil diambil',
                'data' => new IdentitasMasjidResource($identitas)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function store(Request $request)
    {
        try {
            // Check if a record already exists
            if (IdentitasMasjid::exists()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identitas masjid sudah diatur. Gunakan metode UPDATE untuk mengubah data.',
                ], 409); // 409 Conflict
            }

            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Hanya admin yang dapat menambah data.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_masjid' => 'required|string|max:255',
                'alamat' => 'required|string',
                'no_telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'url_website' => 'nullable|url|max:255',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'deskripsi_singkat' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('logo')) {
                $logoPath = $request->file('logo')->store('masjid-logos', 'public');
                $data['logo_path'] = $logoPath;
                $data['logo_url'] = url(Storage::url($logoPath));
            }

            $data['updated_by'] = Auth::id();

            $identitas = IdentitasMasjid::create($data);
            $identitas->load('updatedBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Identitas masjid berhasil disimpan',
                'data' => new IdentitasMasjidResource($identitas)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request)
    {
        try {
            $identitas = IdentitasMasjid::first();

            if (!$identitas) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Identitas masjid belum diatur. Gunakan metode STORE untuk membuat data.',
                ], 404);
            }
            
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Hanya admin yang dapat mengubah data.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_masjid' => 'required|string|max:255',
                'alamat' => 'required|string',
                'no_telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'url_website' => 'nullable|url|max:255',
                'logo' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048',
                'deskripsi_singkat' => 'nullable|string'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('logo')) {
                // Delete old logo if exists
                if ($identitas->logo_path) {
                    Storage::disk('public')->delete($identitas->logo_path);
                }
                
                $logoFile = $request->file('logo');
                $logoPath = $logoFile->store('masjid-logos', 'public');
                $data['logo_path'] = $logoPath;
                $data['logo_url'] = url(Storage::url($logoPath));
            } elseif ($request->boolean('hapus_logo')) {
                // Remove existing logo
                if ($identitas->logo_path) {
                    Storage::disk('public')->delete($identitas->logo_path);
                    $data['logo_path'] = null;
                    $data['logo_url'] = null;
                }
            }

            $data['updated_by'] = Auth::id();

            $identitas->update($data);
            $identitas->load('updatedBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Identitas masjid berhasil diperbarui',
                'data' => new IdentitasMasjidResource($identitas)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}