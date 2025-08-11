<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\Muadzin;
use App\Http\Resources\MuadzinResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class MuadzinApiController extends Controller
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
            $query = Muadzin::with('createdBy');

            if ($request->has('search')) {
                $search = $request->search;
                $query->where('nama_muadzin', 'like', '%' . $search . '%');
            }

            $sortBy = $request->get('sort_by', 'nama_muadzin');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            $perPage = $request->get('per_page', 15);
            $muadzins = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data muadzin berhasil diambil',
                'data' => MuadzinResource::collection($muadzins),
                'pagination' => [
                    'current_page' => $muadzins->currentPage(),
                    'last_page' => $muadzins->lastPage(),
                    'per_page' => $muadzins->perPage(),
                    'total' => $muadzins->total(),
                    'from' => $muadzins->firstItem(),
                    'to' => $muadzins->lastItem(),
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
                'nama_muadzin' => 'required|string|max:255',
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

            $muadzin = Muadzin::create($data);
            $muadzin->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Data muadzin berhasil disimpan',
                'data' => new MuadzinResource($muadzin)
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
            $muadzin = Muadzin::with('createdBy')->find($id);

            if (!$muadzin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data muadzin tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data muadzin berhasil diambil',
                'data' => new MuadzinResource($muadzin)
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

            $muadzin = Muadzin::find($id);

            if (!$muadzin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data muadzin tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_muadzin' => 'required|string|max:255',
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

            $muadzin->update($validator->validated());
            $muadzin->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Data muadzin berhasil diperbarui',
                'data' => new MuadzinResource($muadzin)
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

            $muadzin = Muadzin::find($id);

            if (!$muadzin) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data muadzin tidak ditemukan',
                ], 404);
            }

            $muadzin->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data muadzin berhasil dihapus',
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