<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KategoriBarang;
use App\Http\Resources\KategoriBarangResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class KategoriBarangApiController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index(Request $request)
    {
        try {
            $query = KategoriBarang::with('createdBy');

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('nama_kategori', 'like', '%' . $search . '%')
                      ->orWhere('deskripsi', 'like', '%' . $search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            
            if ($request->get('all') === 'true') {
                // Return all categories without pagination (useful for dropdowns)
                $categories = $query->get();
                
                return response()->json([
                    'status' => 'success',
                    'message' => 'Data kategori barang berhasil diambil',
                    'data' => KategoriBarangResource::collection($categories)
                ], 200);
            }

            $categories = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data kategori barang berhasil diambil',
                'data' => KategoriBarangResource::collection($categories),
                'pagination' => [
                    'current_page' => $categories->currentPage(),
                    'last_page' => $categories->lastPage(),
                    'per_page' => $categories->perPage(),
                    'total' => $categories->total(),
                    'from' => $categories->firstItem(),
                    'to' => $categories->lastItem(),
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
     */
    public function store(Request $request)
    {
        try {
            // No role check needed - all authenticated users are admins
            
            $validator = Validator::make($request->all(), [
                'nama_kategori' => 'required|string|max:255|unique:kategori_barang,nama_kategori',
                'deskripsi' => 'nullable|string|max:1000'
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

            $kategori = KategoriBarang::create($data);
            $kategori->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori barang berhasil disimpan',
                'data' => new KategoriBarangResource($kategori)
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
     */
    public function show($id)
    {
        try {
            $kategori = KategoriBarang::with(['createdBy', 'inventarisBarang'])->find($id);

            if (!$kategori) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data kategori barang tidak ditemukan',
                ], 404);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data kategori barang berhasil diambil',
                'data' => new KategoriBarangResource($kategori),
                'statistics' => [
                    'total_barang' => $kategori->inventarisBarang->count(),
                    'total_jumlah' => $kategori->inventarisBarang->sum('jumlah'),
                    'kondisi_baik' => $kategori->inventarisBarang->where('kondisi', 'Baik')->count(),
                    'kondisi_rusak_ringan' => $kategori->inventarisBarang->where('kondisi', 'Rusak Ringan')->count(),
                    'kondisi_rusak_berat' => $kategori->inventarisBarang->where('kondisi', 'Rusak Berat')->count(),
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
     * Update the specified resource in storage.
     */
    public function update(Request $request, $id)
    {
        try {
            // No role check needed - all authenticated users are admins
            
            $kategori = KategoriBarang::find($id);

            if (!$kategori) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data kategori barang tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'nama_kategori' => 'required|string|max:255|unique:kategori_barang,nama_kategori,' . $id,
                'deskripsi' => 'nullable|string|max:1000'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();
            $kategori->update($data);
            $kategori->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori barang berhasil diperbarui',
                'data' => new KategoriBarangResource($kategori)
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
     */
    public function destroy($id)
    {
        try {
            // No role check needed - all authenticated users are admins
            
            $kategori = KategoriBarang::find($id);

            if (!$kategori) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data kategori barang tidak ditemukan',
                ], 404);
            }

            // Check if category is being used by inventaris
            $inventarisCount = $kategori->inventarisBarang()->count();
            if ($inventarisCount > 0) {
                return response()->json([
                    'status' => 'error',
                    'message' => "Kategori tidak dapat dihapus karena masih digunakan oleh {$inventarisCount} item inventaris",
                ], 422);
            }

            $kategori->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Kategori barang berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus data',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    /**
     * Get categories for dropdown/select options
     */
    public function getOptions()
    {
        try {
            $categories = KategoriBarang::select('id', 'nama_kategori')
                ->orderBy('nama_kategori', 'asc')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Data kategori untuk dropdown berhasil diambil',
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

    /**
     * Get category statistics
     */
    public function statistics()
    {
        try {
            $categories = KategoriBarang::withCount(['inventarisBarang as total_items'])
                ->with(['inventarisBarang' => function ($query) {
                    $query->selectRaw('kategori_id, SUM(jumlah) as total_quantity')
                        ->groupBy('kategori_id');
                }])
                ->get();

            $statistics = $categories->map(function ($category) {
                $totalQuantity = $category->inventarisBarang->sum('jumlah') ?? 0;
                
                return [
                    'id' => $category->id,
                    'nama_kategori' => $category->nama_kategori,
                    'total_items' => $category->total_items,
                    'total_quantity' => $totalQuantity,
                    'percentage' => $categories->sum('total_items') > 0 
                        ? round(($category->total_items / $categories->sum('total_items')) * 100, 2) 
                        : 0
                ];
            });

            return response()->json([
                'status' => 'success',
                'message' => 'Statistik kategori berhasil diambil',
                'data' => [
                    'total_categories' => $categories->count(),
                    'total_items_all_categories' => $categories->sum('total_items'),
                    'categories' => $statistics
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil statistik',
                'error' => $e->getMessage()
            ], 500);
        }
    }
}