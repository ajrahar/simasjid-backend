<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\AgendaMasjid;
use App\Http\Resources\AgendaMasjidResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AgendaMasjidApiController extends Controller
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
            $query = AgendaMasjid::with('createdBy');

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('nama_kegiatan', 'like', '%' . $search . '%');
            }

            // Filter by tanggal
            if ($request->has('tanggal_dari')) {
                $query->whereDate('tanggal', '>=', $request->tanggal_dari);
            }

            if ($request->has('tanggal_sampai')) {
                $query->whereDate('tanggal', '<=', $request->tanggal_sampai);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'tanggal');
            $sortOrder = $request->get('sort_order', 'asc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $agendas = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data agenda berhasil diambil',
                'data' => AgendaMasjidResource::collection($agendas),
                'pagination' => [
                    'current_page' => $agendas->currentPage(),
                    'last_page' => $agendas->lastPage(),
                    'per_page' => $agendas->perPage(),
                    'total' => $agendas->total(),
                    'from' => $agendas->firstItem(),
                    'to' => $agendas->lastItem(),
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
            // Check if user is admin
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Hanya admin yang dapat menambah data.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'nama_kegiatan' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'waktu' => 'required|date_format:H:i:s',
                'deskripsi' => 'nullable|string',
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

            $agenda = AgendaMasjid::create($data);
            $agenda->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Data agenda berhasil disimpan',
                'data' => new AgendaMasjidResource($agenda)
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
            $agenda = AgendaMasjid::with('createdBy')->find($id);

            if (!$agenda) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data agenda tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data agenda berhasil diambil',
                'data' => new AgendaMasjidResource($agenda)
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
            // Check if user is admin
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Hanya admin yang dapat mengubah data.',
                ], 403);
            }

            $agenda = AgendaMasjid::find($id);

            if (!$agenda) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data agenda tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_kegiatan' => 'required|string|max:255',
                'tanggal' => 'required|date',
                'waktu' => 'required|date_format:H:i:s',
                'deskripsi' => 'nullable|string',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $agenda->update($validator->validated());
            $agenda->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Data agenda berhasil diperbarui',
                'data' => new AgendaMasjidResource($agenda)
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
            // Check if user is admin
            if (Auth::user()->role !== 'admin') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Hanya admin yang dapat menghapus data.',
                ], 403);
            }

            $agenda = AgendaMasjid::find($id);

            if (!$agenda) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data agenda tidak ditemukan',
                ], 404);
            }

            $agenda->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data agenda berhasil dihapus',
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