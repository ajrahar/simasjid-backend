<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\InventarisBarang;
use App\Models\KategoriBarang;
use App\Http\Resources\InventarisBarangResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class InventarisBarangApiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = InventarisBarang::with(['kategori', 'createdBy']);

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('nama_barang', 'like', '%' . $search . '%');
            }

            // Filter by kategori
            if ($request->has('kategori_id')) {
                $query->where('kategori_id', $request->kategori_id);
            }

            // Filter by kondisi
            if ($request->has('kondisi')) {
                $query->where('kondisi', $request->kondisi);
            }

            // Filter by tanggal
            if ($request->has('tanggal_dari')) {
                $query->whereDate('tanggal_masuk', '>=', $request->tanggal_dari);
            }

            if ($request->has('tanggal_sampai')) {
                $query->whereDate('tanggal_masuk', '<=', $request->tanggal_sampai);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $inventaris = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data inventaris berhasil diambil',
                'data' => InventarisBarangResource::collection($inventaris),
                'pagination' => [
                    'current_page' => $inventaris->currentPage(),
                    'last_page' => $inventaris->lastPage(),
                    'per_page' => $inventaris->perPage(),
                    'total' => $inventaris->total(),
                    'from' => $inventaris->firstItem(),
                    'to' => $inventaris->lastItem(),
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
                'nama_barang' => 'required|string|max:255',
                'kategori_id' => 'required|exists:kategori_barang,id',
                'tanggal_masuk' => 'required|date',
                'jumlah' => 'required|integer|min:1',
                'kondisi' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
                'deskripsi' => 'nullable|string',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('gambar')) {
                $gambarPath = $request->file('gambar')->store('inventaris', 'public');
                $data['gambar_path'] = $gambarPath;
            }

            $data['created_by'] = Auth::id();

            $inventaris = InventarisBarang::create($data);
            $inventaris->load(['kategori', 'createdBy']);

            return response()->json([
                'status' => 'success',
                'message' => 'Data inventaris berhasil disimpan',
                'data' => new InventarisBarangResource($inventaris)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $inventaris = InventarisBarang::with(['kategori', 'createdBy'])->find($id);

            if (!$inventaris) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data inventaris tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data inventaris berhasil diambil',
                'data' => new InventarisBarangResource($inventaris)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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

            $inventaris = InventarisBarang::find($id);

            if (!$inventaris) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data inventaris tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_barang' => 'required|string|max:255',
                'kategori_id' => 'required|exists:kategori_barang,id',
                'tanggal_masuk' => 'required|date',
                'jumlah' => 'required|integer|min:1',
                'kondisi' => 'required|in:Baik,Rusak Ringan,Rusak Berat',
                'deskripsi' => 'nullable|string',
                'gambar' => 'nullable|image|mimes:jpeg,png,jpg,gif|max:2048'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('gambar')) {
                // Delete old image if exists
                if ($inventaris->gambar_path) {
                    Storage::disk('public')->delete($inventaris->gambar_path);
                }
                
                $gambarPath = $request->file('gambar')->store('inventaris', 'public');
                $data['gambar_path'] = $gambarPath;
            }

            $inventaris->update($data);
            $inventaris->load(['kategori', 'createdBy']);

            return response()->json([
                'status' => 'success',
                'message' => 'Data inventaris berhasil diperbarui',
                'data' => new InventarisBarangResource($inventaris)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

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

            $inventaris = InventarisBarang::find($id);

            if (!$inventaris) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data inventaris tidak ditemukan',
                ], 404);
            }

            // Delete image file if exists
            if ($inventaris->gambar_path) {
                Storage::disk('public')->delete($inventaris->gambar_path);
            }

            $inventaris->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data inventaris berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getKategories()
    {
        try {
            $categories = KategoriBarang::select('id', 'nama_kategori')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Data kategori berhasil diambil',
                'data' => $categories
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil data kategori',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}