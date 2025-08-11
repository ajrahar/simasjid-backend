<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Khatib;
use App\Http\Resources\KhatibResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class KhatibApiController extends Controller
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
            $query = Khatib::with('createdBy');

            if ($request->has('search')) {
                $search = $request->search;
                $query->where('nama_khatib', 'like', '%' . $search . '%');
            }

            $sortBy = $request->get('sort_by', 'nama_khatib');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $khatibs = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data khatib berhasil diambil',
                'data' => KhatibResource::collection($khatibs),
                'pagination' => [
                    'current_page' => $khatibs->currentPage(),
                    'last_page' => $khatibs->lastPage(),
                    'per_page' => $khatibs->perPage(),
                    'total' => $khatibs->total(),
                    'from' => $khatibs->firstItem(),
                    'to' => $khatibs->lastItem(),
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
                'nama_khatib' => 'required|string|max:255',
                'no_telepon' => 'nullable|string|max:20',
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

            $khatib = Khatib::create($data);
            $khatib->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Data khatib berhasil disimpan',
                'data' => new KhatibResource($khatib)
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
            $khatib = Khatib::with('createdBy')->find($id);

            if (!$khatib) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data khatib tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data khatib berhasil diambil',
                'data' => new KhatibResource($khatib)
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

            $khatib = Khatib::find($id);

            if (!$khatib) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data khatib tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_khatib' => 'required|string|max:255',
                'no_telepon' => 'nullable|string|max:20',
                'alamat' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $khatib->update($validator->validated());
            $khatib->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Data khatib berhasil diperbarui',
                'data' => new KhatibResource($khatib)
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

            $khatib = Khatib::find($id);

            if (!$khatib) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data khatib tidak ditemukan',
                ], 404);
            }

            $khatib->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data khatib berhasil dihapus',
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