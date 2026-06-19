<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class MasterController extends Controller
{
    public function getAllMasterData()
    {
        return response()->json([
            'karyawan' => DB::table('karyawan')->orderBy('id_karyawan', 'desc')->get(),
            'truk' => DB::table('truk')->orderBy('id_truk', 'desc')->get(),
            'pangkalan' => DB::table('pangkalan')->orderBy('nama_pangkalan', 'asc')->get(),
            'spbe' => DB::table('spbe')->orderBy('id_spbe', 'desc')->get(),
        ]);
    }

    // CREATE & UPDATE (Universal)
    public function storeOrUpdate(Request $request, $type)
    {
        $id = $request->id;
        $data = [];
        $pk = '';

        // FILTER DATA berdasarkan tipe agar tidak memasukkan kolom yang salah
        if ($type === 'karyawan') {
            $data = $request->only(['nama', 'jabatan', 'no_hp']);
            $pk = 'id_karyawan';
        } elseif ($type === 'truk') {
            $data = $request->only(['plat_no', 'tipe_truk']);
            $pk = 'id_truk';
        } elseif ($type === 'pangkalan') {
            $data = $request->only(['nama_pangkalan', 'no_registrasi', 'alamat']);
            $pk = 'id_pangkalan';
        } elseif ($type === 'spbe') {
            $data = $request->only(['nama_spbe']);
            $pk = 'id_spbe';
        }

        $data['updated_at'] = now();

        DB::beginTransaction();
        try {
            if ($id) {
                // 1. Update Tabel Master (Misal: Pangkalan)
                DB::table($type)->where($pk, $id)->update($data);

                // 2. SINKRONISASI: Jika tipe adalah pangkalan, update juga tabel Users
                if ($type === 'pangkalan') {
                    $pangkalan = DB::table('pangkalan')->where('id_pangkalan', $id)->first();
                    DB::table('users')
                        ->where('id', $pangkalan->id_user)
                        ->update(['name' => $request->nama_pangkalan]);
                }

                $msg = "Data berhasil diperbarui dan disinkronkan";
            } else {
                // ... logic insert tetap sama
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // DELETE (Universal)
    public function destroy($type, $id)
    {
        $pk = $type == 'karyawan' ? 'id_karyawan' : ($type == 'truk' ? 'id_truk' : ($type == 'pangkalan' ? 'id_pangkalan' : 'id_spbe'));
        
        DB::table($type)->where($pk, $id)->delete();
        return response()->json(['success' => true, 'message' => 'Data berhasil dihapus']);
    }

    public function storeKaryawan(Request $request)
    {
        $request->validate([
            'nama' => 'required',
            'jabatan' => 'required',
            'no_hp' => 'required'
        ]);

        DB::table('karyawan')->insert([
            'nama' => $request->nama,
            'jabatan' => $request->jabatan,
            'no_hp' => $request->no_hp,
            'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['success' => true]);
    }

    public function storeTruk(Request $request)
    {
        $request->validate([
            'plat_no' => 'required|unique:truk,plat_no',
            'tipe_truk' => 'required'
        ]);

        DB::table('truk')->insert([
            'plat_no' => $request->plat_no,
            'tipe_truk' => $request->tipe_truk,
            'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['success' => true]);
    }

    public function storeSpbe(Request $request)
    {
        $request->validate(['nama_spbe' => 'required']);
        DB::table('spbe')->insert([
            'nama_spbe' => $request->nama_spbe,
            'created_at' => now(), 'updated_at' => now()
        ]);
        return response()->json(['success' => true]);
    }

    public function getAuditLogs() {
        return \DB::table('audit_trail')
            ->join('users', 'audit_trail.id_user', '=', 'users.id')
            ->select('audit_trail.*', 'users.name', 'users.role')
            ->orderBy('audit_trail.waktu_log', 'desc')
            ->get();
    }

    public function getAssetSummary() {
        // 1. Stok di Gudang (Isi & Kosong)
        $gudang = DB::table('stok_gudang')->first();

        // 2. Stok di Pangkalan (Total dari 76 pangkalan)
        $diPangkalan = DB::table('pangkalan')->sum('stok_saat_ini');

        // 3. Stok di Perjalanan (Sedang dibawa truk, belum dikonfirmasi pangkalan)
        $diPerjalanan = DB::table('distribusi_pangkalan')
            ->where('status_penerimaan', 'Proses')
            ->sum('jumlah_isi_dikirim');

        // 4. Total Aset Terdeteksi
        $totalTerdeteksi = $gudang->jumlah_isi + $gudang->jumlah_kosong + $diPangkalan + $diPerjalanan;

        return response()->json([
            'gudang_isi' => $gudang->jumlah_isi,
            'gudang_kosong' => $gudang->jumlah_kosong,
            'pangkalan' => (int)$diPangkalan,
            'perjalanan' => (int)$diPerjalanan,
            'total_aset' => $totalTerdeteksi,
            'target_aset' => 5906,
            'selisih' => 5906 - $totalTerdeteksi
        ]);
    }

    public function getInventoryMapping() {
        try {
            // 1. Ambil data stok gudang (Lantai 1, 2, 3)
            $gudang = DB::table('stok_gudang')->where('id_stok', 1)->first();

            // 2. Ambil data armada (Truk/Colt) yang sedang membawa muatan (Isi atau Kosong)
            // Kita ambil semua truk yang punya stok_isi > 0 atau stok_kosong > 0
            $armada = DB::table('truk')
                ->select('plat_no', 'kategori_kendaraan', 'stok_isi', 'stok_kosong')
                ->where('stok_isi', '>', 0)
                ->orWhere('stok_kosong', '>', 0)
                ->get()
                ->map(function($t) {
                    return [
                        'plat_no' => $t->plat_no,
                        'kategori_kendaraan' => $t->kategori_kendaraan,
                        'muatan' => $t->stok_isi + $t->stok_kosong // Total muatan di truk
                    ];
                });

            // 3. Hitung total tabung yang ada di 76 Pangkalan
            $totalPangkalan = DB::table('pangkalan')->sum('stok_saat_ini');

            return response()->json([
                'total_aset' => 5906,
                'jadug_hub' => [
                    'lantai_1' => [
                        'isi' => (int)$gudang->lantai_1_isi, 
                        'kosong' => (int)$gudang->lantai_1_kosong
                    ],
                    'lantai_2' => [
                        'isi' => (int)$gudang->lantai_2_isi, 
                        'kosong' => (int)$gudang->lantai_2_kosong
                    ],
                    'lantai_3' => [
                        'isi' => (int)$gudang->lantai_3_isi, 
                        'kosong' => (int)$gudang->lantai_3_kosong
                    ],
                ],
                'armada' => $armada,
                'pangkalan' => (int)$totalPangkalan
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getDashboardExtra() {
        // 1. Ambil pangkalan yang stoknya di bawah 15 unit
        $lowStock = DB::table('pangkalan')
            ->where('stok_saat_ini', '<', 15)
            ->orderBy('stok_saat_ini', 'asc')
            ->limit(5)
            ->get();

        // 2. Ambil 5 aktivitas terbaru dari audit trail
        $logs = DB::table('audit_trail')
            ->join('users', 'audit_trail.id_user', '=', 'users.id')
            ->select('audit_trail.*', 'users.name', 'users.role')
            ->orderBy('audit_trail.waktu_log', 'desc')
            ->limit(5)
            ->get();

        return response()->json([
            'low_stock' => $lowStock,
            'logs' => $logs
        ]);
    }

    // Ambil daftar semua user
    public function getUsers() {
        return DB::table('users')->orderBy('role', 'asc')->get();
    }

    // Update atau Simpan User baru
    public function saveUser(Request $request) {
        $request->validate([
            'name' => 'required',
            'email' => 'required|email',
            'role' => 'required'
        ]);

        $id = $request->id;

        DB::beginTransaction(); // Gunakan transaksi agar jika satu gagal, semua batal
        try {
            $data = [
                'name' => $request->name,
                'email' => $request->email,
                'role' => $request->role,
                'updated_at' => now()
            ];

            if ($request->password) {
                $data['password'] = bcrypt($request->password);
            }

            if ($id) {
                // 1. Update Tabel Users
                DB::table('users')->where('id', $id)->update($data);

                // 2. SINKRONISASI: Jika user adalah pangkalan, update juga tabel pangkalan
                if ($request->role === 'customer') {
                    DB::table('pangkalan')
                        ->where('id_user', $id)
                        ->update(['nama_pangkalan' => $request->name]);
                }
                
                $msg = "User dan data terkait berhasil diperbarui";
            } else {
                $data['created_at'] = now();
                DB::table('users')->insert($data);
                $msg = "User baru ditambahkan";
            }

            DB::commit();
            return response()->json(['success' => true, 'message' => $msg]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Hapus User
    public function deleteUser($id) {
        // Proteksi: Admin tidak boleh hapus dirinya sendiri
        if (auth()->id() == $id) {
            return response()->json(['success' => false, 'message' => 'Tidak bisa menghapus akun sendiri!'], 422);
        }
        DB::table('users')->where('id', $id)->delete();
        return response()->json(['success' => true]);
    }
}