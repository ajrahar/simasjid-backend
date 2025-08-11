<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\PengurusMasjid;
use App\Http\Resources\PengurusMasjidResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class PengurusMasjidApiController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\JsonResponse
     */
    public function index(Request $request)
    {
        try {
            $query = PengurusMasjid::with(['jabatan', 'createdBy']);

            if ($request->has('search')) {
                $search = $request->search;
                $query->where('nama_pengurus', 'like', '%' . $search . '%');
            }

            if ($request->has('jabatan_id')) {
                $query->where('jabatan_id', $request->jabatan_id);
            }

            $sortBy = $request->get('sort_by', 'nama_pengurus');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $pengurus = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data pengurus masjid berhasil diambil',
                'data' => PengurusMasjidResource::collection($pengurus),
                'pagination' => [
                    'current_page' => $pengurus->currentPage(),
                    'last_page' => $pengurus->lastPage(),
                    'per_page' => $pengurus->perPage(),
                    'total' => $pengurus->total(),
                    'from' => $pengurus->firstItem(),
                    'to' => $pengurus->lastItem(),
                ]
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
                'nama_pengurus' => 'required|string|max:255',
                'jabatan_id' => 'required|exists:jabatans,id',
                'no_telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'alamat' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $data['created_by'] = Auth::id();

            $pengurus = PengurusMasjid::create($data);
            $pengurus->load(['jabatan', 'createdBy']);

            return response()->json([
                'status' => 'success',
                'message' => 'Data pengurus berhasil disimpan',
                'data' => new PengurusMasjidResource($pengurus)
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
     * Display the specified resource.
     *
     * @param  string  $id
     * @return \Illuminate\Http\JsonResponse
     */
    public function show($id)
    {
        try {
            $pengurus = PengurusMasjid::with(['jabatan', 'createdBy'])->find($id);

            if (!$pengurus) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pengurus tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data pengurus berhasil diambil',
                'data' => new PengurusMasjidResource($pengurus)
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

            $pengurus = PengurusMasjid::find($id);

            if (!$pengurus) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pengurus tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_pengurus' => 'required|string|max:255',
                'jabatan_id' => 'required|exists:jabatans,id',
                'no_telepon' => 'nullable|string|max:20',
                'email' => 'nullable|email|max:255',
                'alamat' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $pengurus->update($validator->validated());
            $pengurus->load(['jabatan', 'createdBy']);

            return response()->json([
                'status' => 'success',
                'message' => 'Data pengurus berhasil diperbarui',
                'data' => new PengurusMasjidResource($pengurus)
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

            $pengurus = PengurusMasjid::find($id);

            if (!$pengurus) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data pengurus tidak ditemukan',
                ], 404);
            }

            $pengurus->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data pengurus berhasil dihapus',
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
