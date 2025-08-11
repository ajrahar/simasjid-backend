<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Jabatan;
use App\Http\Resources\JabatanResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class JabatanApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\JsonResponse
     */
    public function index()
    {
        try {
            $jabatans = Jabatan::all();
            return response()->json([
                'status' => 'success',
                'message' => 'Data jabatan berhasil diambil',
                'data' => JabatanResource::collection($jabatans)
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
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Hanya admin yang dapat menambah data.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan',
                'deskripsi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $jabatan = Jabatan::create($validator->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Data jabatan berhasil disimpan',
                'data' => new JabatanResource($jabatan)
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
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function update(Request $request, $id)
    {
        try {
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Hanya admin yang dapat mengubah data.',
                ], 403);
            }

            $jabatan = Jabatan::find($id);

            if (!$jabatan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data jabatan tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_jabatan' => 'required|string|max:255|unique:jabatans,nama_jabatan,' . $id,
                'deskripsi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $jabatan->update($validator->validated());

            return response()->json([
                'status' => 'success',
                'message' => 'Data jabatan berhasil diperbarui',
                'data' => new JabatanResource($jabatan)
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function destroy($id)
    {
        try {
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Hanya admin yang dapat menghapus data.',
                ], 403);
            }

            $jabatan = Jabatan::find($id);

            if (!$jabatan) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data jabatan tidak ditemukan',
                ], 404);
            }

            $jabatan->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data jabatan berhasil dihapus',
            ], 200);
        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}