<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\AuthController;

// API Controllers
use App\Http\Controllers\API\IdentitasMasjidApiController;
use App\Http\Controllers\API\KategoriBarangApiController;
use App\Http\Controllers\API\InventarisBarangApiController;
use App\Http\Controllers\API\PengurusMasjidApiController;
use App\Http\Controllers\API\JabatanApiController;
use App\Http\Controllers\API\MuadzinApiController;
use App\Http\Controllers\API\KhatibApiController;
use App\Http\Controllers\API\KontenMasjidApiController;
use App\Http\Controllers\API\AgendaMasjidApiController;
use App\Http\Controllers\API\KategoriKeuanganApiController;
use App\Http\Controllers\API\TransaksiKeuanganApiController;

// Auth Routes
Route::post('/register', [AuthController::class, 'register']);
Route::post('/login', [AuthController::class, 'login']);

Route::middleware('auth:sanctum')->group(function () {
    // Auth routes
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/user', [AuthController::class, 'user']);

    // ===== IDENTITAS MASJID =====
    Route::apiResource('identitas', IdentitasMasjidApiController::class);

    // ===== KATEGORI BARANG =====
    Route::apiResource('kategori-barang', KategoriBarangApiController::class);
    Route::get('kategori-barang-options', [KategoriBarangApiController::class, 'getOptions']);
    Route::get('kategori-barang-statistics', [KategoriBarangApiController::class, 'statistics']);


    // ===== INVENTARIS BARANG =====
    Route::apiResource('inventaris', InventarisBarangApiController::class);
    Route::get('inventaris/kategori/list', [InventarisBarangApiController::class, 'getKategories']);

    // ===== PENGURUS MASJID =====
    Route::apiResource('pengurus', PengurusMasjidApiController::class);

    // ===== JABATAN =====
    Route::apiResource('jabatan', JabatanApiController::class);

    // ===== MUADZIN =====
    Route::apiResource('muadzin', MuadzinApiController::class);

    // ===== KHATIB =====
    Route::apiResource('khatib', KhatibApiController::class);

    // ===== KONTEN MASJID =====
    Route::apiResource('konten', KontenMasjidApiController::class);
    Route::patch('konten/{konten}/publish', [KontenMasjidApiController::class, 'publish']);
    Route::patch('konten/{konten}/unpublish', [KontenMasjidApiController::class, 'unpublish']);

    // ===== AGENDA MASJID =====
    Route::apiResource('agenda', AgendaMasjidApiController::class);
    Route::get('agenda/calendar/{year}/{month?}', [AgendaMasjidApiController::class, 'calendar']);

    // ===== KATEGORI KEUANGAN =====
    Route::apiResource('kategori-keuangan', KategoriKeuanganApiController::class);

    // ===== TRANSAKSI KEUANGAN =====
    Route::apiResource('transaksi', TransaksiKeuanganApiController::class);
    Route::get('transaksi/kategori/list', [TransaksiKeuanganApiController::class, 'getKategories']);
    Route::get('keuangan/rekapitulasi', [TransaksiKeuanganApiController::class, 'rekapitulasi']);

    // ===== DASHBOARD/SUMMARY =====
    Route::get('dashboard/summary', function () {
        try {
            $totalInventaris = \App\Models\InventarisBarang::count();
            $totalPengurus = \App\Models\PengurusMasjid::where('status', 'Aktif')->count();
            $totalMuadzin = \App\Models\Muadzin::where('status', 'Aktif')->count();
            $totalKhatib = \App\Models\Khatib::where('status', 'Aktif')->count();
            $totalKonten = \App\Models\KontenMasjid::where('status', 'Published')->count();
            $agendaHariIni = \App\Models\AgendaMasjid::whereDate('tanggal_masehi', today())->count();
            
            // Financial summary for current month
            $bulanIni = now()->month;
            $tahunIni = now()->year;
            $totalPemasukan = \App\Models\TransaksiKeuangan::whereMonth('tanggal_masehi', $bulanIni)
                ->whereYear('tanggal_masehi', $tahunIni)
                ->where('jenis_transaksi', 'pemasukan')
                ->sum('jumlah');
            
            $totalPengeluaran = \App\Models\TransaksiKeuangan::whereMonth('tanggal_masehi', $bulanIni)
                ->whereYear('tanggal_masehi', $tahunIni)
                ->where('jenis_transaksi', 'pengeluaran')
                ->sum('jumlah');

            return response()->json([
                'status' => 'success',
                'message' => 'Dashboard summary berhasil diambil',
                'data' => [
                    'inventaris' => [
                        'total' => $totalInventaris,
                        'label' => 'Total Inventaris'
                    ],
                    'pengurus' => [
                        'total' => $totalPengurus,
                        'label' => 'Pengurus Aktif'
                    ],
                    'muadzin' => [
                        'total' => $totalMuadzin,
                        'label' => 'Muadzin Aktif'
                    ],
                    'khatib' => [
                        'total' => $totalKhatib,
                        'label' => 'Khatib Aktif'
                    ],
                    'konten' => [
                        'total' => $totalKonten,
                        'label' => 'Konten Published'
                    ],
                    'agenda_hari_ini' => [
                        'total' => $agendaHariIni,
                        'label' => 'Agenda Hari Ini'
                    ],
                    'keuangan_bulan_ini' => [
                        'pemasukan' => (float)$totalPemasukan,
                        'pemasukan_formatted' => 'Rp ' . number_format($totalPemasukan, 0, ',', '.'),
                        'pengeluaran' => (float)$totalPengeluaran,
                        'pengeluaran_formatted' => 'Rp ' . number_format($totalPengeluaran, 0, ',', '.'),
                        'saldo' => (float)($totalPemasukan - $totalPengeluaran),
                        'saldo_formatted' => 'Rp ' . number_format($totalPemasukan - $totalPengeluaran, 0, ',', '.'),
                        'bulan' => now()->format('F Y')
                    ]
                ]
            ], 200);

        } catch (\Exception $e) {
            return response()->json([
                'status' => 'error',
                'message' => 'Terjadi kesalahan saat mengambil summary',
                'error' => $e->getMessage()
            ], 500);
        }
    });
});

// ===== PUBLIC ROUTES (Untuk User/Jemaah) =====
Route::prefix('public')->name('api.public.')->group(function () {
    Route::get('identitas', [IdentitasMasjidApiController::class, 'index']);
    Route::get('inventaris', [InventarisBarangApiController::class, 'index']);
    Route::get('pengurus', [PengurusMasjidApiController::class, 'index']);
    Route::get('konten', [KontenMasjidApiController::class, 'index']);
    Route::get('konten/{konten}', [KontenMasjidApiController::class, 'show']);
    Route::get('muadzin', [MuadzinApiController::class, 'index']);
    Route::get('khatib', [KhatibApiController::class, 'index']);
    Route::get('agenda', [AgendaMasjidApiController::class, 'index']);
    Route::get('agenda/calendar/{year}/{month?}', [AgendaMasjidApiController::class, 'calendar']);
    Route::get('keuangan/rekapitulasi', [TransaksiKeuanganApiController::class, 'rekapitulasi']);
});