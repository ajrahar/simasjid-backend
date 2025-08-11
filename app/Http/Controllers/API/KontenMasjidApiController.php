<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\KontenMasjid;
use App\Http\Resources\KontenMasjidResource;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

class KontenMasjidApiController extends Controller
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
            $query = KontenMasjid::with('createdBy');

            // Filter by status for non-admins
            if (Auth::user() && Auth::user()->role !== 'admin') {
                $query->where('status', 'publish');
            }

            // Search functionality
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('judul', 'like', '%' . $search . '%');
            }

            // Filter by content type
            if ($request->has('jenis_konten')) {
                $query->where('jenis_konten', $request->jenis_konten);
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'created_at');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Pagination
            $perPage = $request->get('per_page', 15);
            $kontens = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data konten berhasil diambil',
                'data' => KontenMasjidResource::collection($kontens),
                'pagination' => [
                    'current_page' => $kontens->currentPage(),
                    'last_page' => $kontens->lastPage(),
                    'per_page' => $kontens->perPage(),
                    'total' => $kontens->total(),
                    'from' => $kontens->firstItem(),
                    'to' => $kontens->lastItem(),
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
                'judul' => 'required|string|max:255',
                'jenis_konten' => 'required|in:artikel,gambar,video',
                'konten_teks' => 'nullable|string',
                'file' => 'nullable|file|max:10240', // 10MB max
                'status' => 'required|in:draft,publish',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('file')) {
                $filePath = $request->file('file')->store('konten-masjid', 'public');
                $data['file_path'] = $filePath;
            }

            $data['created_by'] = Auth::id();
            $data['slug'] = Str::slug($data['judul']);

            $konten = KontenMasjid::create($data);
            $konten->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Data konten berhasil disimpan',
                'data' => new KontenMasjidResource($konten)
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
            $konten = KontenMasjid::with('createdBy')->find($id);

            if (!$konten) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data konten tidak ditemukan',
                ], 404);
            }

            // Check authorization for non-published content
            if ($konten->status === 'draft' && (!Auth::user() || Auth::user()->role !== 'admin')) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Konten tidak dipublikasikan.',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data konten berhasil diambil',
                'data' => new KontenMasjidResource($konten)
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

            $konten = KontenMasjid::find($id);

            if (!$konten) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data konten tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'judul' => 'required|string|max:255',
                'jenis_konten' => 'required|in:artikel,gambar,video',
                'konten_teks' => 'nullable|string',
                'file' => 'nullable|file|max:10240',
                'status' => 'required|in:draft,publish',
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            if ($request->hasFile('file')) {
                if ($konten->file_path) {
                    Storage::disk('public')->delete($konten->file_path);
                }
                $filePath = $request->file('file')->store('konten-masjid', 'public');
                $data['file_path'] = $filePath;
            }

            $data['slug'] = Str::slug($data['judul']);
            $konten->update($data);
            $konten->load('createdBy');

            return response()->json([
                'status' => 'success',
                'message' => 'Data konten berhasil diperbarui',
                'data' => new KontenMasjidResource($konten)
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

            $konten = KontenMasjid::find($id);

            if (!$konten) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data konten tidak ditemukan',
                ], 404);
            }

            if ($konten->file_path) {
                Storage::disk('public')->delete($konten->file_path);
            }

            $konten->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Data konten berhasil dihapus',
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