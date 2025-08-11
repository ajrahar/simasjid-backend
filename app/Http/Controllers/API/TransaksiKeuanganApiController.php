<?php

namespace App\Http\Controllers\API;

use App\Http\Controllers\Controller;
use App\Models\TransaksiKeuangan;
use App\Models\KategoriKeuangan;
use App\Http\Resources\TransaksiKeuanganResource;
use App\Helpers\HijriHelper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\DB;

class TransaksiKeuanganApiController extends Controller
{
    public function index(Request $request)
    {
        try {
            $query = TransaksiKeuangan::with(['kategoriKeuangan', 'createdBy']);

            // Filter by jenis transaksi
            if ($request->has('jenis_transaksi')) {
                $query->where('jenis_transaksi', $request->jenis_transaksi);
            }

            // Filter by kategori
            if ($request->has('kategori_id')) {
                $query->where('kategori_keuangan_id', $request->kategori_id);
            }

            // Filter by tanggal
            if ($request->has('tanggal_dari')) {
                $query->whereDate('tanggal_masehi', '>=', $request->tanggal_dari);
            }

            if ($request->has('tanggal_sampai')) {
                $query->whereDate('tanggal_masehi', '<=', $request->tanggal_sampai);
            }

            // Search by keterangan
            if ($request->has('search')) {
                $search = $request->search;
                $query->where('keterangan', 'like', '%' . $search . '%');
            }

            // Sorting
            $sortBy = $request->get('sort_by', 'tanggal_masehi');
            $sortOrder = $request->get('sort_order', 'desc');
            $query->orderBy($sortBy, $sortOrder);

            // Check user role for access control
            if (Auth::user()->role === 'user') {
                // For public users, hide detailed information
                $query->select([
                    'id', 'tanggal_masehi', 'tanggal_hijriah', 'jenis_transaksi', 
                    'kategori_keuangan_id', 'jumlah', 'created_at'
                ]);
            }

            // Pagination
            $perPage = $request->get('per_page', 15);
            $transaksi = $query->paginate($perPage);

            return response()->json([
                'status' => 'success',
                'message' => 'Data transaksi berhasil diambil',
                'data' => TransaksiKeuanganResource::collection($transaksi),
                'pagination' => [
                    'current_page' => $transaksi->currentPage(),
                    'last_page' => $transaksi->lastPage(),
                    'per_page' => $transaksi->perPage(),
                    'total' => $transaksi->total(),
                    'from' => $transaksi->firstItem(),
                    'to' => $transaksi->lastItem(),
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
                    'message' => 'Akses ditolak. Hanya admin yang dapat menambah transaksi.',
                ], 403);
            }

            $validator = Validator::make($request->all(), [
                'tanggal_masehi' => 'required|date',
                'jenis_transaksi' => 'required|in:pemasukan,pengeluaran',
                'kategori_keuangan_id' => 'required|exists:kategori_keuangan,id',
                'keterangan' => 'required|string',
                'jumlah' => 'required|numeric|min:0.01',
                'sumber_tujuan_dana' => 'required|string|max:255',
                'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg,gif,pdf|max:5120',
                'nomor_referensi' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Generate Hijri date
            $data['tanggal_hijriah'] = HijriHelper::convertToHijri($data['tanggal_masehi']);

            if ($request->hasFile('bukti_transaksi')) {
                $buktiPath = $request->file('bukti_transaksi')->store('transaksi', 'public');
                $data['bukti_transaksi_path'] = $buktiPath;
            }

            $data['created_by'] = Auth::id();

            $transaksi = TransaksiKeuangan::create($data);
            $transaksi->load(['kategoriKeuangan', 'createdBy']);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil disimpan',
                'data' => new TransaksiKeuanganResource($transaksi)
            ], 201);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menyimpan transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function show($id)
    {
        try {
            $transaksi = TransaksiKeuangan::with(['kategoriKeuangan', 'createdBy'])->find($id);

            if (!$transaksi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data transaksi tidak ditemukan',
                ], 404);
            }

            // Check access for non-admin users
            if (Auth::user()->role === 'user') {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Akses ditolak. Anda tidak memiliki izin untuk melihat detail transaksi.',
                ], 403);
            }

            return response()->json([
                'status' => 'success',
                'message' => 'Data transaksi berhasil diambil',
                'data' => new TransaksiKeuanganResource($transaksi)
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
                    'message' => 'Akses ditolak. Hanya admin yang dapat mengubah transaksi.',
                ], 403);
            }

            $transaksi = TransaksiKeuangan::find($id);

            if (!$transaksi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data transaksi tidak ditemukan',
                ], 404);
            }

            $validator = Validator::make($request->all(), [
                'tanggal_masehi' => 'required|date',
                'jenis_transaksi' => 'required|in:pemasukan,pengeluaran',
                'kategori_keuangan_id' => 'required|exists:kategori_keuangan,id',
                'keterangan' => 'required|string',
                'jumlah' => 'required|numeric|min:0.01',
                'sumber_tujuan_dana' => 'required|string|max:255',
                'bukti_transaksi' => 'nullable|image|mimes:jpeg,png,jpg,gif,pdf|max:5120',
                'nomor_referensi' => 'nullable|string|max:100'
            ]);

            if ($validator->fails()) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Validasi gagal',
                    'errors' => $validator->errors()
                ], 422);
            }

            $data = $validator->validated();

            // Generate Hijri date if date changed
            if ($data['tanggal_masehi'] !== $transaksi->tanggal_masehi->format('Y-m-d')) {
                $data['tanggal_hijriah'] = HijriHelper::convertToHijri($data['tanggal_masehi']);
            }

            if ($request->hasFile('bukti_transaksi')) {
                // Delete old file if exists
                if ($transaksi->bukti_transaksi_path) {
                    Storage::disk('public')->delete($transaksi->bukti_transaksi_path);
                }
                
                $buktiPath = $request->file('bukti_transaksi')->store('transaksi', 'public');
                $data['bukti_transaksi_path'] = $buktiPath;
            }

            $transaksi->update($data);
            $transaksi->load(['kategoriKeuangan', 'createdBy']);

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil diperbarui',
                'data' => new TransaksiKeuanganResource($transaksi)
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat memperbarui transaksi',
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
                    'message' => 'Akses ditolak. Hanya admin yang dapat menghapus transaksi.',
                ], 403);
            }

            $transaksi = TransaksiKeuangan::find($id);

            if (!$transaksi) {
                return response()->json([
                    'status' => 'error',
                    'message' => 'Data transaksi tidak ditemukan',
                ], 404);
            }

            // Delete bukti file if exists
            if ($transaksi->bukti_transaksi_path) {
                Storage::disk('public')->delete($transaksi->bukti_transaksi_path);
            }

            $transaksi->delete();

            return response()->json([
                'status' => 'success',
                'message' => 'Transaksi berhasil dihapus',
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat menghapus transaksi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function rekapitulasi(Request $request)
    {
        try {
            $tahun = $request->get('tahun', date('Y'));
            $bulan = $request->get('bulan');

            $query = TransaksiKeuangan::whereYear('tanggal_masehi', $tahun);

            if ($bulan) {
                $query->whereMonth('tanggal_masehi', $bulan);
            }

            $totalPemasukan = $query->clone()->where('jenis_transaksi', 'pemasukan')->sum('jumlah');
            $totalPengeluaran = $query->clone()->where('jenis_transaksi', 'pengeluaran')->sum('jumlah');
            $saldoAkhir = $totalPemasukan - $totalPengeluaran;

            // Rekapitulasi per kategori
            $rekapPerKategori = $query->clone()
                ->join('kategori_keuangan', 'transaksi_keuangan.kategori_keuangan_id', '=', 'kategori_keuangan.id')
                ->select(
                    'kategori_keuangan.nama_kategori',
                    'kategori_keuangan.jenis_kategori',
                    DB::raw('SUM(transaksi_keuangan.jumlah) as total')
                )
                ->groupBy('kategori_keuangan.id', 'kategori_keuangan.nama_kategori', 'kategori_keuangan.jenis_kategori')
                ->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Rekapitulasi keuangan berhasil diambil',
                'data' => [
                    'periode' => [
                        'tahun' => (int)$tahun,
                        'bulan' => $bulan ? (int)$bulan : null,
                        'nama_bulan' => $bulan ? date('F', mktime(0, 0, 0, $bulan, 1)) : null
                    ],
                    'summary' => [
                        'total_pemasukan' => (float)$totalPemasukan,
                        'total_pemasukan_formatted' => 'Rp ' . number_format($totalPemasukan, 0, ',', '.'),
                        'total_pengeluaran' => (float)$totalPengeluaran,
                        'total_pengeluaran_formatted' => 'Rp ' . number_format($totalPengeluaran, 0, ',', '.'),
                        'saldo_akhir' => (float)$saldoAkhir,
                        'saldo_akhir_formatted' => 'Rp ' . number_format($saldoAkhir, 0, ',', '.')
                    ],
                    'rekap_per_kategori' => $rekapPerKategori->map(function ($item) {
                        return [
                            'nama_kategori' => $item->nama_kategori,
                            'jenis_kategori' => $item->jenis_kategori,
                            'total' => (float)$item->total,
                            'total_formatted' => 'Rp ' . number_format($item->total, 0, ',', '.')
                        ];
                    })
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil rekapitulasi',
                'error' => $e->getMessage()
            ], 500);
        }
    }

    public function getKategories()
    {
        try {
            $categories = KategoriKeuangan::select('id', 'nama_kategori', 'jenis_kategori')->get();

            return response()->json([
                'status' => 'success',
                'message' => 'Data kategori keuangan berhasil diambil',
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