<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AlokasiController extends Controller
{
    // Ambil data pendukung untuk Form
    public function getResources()
    {
        return response()->json([
            'spbe' => DB::table('spbe')->get(),
            'pangkalan' => DB::table('pangkalan')->get()
        ]);
    }

    // Simpan Alokasi Harian
    public function store(Request $request)
    {
        // 1. Validasi Input
        $request->validate([
            'tanggal' => 'required|date',
            'id_spbe' => 'required',
            'total_rencana' => 'required|numeric|min:1',
            'alokasi_data' => 'required|array',
            'user_id' => 'required' 
        ]);

        $totalInputPangkalan = collect($request->alokasi_data)->sum('jumlah');

        if ((int)$totalInputPangkalan !== (int)$request->total_rencana) {
            return response()->json(['success' => false, 'message' => "Total tidak sinkron! Rencana: {$request->total_rencana}, Input: {$totalInputPangkalan}"], 422);
        }

        // Tentukan sumber alokasi
        $isTitipan = ($request->id_spbe === 'titipan');

        DB::beginTransaction();
        try {
            // 2. Simpan Header
            // Pastikan kolom 'status' sudah ada di tabel header_alokasi
            $headerId = DB::table('header_alokasi')->insertGetId([
                'tanggal' => $request->tanggal,
                // Jika titipan, masukkan null. Jika SPBE, masukkan ID-nya.
                'id_spbe' => $isTitipan ? null : $request->id_spbe, 
                'total_rencana_isi' => $request->total_rencana,
                'jumlah_truk' => ceil($request->total_rencana / 560),
                'status' => $isTitipan ? 'SPBE' : 'Pending', 
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 3. Simpan Detail Alokasi
            foreach ($request->alokasi_data as $item) {
                if ($item['jumlah'] > 0) {
                    // Simpan ke tabel alokasi
                    DB::table('alokasi')->insert([
                        'header_id' => $headerId,
                        'id_pangkalan' => $item['id_pangkalan'],
                        'jumlah_alokasi' => $item['jumlah'],
                        'tanggal' => $request->tanggal,
                        'id_spbe' => $isTitipan ? null : $request->id_spbe,
                        'created_at' => now(),
                        'updated_at' => now()
                    ]);

                    // 4. Logika Khusus Titipan: Kurangi Saldo
                    if ($isTitipan) {
                        $pangkalan = DB::table('pangkalan')->where('id_pangkalan', $item['id_pangkalan'])->lockForUpdate()->first();
                        
                        if (!$pangkalan || $pangkalan->stok_titipan < $item['jumlah']) {
                            throw new \Exception("Stok titipan Pangkalan " . ($pangkalan->nama_pangkalan ?? $item['id_pangkalan']) . " tidak mencukupi.");
                        }

                        // Update Saldo Pangkalan
                        DB::table('pangkalan')->where('id_pangkalan', $item['id_pangkalan'])->decrement('stok_titipan', $item['jumlah']);
                        
                        // Update Saldo Global Gudang
                        DB::table('stok_gudang')->where('id_stok', 1)->decrement('jumlah_titipan', $item['jumlah']);
                    }
                }
            }

            // 5. Catat Audit Trail
            $tipeAksi = $isTitipan ? "DISTRIBUSI TITIPAN" : "ALOKASI BARU";
            $this->logActivity($request->user_id, "Manager menerbitkan $tipeAksi sejumlah {$request->total_rencana} tabung.");

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Alokasi berhasil disimpan!']);

        } catch (\Exception $e) {
            DB::rollback();
            // Mengembalikan pesan error asli agar terlihat di console/network tab
            return response()->json([
                'success' => false, 
                'message' => 'Gagal di server: ' . $e->getMessage()
            ], 500);
        }
    }

    // UNTUK OPERATOR
    public function getOperatorTask() {
        $tasks = DB::table('header_alokasi')
            ->join('spbe', 'header_alokasi.id_spbe', '=', 'spbe.id_spbe')
            ->whereIn('header_alokasi.status', ['Pending', 'SPBE'])
            ->select('header_alokasi.*', 'spbe.nama_spbe')
            ->orderBy('header_alokasi.tanggal', 'desc')
            ->get();

        foreach ($tasks as $task) {
            // HITUNG BERDASARKAN ID HEADER YANG UNIK
            $task->truk_tereksekusi = DB::table('transaksi_spbe')
                ->where('header_id', $task->id) 
                ->count();
        }

        return response()->json($tasks);
    }

    // UNTUK CUSTOMER
    public function getCustomerNotification($id_user) {
        $pangkalan = DB::table('pangkalan')->where('id_user', $id_user)->first();
        if (!$pangkalan) return response()->json([]);

        // 2. Query alokasi yang BELUM dikonfirmasi oleh pangkalan ini
        return DB::table('alokasi')
            ->join('header_alokasi', 'alokasi.header_id', '=', 'header_alokasi.id')
            ->leftjoin('spbe', 'alokasi.id_spbe', '=', 'spbe.id_spbe')
            ->leftJoin('distribusi_pangkalan', 'alokasi.id_alokasi', '=', 'distribusi_pangkalan.id_alokasi')
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('konfirmasi_pangkalan')
                    ->whereRaw('konfirmasi_pangkalan.id_distribusi = distribusi_pangkalan.id_distribusi');
            })
            
            ->where('alokasi.id_pangkalan', $pangkalan->id_pangkalan)
            ->where('header_alokasi.status', '!=', 'Selesai') 
            ->select(
                'alokasi.id_alokasi',
                'alokasi.jumlah_alokasi',
                'header_alokasi.status as status_global',
                'header_alokasi.tanggal',
                'spbe.nama_spbe',
                'distribusi_pangkalan.id_distribusi' 
            )
            ->get();
    }

    // UNTUK MONITORING ADMIN (MANAGER)
    public function getGlobalMonitoring() {
        return DB::table('header_alokasi')
            ->join('spbe', 'header_alokasi.id_spbe', '=', 'spbe.id_spbe')
            ->select('header_alokasi.*', 'spbe.nama_spbe')
            ->orderBy('header_alokasi.tanggal', 'desc')
            ->get();
    }

    // Ambil data Truk dan Supir untuk Modal
    public function getFleetResources() {
        // 1. Ambil data Truk dengan status jalan
        $truk = DB::table('truk')->get()->map(function($item) {
            $isBusy = DB::table('distribusi_pangkalan')
                ->where('id_truk', $item->id_truk)
                ->where('status_penerimaan', 'Proses')
                ->exists();
            $item->status_jalan = $isBusy ? 'DI JALAN' : 'STANDBY';
            return $item;
        });

        // 2. Ambil data Supir dengan status ketersediaan (VALIDASI BARU)
        $supir = DB::table('karyawan')
            ->where('jabatan', 'Supir')
            ->get()
            ->map(function($s) {
                // Cek apakah supir ini sedang membawa truk di tabel distribusi_pangkalan
                $isWorking = DB::table('distribusi_pangkalan')
                    ->where('id_supir', $s->id_karyawan)
                    ->where('status_penerimaan', 'Proses')
                    ->exists();
                
                $s->status_tugas = $isWorking ? 'BERTUGAS' : 'STANDBY';
                return $s;
            });

        return response()->json([
            'truk' => $truk,
            'supir' => $supir,
            'kernet' => DB::table('karyawan')->where('jabatan', 'Kernet')->get(),
            'stok_saat_ini' => DB::table('stok_gudang')->where('id_stok', 1)->first()
        ]);
    }

    // Proses Transaksi SPBE (Validasi Stok Kosong -> Kurangi Stok Kosong -> Tambah Stok Isi)
    public function storeTransaksiSpbe(Request $request) {
        $request->validate([
            'id_header' => 'required',
            'id_spbe' => 'required',
            'id_truk' => 'required',
            'id_karyawan' => 'required',
            'jumlah' => 'required|numeric',
            'user_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            // 1. Ambil data Truk
            $truk = DB::table('truk')->where('id_truk', $request->id_truk)->lockForUpdate()->first();
            // VALIDASI SUPIR
            $isSupirBusy = DB::table('distribusi_pangkalan')
                ->where('id_supir', $request->id_karyawan)
                ->where('status_penerimaan', 'Proses')
                ->exists();

            if ($isSupirBusy) {
                return response()->json([
                    'success' => false, 
                    'message' => 'Gagal! Supir yang dipilih sedang dalam perjalanan distribusi.'
                ], 422);
            }
            // 2. VALIDASI FISIK: Cek apakah Truk membawa tabung kosong yang cukup?
            if ($truk->stok_kosong < $request->jumlah) {
                return response()->json([
                    'success' => false, 
                    'message' => "Gagal! Truk {$truk->plat_no} hanya membawa {$truk->stok_kosong} tabung kosong. Silahkan muat tabung kosong ke truk terlebih dahulu di menu Update Stok."
                ], 422);
            }

            // 3. Simpan Transaksi SPBE
            DB::table('transaksi_spbe')->insert([
                'header_id' => $request->id_header,
                'id_spbe' => $request->id_spbe,
                'id_truk' => $request->id_truk,
                'id_karyawan' => $request->id_karyawan,
                'jumlah_isi_masuk' => $request->jumlah,
                'jumlah_kosong_keluar' => $request->jumlah,
                'waktu_transaksi' => now(),
                'created_at' => now()
            ]);

            // 4. UPDATE STOK TRUK (Kosong berubah jadi Isi)
            DB::table('truk')->where('id_truk', $request->id_truk)->update([
                'stok_kosong' => $truk->stok_kosong - $request->jumlah,
                'stok_isi' => $truk->stok_isi + $request->jumlah,
            ]);

            // 5. Update Status Alokasi
            DB::table('header_alokasi')->where('id', $request->id_header)->update(['status' => 'SPBE']);

            // 6. Log Aktivitas
            $this->logActivity($request->user_id, "Transaksi SPBE Selesai: Truk {$truk->plat_no} telah menukar {$request->jumlah} tabung kosong dengan tabung isi.");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getSpbeHistory() {
        return DB::table('transaksi_spbe')
            ->join('spbe', 'transaksi_spbe.id_spbe', '=', 'spbe.id_spbe')
            ->join('truk', 'transaksi_spbe.id_truk', '=', 'truk.id_truk')
            ->join('karyawan', 'transaksi_spbe.id_karyawan', '=', 'karyawan.id_karyawan')
            ->select(
                'transaksi_spbe.*', 
                'spbe.nama_spbe', 
                'truk.plat_no', 
                'karyawan.nama as nama_supir'
            )
            ->orderBy('transaksi_spbe.waktu_transaksi', 'desc')
            ->limit(10) // Ambil 10 transaksi terakhir
            ->get();
    }

    // Ambil detail alokasi (Header + Pangkalan-pangkalan di dalamnya)
    public function getMonitoringDetail($id) {
        $header = DB::table('header_alokasi')
            ->join('spbe', 'header_alokasi.id_spbe', '=', 'spbe.id_spbe')
            ->where('header_alokasi.id', $id)
            ->select('header_alokasi.*', 'spbe.nama_spbe')
            ->first();

        $details = DB::table('alokasi')
            ->join('pangkalan', 'alokasi.id_pangkalan', '=', 'pangkalan.id_pangkalan')
            ->where('alokasi.header_id', $id)
            ->select('alokasi.*', 'pangkalan.nama_pangkalan', 'pangkalan.no_registrasi')
            ->get();

        return response()->json([
            'header' => $header,
            'details' => $details
        ]);
    }

    public function getTrackingDetail($id) {
        $header = DB::table('header_alokasi')
            ->join('spbe', 'header_alokasi.id_spbe', '=', 'spbe.id_spbe')
            ->where('header_alokasi.id', $id)
            ->select('header_alokasi.*', 'spbe.nama_spbe')
            ->first();

        // PERBAIKAN QUERY: Join ke distribusi dan konfirmasi untuk ambil FOTO
        $details = DB::table('alokasi')
            ->join('pangkalan', 'alokasi.id_pangkalan', '=', 'pangkalan.id_pangkalan')
            ->leftJoin('distribusi_pangkalan', 'alokasi.id_alokasi', '=', 'distribusi_pangkalan.id_alokasi')
            ->leftJoin('konfirmasi_pangkalan', 'distribusi_pangkalan.id_distribusi', '=', 'konfirmasi_pangkalan.id_distribusi')
            ->where('alokasi.header_id', $id)
            ->select(
                'alokasi.*', 
                'pangkalan.nama_pangkalan',
                'konfirmasi_pangkalan.foto_bukti',      // <--- Ambil kolom foto
                'konfirmasi_pangkalan.pesan_keterangan', // <--- Ambil kolom catatan
                'konfirmasi_pangkalan.waktu_konfirmasi'
            )
            ->get();

        return response()->json([
            'header' => $header,
            'details' => $details
        ]);
    }

    public function startDistribution(Request $request) {
        $request->validate([
            'header_id' => 'required',
            'id_truk' => 'required',
            'id_supir' => 'required'
        ]);

        DB::beginTransaction();
        try {
            // 1. Ambil semua detail pangkalan dalam alokasi ini
            $details = DB::table('alokasi')->where('header_id', $request->header_id)->get();
            $totalTabung = $details->sum('jumlah_alokasi');

            // 2. Cek Stok Isi di Gudang (TPS Validation)
            $stok = DB::table('stok_gudang')->lockForUpdate()->first();
            if ($stok->jumlah_isi < $totalTabung) {
                return response()->json(['success' => false, 'message' => 'Stok Isi di Gudang tidak cukup!'], 422);
            }

            // 3. Masukkan ke tabel distribusi_pangkalan (untuk history pengiriman)
            foreach ($details as $d) {
                DB::table('distribusi_pangkalan')->insert([
                    'id_alokasi' => $d->id_alokasi,
                    'id_pangkalan' => $d->id_pangkalan,
                    'id_supir' => $request->id_supir,
                    'id_truk' => $request->id_truk,
                    'jumlah_isi_dikirim' => $d->jumlah_alokasi,
                    'waktu_berangkat' => now(),
                    'status_penerimaan' => 'Proses',
                    'created_at' => now()
                ]);
            }

            // 4. Update Stok Gudang (Kurangi Stok Isi karena sudah keluar gudang)
            DB::table('stok_gudang')->where('id_stok', $stok->id_stok)->update([
                'jumlah_isi' => $stok->jumlah_isi - $totalTabung,
                'terakhir_update' => now()
            ]);

            // 5. Update Status Header menjadi 'Distribusi' (Agar Live Tracking Manager berubah)
            DB::table('header_alokasi')->where('id', $request->header_id)->update([
                'status' => 'Distribusi'
            ]);

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Armada telah diberangkatkan!']);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Ambil semua data karyawan
    public function getPersonel() {
        return DB::table('karyawan')->orderBy('nama', 'asc')->get();
    }

    // Simpan karyawan baru
    public function storePersonel(Request $request) {
        $request->validate([
            'nama' => 'required',
            'jabatan' => 'required',
            'no_hp' => 'required'
        ]);

        DB::table('karyawan')->insert([
            'nama' => $request->nama,
            'jabatan' => $request->jabatan,
            'no_hp' => $request->no_hp,
            'created_at' => now(),
            'updated_at' => now()
        ]);

        return response()->json(['success' => true, 'message' => 'Personel berhasil ditambahkan']);
    }

    // 1. Ambil data alokasi pangkalan yang belum dikirim & data personel (Supir/Kernet)
    public function getDispatchPending() {
        $personel = DB::table('karyawan')->get();
        
        // PERBAIKAN: Ambil pangkalan dari batch yang statusnya 'SPBE' ATAU 'Distribusi'
        // Selama pangkalan tersebut belum ada di tabel distribusi_pangkalan, dia harus tetap muncul
        $pangkalans = DB::table('alokasi')
            ->join('header_alokasi', 'alokasi.header_id', '=', 'header_alokasi.id')
            ->join('pangkalan', 'alokasi.id_pangkalan', '=', 'pangkalan.id_pangkalan')
            ->leftjoin('spbe', 'alokasi.id_spbe', '=', 'spbe.id_spbe')
            ->whereIn('header_alokasi.status', ['SPBE', 'Distribusi']) 
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('distribusi_pangkalan')
                    ->whereRaw('distribusi_pangkalan.id_alokasi = alokasi.id_alokasi');
            })
            ->select(
                'alokasi.*', 
                'pangkalan.nama_pangkalan', 
                'pangkalan.alamat', 
                'spbe.nama_spbe',
                'header_alokasi.status as current_batch_status'
            )
            ->get();

        return response()->json([
            'pangkalans' => $pangkalans,
            'supir' => $personel->where('jabatan', 'Supir')->values(), 
            'kernet' => $personel->where('jabatan', 'Kernet')->values(),
            'truk' => DB::table('truk')->get()
        ]);
    }

    // 2. Simpan Penugasan & Berangkatkan
    public function storeDispatchAssignment(Request $request) { 
        $request->validate([
            'assignments' => 'required|array',
            'id_supir'    => 'required',
            'id_truk'     => 'required',
            'header_id'   => 'required',
            'user_id'     => 'required' 
        ]);

        DB::beginTransaction();
        try {
            // 1. Hitung total tabung yang akan dikirim dalam sesi ini
            $totalKirim = 0;
            foreach ($request->assignments as $id_alokasi) {
                $totalKirim += DB::table('alokasi')->where('id_alokasi', $id_alokasi)->value('jumlah_alokasi');
            }

            // 2. Cek apakah Truk punya Stok Isi yang cukup?
            $truk = DB::table('truk')->where('id_truk', $request->id_truk)->lockForUpdate()->first();
            if ($truk->stok_isi < $totalKirim) {
                return response()->json([
                    'success' => false, 
                    'message' => "Gagal! Stok ISI di Truk ({$truk->stok_isi}) tidak cukup untuk mengirim {$totalKirim} tabung. Silahkan muat tabung isi ke truk atau konfirmasi balik dari SPBE."
                ], 422);
            }

            // 3. Proses Keberangkatan
            foreach ($request->assignments as $id_alokasi) {
                $alokasi = DB::table('alokasi')->where('id_alokasi', $id_alokasi)->first();
                
                DB::table('distribusi_pangkalan')->insert([
                    'id_alokasi' => $id_alokasi,
                    'id_pangkalan' => $alokasi->id_pangkalan,
                    'id_supir' => $request->id_supir,
                    'id_truk' => $request->id_truk,
                    'id_kernet' => $request->id_kernet ?? null,
                    'jumlah_isi_dikirim' => $alokasi->jumlah_alokasi,
                    'waktu_berangkat' => now(),
                    'status_penerimaan' => 'Proses',
                    'created_at' => now(),
                    'updated_at' => now()
                ]);
            }

            // 4. Update status header alokasi
            DB::table('header_alokasi')->where('id', $request->header_id)->update(['status' => 'Distribusi']);

            // 5. Log Audit
            $this->logActivity($request->user_id, "Truk {$truk->plat_no} berangkat mengirim {$totalKirim} tabung ke " . count($request->assignments) . " pangkalan.");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    private function logActivity($userId, $aktivitas) {
        DB::table('audit_trail')->insert([
            'id_user'   => $userId,
            'aktivitas' => $aktivitas,
            'waktu_log' => now(),
            'created_at' => now(),
            'updated_at' => now() // Tambahkan ini jika migrasi menggunakan timestamps()
        ]);
    }

    public function storeKonfirmasiPangkalan(Request $request) {
        // 1. Validasi Input
        $request->validate([
            'id_distribusi' => 'required',
            'catatan' => 'nullable',
            'foto' => 'required|image|mimes:jpeg,png,jpg|max:2048',
            'user_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            // 2. Ambil data distribusi terlebih dahulu
            $distribusi = DB::table('distribusi_pangkalan')->where('id_distribusi', $request->id_distribusi)->first();
            
            if (!$distribusi) {
                return response()->json(['success' => false, 'message' => 'Data distribusi tidak ditemukan!'], 404);
            }

            // 3. Ambil data pangkalan (untuk ambil nama buat Audit Log)
            $pangkalan = DB::table('pangkalan')->where('id_pangkalan', $distribusi->id_pangkalan)->first();

            // 4. PERBAIKAN: Ambil data alokasi menggunakan ID yang ada di tabel distribusi
            $alokasi = DB::table('alokasi')->where('id_alokasi', $distribusi->id_alokasi)->first();

            if (!$alokasi) {
                return response()->json(['success' => false, 'message' => 'Data alokasi asal tidak ditemukan!'], 404);
            }

            $jumlah = $alokasi->jumlah_alokasi;
            
            // 5. Simpan Foto
            $path = $request->file('foto')->store('bukti_penerimaan', 'public');

            // 6. Simpan ke Tabel Konfirmasi
            DB::table('konfirmasi_pangkalan')->insert([
                'id_distribusi' => $request->id_distribusi,
                'status' => 'Diterima',
                'pesan_keterangan' => $request->catatan,
                'foto_bukti' => $path,
                'waktu_konfirmasi' => now(),
                'created_at' => now()
            ]);

            // 7. UPDATE STOK SIMULTAN (TPS Mechanism)
            // A. Tambah Stok Isi di Pangkalan
            DB::table('pangkalan')->where('id_pangkalan', $alokasi->id_pangkalan)->increment('stok_saat_ini', $jumlah);
            
            // B. Kurangi stok ISI di Truk dan Tambah stok KOSONG di Truk (Tabung kosong balik ke supir)
            DB::table('truk')->where('id_truk', $distribusi->id_truk)->update([
                'stok_isi' => DB::raw("stok_isi - $jumlah"),
                'stok_kosong' => DB::raw("stok_kosong + $jumlah")
            ]);

            // C. Update stok global gudang (Status aset berubah dari Isi ke Kosong secara sistem)
            DB::table('stok_gudang')->where('id_stok', 1)->update([
                'jumlah_isi' => DB::raw("jumlah_isi - $jumlah"),
                'jumlah_kosong' => DB::raw("jumlah_kosong + $jumlah")
            ]);

            // 8. Update Status di Tabel Distribusi Pangkalan
            DB::table('distribusi_pangkalan')->where('id_distribusi', $request->id_distribusi)->update([
                'status_penerimaan' => 'Diterima'
            ]);

            // 9. Cek apakah seluruh alokasi dalam batch ini sudah selesai (Untuk update status Header)
            $headerId = $alokasi->header_id;
            $totalAlokasi = DB::table('alokasi')->where('header_id', $headerId)->count();
            $totalSelesai = DB::table('distribusi_pangkalan')
                ->join('alokasi', 'distribusi_pangkalan.id_alokasi', '=', 'alokasi.id_alokasi')
                ->where('alokasi.header_id', $headerId)
                ->where('distribusi_pangkalan.status_penerimaan', 'Diterima')
                ->count();

            if ($totalAlokasi === $totalSelesai) {
                DB::table('header_alokasi')->where('id', $headerId)->update(['status' => 'Selesai']);
            }

            // 10. Catat Audit Trail
            $this->logActivity($request->user_id, "Pangkalan {$pangkalan->nama_pangkalan} konfirmasi terima {$jumlah} tabung. Tabung kosong dikembalikan ke Truk.");

            DB::commit();
            return response()->json(['success' => true, 'message' => 'Konfirmasi Berhasil!']);

        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => 'Terjadi kesalahan: ' . $e->getMessage()], 500);
        }
    }

    public function getCustomerHistory($id_user) {
        $pangkalan = DB::table('pangkalan')->where('id_user', $id_user)->first();
        if (!$pangkalan) return response()->json([]);

        return DB::table('konfirmasi_pangkalan')
            ->join('distribusi_pangkalan', 'konfirmasi_pangkalan.id_distribusi', '=', 'distribusi_pangkalan.id_distribusi')
            ->join('alokasi', 'distribusi_pangkalan.id_alokasi', '=', 'alokasi.id_alokasi')
            ->join('spbe', 'alokasi.id_spbe', '=', 'spbe.id_spbe')
            ->where('distribusi_pangkalan.id_pangkalan', $pangkalan->id_pangkalan)
            ->select(
                'konfirmasi_pangkalan.*', 
                'alokasi.jumlah_alokasi', 
                'spbe.nama_spbe', 
                'alokasi.tanggal'
            )
            ->orderBy('konfirmasi_pangkalan.waktu_konfirmasi', 'desc')
            ->get();
    }

    public function getOperatorDispatchHistory() {
        return DB::table('distribusi_pangkalan')
            ->join('pangkalan', 'distribusi_pangkalan.id_pangkalan', '=', 'pangkalan.id_pangkalan')
            ->join('karyawan', 'distribusi_pangkalan.id_supir', '=', 'karyawan.id_karyawan')
            ->join('truk', 'distribusi_pangkalan.id_truk', '=', 'truk.id_truk')
            ->select(
                'distribusi_pangkalan.*', 
                'pangkalan.nama_pangkalan', 
                'karyawan.nama as nama_supir', 
                'truk.plat_no'
            )
            ->orderBy('distribusi_pangkalan.waktu_berangkat', 'desc')
            ->limit(10) // Tampilkan 10 pengiriman terakhir saja
            ->get();
    }

    // Update Stok Langsung di Lantai Tertentu
    public function updateFloorStock(Request $request) {
        $request->validate([
            'lantai' => 'required', // 1, 2, atau 3
            'jenis' => 'required',  // isi atau kosong
            'jumlah' => 'required|numeric',
            'aksi' => 'required',   // tambah atau kurang
            'user_id' => 'required'
        ]);

        $kolom = "lantai_" . $request->lantai . "_" . $request->jenis;
        
        DB::beginTransaction();
        try {
            if($request->aksi === 'tambah') {
                DB::table('stok_gudang')->where('id_stok', 1)->increment($kolom, $request->jumlah);
            } else {
                DB::table('stok_gudang')->where('id_stok', 1)->decrement($kolom, $request->jumlah);
            }

            $this->logActivity($request->user_id, "Operator melakukan penyesuaian stok di Lantai {$request->lantai}: {$request->aksi} {$request->jumlah} tabung {$request->jenis}.");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // Pindah Stok Antar Lantai (Internal Transfer)
    public function moveFloorStock(Request $request) {
        $request->validate([
            'dari_lantai' => 'required',
            'ke_lantai' => 'required',
            'jenis' => 'required',
            'jumlah' => 'required|numeric|min:1',
            'user_id' => 'required'
        ]);

        $kolomAsal = "lantai_" . $request->dari_lantai . "_" . $request->jenis;
        $kolomTujuan = "lantai_" . $request->ke_lantai . "_" . $request->jenis;

        DB::beginTransaction();
        try {
            $stok = DB::table('stok_gudang')->first();
            
            // Validasi apakah stok asal cukup
            if ($stok->$kolomAsal < $request->jumlah) {
                return response()->json(['success' => false, 'message' => 'Stok di lantai asal tidak mencukupi!'], 422);
            }

            // Jalankan Pemindahan
            DB::table('stok_gudang')->where('id_stok', 1)->decrement($kolomAsal, $request->jumlah);
            DB::table('stok_gudang')->where('id_stok', 1)->increment($kolomTujuan, $request->jumlah);

            // Log Audit Khusus Lokasi
            $this->logActivity($request->user_id, "Tabung {$request->jenis} dipindahkan dari Lantai {$request->dari_lantai} ke Lantai {$request->ke_lantai} sebanyak {$request->jumlah} unit.");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function transferStock(Request $request) {
        $request->validate([
            'from_type' => 'required', // 'floor' atau 'truck'
            'from_id'   => 'required', // 1,2,3 (jika floor) atau id_truk
            'to_type'   => 'required', 
            'to_id'     => 'required',
            'qty'       => 'required|numeric|min:1',
            'type'      => 'required', // 'isi' atau 'kosong'
            'user_id'   => 'required'
        ]);

        DB::beginTransaction();
        try {
            // --- 1. KURANGI STOK DI SUMBER ---
            if ($request->from_type === 'floor') {
                $col = "lantai_" . $request->from_id . "_" . $request->type;
                $current = DB::table('stok_gudang')->first()->$col;
                if($current < $request->qty) throw new \Exception("Stok di Lantai {$request->from_id} tidak cukup!");
                DB::table('stok_gudang')->where('id_stok', 1)->decrement($col, $request->qty);
            } else {
                $current = DB::table('truk')->where('id_truk', $request->from_id)->value('stok_' . $request->type);
                if($current < $request->qty) throw new \Exception("Stok di Truk tidak cukup!");
                DB::table('truk')->where('id_truk', $request->from_id)->decrement('stok_' . $request->type, $request->qty);
            }

            // --- 2. TAMBAH STOK DI TUJUAN ---
            if ($request->to_type === 'floor') {
                $col = "lantai_" . $request->to_id . "_" . $request->type;
                DB::table('stok_gudang')->where('id_stok', 1)->increment($col, $request->qty);
            } else {
                DB::table('truk')->where('id_truk', $request->to_id)->increment('stok_' . $request->type, $request->qty);
            }

            // --- 3. AUDIT TRAIL ---
            $this->logActivity($request->user_id, "Pemindahan {$request->qty} tabung {$request->type} dari {$request->from_type} {$request->from_id} ke {$request->to_type} {$request->to_id}");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 422);
        }
    }

    public function getPangkalanDashboard($id_user) {
        $pangkalan = DB::table('pangkalan')
            ->where('id_user', $id_user)
            ->first(); // Pastikan di sini mengambil semua kolom termasuk stok_titipan

        if (!$pangkalan) return response()->json(['message' => 'Data tidak ditemukan'], 404);

        $lastDelivery = DB::table('konfirmasi_pangkalan')
            ->join('distribusi_pangkalan', 'konfirmasi_pangkalan.id_distribusi', '=', 'distribusi_pangkalan.id_distribusi')
            ->where('distribusi_pangkalan.id_pangkalan', $pangkalan->id_pangkalan)
            ->orderBy('konfirmasi_pangkalan.waktu_konfirmasi', 'desc')
            ->first();

        return response()->json([
            'info' => $pangkalan, // Ini membawa data stok_saat_ini dan stok_titipan
            'last_delivery' => $lastDelivery
        ]);
    }

    public function unloadTruck(Request $request) {
        $request->validate([
            'id_truk' => 'required',
            'lantai_tujuan' => 'required', // 1, 2, atau 3
            'user_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $truk = DB::table('truk')->where('id_truk', $request->id_truk)->first();
            $qtyKosong = $truk->stok_kosong;

            if ($qtyKosong <= 0) {
                throw new \Exception("Truk ini tidak membawa tabung kosong untuk dibongkar.");
            }

            // 1. Pindahkan stok kosong dari Truk ke Lantai Gudang pilihan
            $colLantai = "lantai_" . $request->lantai_tujuan . "_kosong";
            
            DB::table('stok_gudang')->where('id_stok', 1)->increment($colLantai, $qtyKosong);
            
            // 2. Reset stok di Truk menjadi 0 (Truk sekarang kosong/standby)
            DB::table('truk')->where('id_truk', $request->id_truk)->update([
                'stok_isi' => 0,
                'stok_kosong' => 0
            ]);

            // 3. Update status distribusi pangkalan yang terkait truk ini menjadi 'Selesai'
            // (Artinya perjalanan pulang-pergi supir sudah tuntas)
            DB::table('distribusi_pangkalan')
                ->where('id_truk', $request->id_truk)
                ->where('status_penerimaan', 'Diterima')
                ->update(['status_penerimaan' => 'Selesai']);

            // 4. Log Audit
            $this->logActivity($request->user_id, "Rekonsiliasi: Truk {$truk->plat_no} kembali ke gudang. Bongkar {$qtyKosong} tabung kosong ke Lantai {$request->lantai_tujuan}.");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function confirmTruckReturn(Request $request) {
        $request->validate([
            'id_distribusi' => 'required',
            'to_type' => 'required', // 'floor' atau 'truck'
            'to_id' => 'required',   // ID Lantai (1,2,3) atau ID Truk
            'jenis_stok' => 'required', 
            'user_id' => 'required'
        ]);

        DB::beginTransaction();
        try {
            $dist = DB::table('distribusi_pangkalan')->where('id_distribusi', $request->id_distribusi)->first();
            $qty = $dist->jumlah_isi_dikirim;
            $trukAsalId = $dist->id_truk;
            $trukAsal = DB::table('truk')->where('id_truk', $trukAsalId)->first();

            // 1. Tentukan kondisi: Apakah disimpan di truk yang sama?
            $isSameTruck = ($request->to_type === 'truck' && $request->to_id == $trukAsalId);

            // 2. Eksekusi Perpindahan Stok Fisik
            if (!$isSameTruck) {
                // Jika pindah ke tempat lain, kurangi dari truk yang baru pulang
                $kolomTrukAsal = ($request->jenis_stok === 'isi') ? 'stok_isi' : 'stok_kosong';
                DB::table('truk')->where('id_truk', $trukAsalId)->decrement($kolomTrukAsal, $qty);

                if ($request->to_type === 'floor') {
                    // TUJUAN: LANTAI GUDANG
                    $colLantai = "lantai_" . $request->to_id . "_" . $request->jenis_stok;
                    DB::table('stok_gudang')->where('id_stok', 1)->increment($colLantai, $qty);
                } else {
                    // TUJUAN: TRUK LAIN (Cross-Docking)
                    $kolomTrukTujuan = ($request->jenis_stok === 'isi') ? 'stok_isi' : 'stok_kosong';
                    DB::table('truk')->where('id_truk', $request->to_id)->increment($kolomTrukTujuan, $qty);
                }
            }

            // 3. Update Saldo Global (Status Aset Berubah)
            if ($request->jenis_stok === 'isi') {
                // Kasus Ditolak (Barang Titipan)
                DB::table('stok_gudang')->where('id_stok', 1)->increment('jumlah_titipan', $qty);
                $lokasiTeks = $isSameTruck ? "Truk yang sama ({$trukAsal->plat_no})" : $request->to_type . " ID: " . $request->to_id;
                $aktivitas = "Konfirmasi Pulang: Menyimpan RETUR ISI di " . $lokasiTeks;
            } else {
                // Kasus Normal (Diterima -> Pangkalan ambil Isi, balikin Kosong)
                // Aset Internal: Isi berkurang, Kosong bertambah
                DB::table('stok_gudang')->where('id_stok', 1)->update([
                    'jumlah_isi' => DB::raw("jumlah_isi - $qty"),
                    'jumlah_kosong' => DB::raw("jumlah_kosong + $qty")
                ]);
                $lokasiTeks = $isSameTruck ? "Truk yang sama ({$trukAsal->plat_no})" : $request->to_type . " ID: " . $request->to_id;
                $aktivitas = "Konfirmasi Pulang: Bongkar TABUNG KOSONG ke " . $lokasiTeks;
            }

            // 4. Selesaikan Transaksi Distribusi
            DB::table('distribusi_pangkalan')->where('id_distribusi', $request->id_distribusi)->update([
                'status_penerimaan' => 'Selesai'
            ]);

            // 5. Log Activity
            $this->logActivity($request->user_id, $aktivitas . " sejumlah {$qty} unit.");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    public function getExistingAllocations(Request $request)
    {
        $tanggal = $request->query('tanggal');
        
        // Ambil daftar id_pangkalan yang sudah menerima alokasi di tanggal tersebut
        $existingIds = DB::table('alokasi')
            ->where('tanggal', $tanggal)
            ->pluck('id_pangkalan');

        return response()->json($existingIds);
    }

    // 1. Ambil seluruh posisi stok sistem saat ini untuk formulir audit
    public function getSystemStockAudit() {
        $gudang = DB::table('stok_gudang')->where('id_stok', 1)->first();
        $trucks = DB::table('truk')->get();
        $pangkalanTotal = DB::table('pangkalan')->sum('stok_saat_ini');

        return response()->json([
            'warehouse' => $gudang,
            'trucks' => $trucks,
            'pangkalan_total' => (int)$pangkalanTotal
        ]);
    }

    // 2. Simpan hasil rekonsiliasi dan update saldo sistem
    public function submitReconciliation(Request $request) {

        $request->validate([
            'user_id' => 'required',
            'catatan' => 'required',
            'items' => 'required|array'
        ]);

        DB::beginTransaction();
        try {
            // 1. Simpan ke tabel rekonsiliasi
            $rekonId = DB::table('rekonsiliasi')->insertGetId([
                'tanggal' => now(),
                'id_user_gudang' => $request->user_id,
                'catatan_selisih' => $request->catatan,
                'created_at' => now(),
                'updated_at' => now()
            ]);

            // 2. Simpan detail per lokasi
            foreach ($request->items as $item) {
                DB::table('detail_fisik_stok')->insert([
                    'id_rekon' => $rekonId,
                    'lokasi' => $item['nama'],
                    'jumlah_isi_fisik' => $item['isi_fisik'],
                    'jumlah_stok_fisik' => $item['kosong_fisik'],
                    'created_at' => now(),
                    'updated_at' => now()
                ]);

                // 3. Update saldo sistem agar sinkron dengan fisik (Audit Adjustment)
                if ($item['type'] === 'floor') {
                    DB::table('stok_gudang')->where('id_stok', 1)->update([
                        "lantai_" . $item['id'] . "_isi" => $item['isi_fisik'],
                        "lantai_" . $item['id'] . "_kosong" => $item['kosong_fisik']
                    ]);
                } else {
                    DB::table('truk')->where('id_truk', $item['id'])->update([
                        'stok_isi' => $item['isi_fisik'],
                        'stok_kosong' => $item['kosong_fisik']
                    ]);
                }
            }

            // 4. Hitung ulang total global gudang
            $g = DB::table('stok_gudang')->where('id_stok', 1)->first();
            DB::table('stok_gudang')->where('id_stok', 1)->update([
                'jumlah_isi' => $g->lantai_1_isi + $g->lantai_2_isi + $g->lantai_3_isi,
                'jumlah_kosong' => $g->lantai_1_kosong + $g->lantai_2_kosong + $g->lantai_3_kosong,
            ]);

            DB::commit();
            return response()->json(['success' => true]);

        } catch (\Exception $e) {
            DB::rollback();
            // Mengirimkan pesan error asli ke Vue
            return response()->json([
                'success' => false, 
                'message' => 'Kesalahan Database: ' . $e->getMessage()
            ], 500);
        }
    }

    public function rejectDistribution(Request $request) {
        $request->validate([
            'id_distribusi' => 'required',
            'alasan' => 'required',
            'user_id' => 'required' // ID Pangkalan yang login
        ]);

        DB::beginTransaction();
        try {
            $dist = DB::table('distribusi_pangkalan')->where('id_distribusi', $request->id_distribusi)->first();
            $pangkalan = DB::table('pangkalan')->where('id_pangkalan', $dist->id_pangkalan)->first();

            // 1. Tambah stok titipan
            DB::table('pangkalan')->where('id_pangkalan', $dist->id_pangkalan)->increment('stok_titipan', $dist->jumlah_isi_dikirim);

            // 2. Update status
            DB::table('distribusi_pangkalan')->where('id_distribusi', $request->id_distribusi)->update(['status_penerimaan' => 'Ditolak']);

            // 3. Record Konfirmasi
            DB::table('konfirmasi_pangkalan')->insert([
                'id_distribusi' => $request->id_distribusi,
                'status' => 'Ditolak',
                'pesan_keterangan' => 'PENOLAKAN: ' . $request->alasan,
                'waktu_konfirmasi' => now(),
                'created_at' => now()
            ]);

            // --- TAMBAHKAN LOG ACTIVITY DI SINI ---
            $this->logActivity($request->user_id, "Pangkalan {$pangkalan->nama_pangkalan} MENOLAK kiriman ({$dist->jumlah_isi_dikirim} tabung). Alasan: {$request->alasan}. Stok dialihkan ke TITIPAN.");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 1. Simpan Penjualan & Catat Riwayat
    public function updateUsagePangkalan(Request $request) {
        $request->validate(['user_id' => 'required', 'jumlah_jual' => 'required|numeric|min:1']);

        DB::beginTransaction();
        try {
            $pangkalan = DB::table('pangkalan')->where('id_user', $request->user_id)->first();

            // Potong stok fisik di pangkalan
            DB::table('pangkalan')->where('id_pangkalan', $pangkalan->id_pangkalan)->decrement('stok_saat_ini', $request->jumlah_jual);

            // CATAT KE TABEL RIWAYAT PENJUALAN
            DB::table('pangkalan_usage')->insert([
                'id_pangkalan' => $pangkalan->id_pangkalan,
                'jumlah_terjual' => $request->jumlah_jual,
                'tanggal' => now()->format('Y-m-d'),
                'created_at' => now()
            ]);

            $this->logActivity($request->user_id, "Pangkalan {$pangkalan->nama_pangkalan} melaporkan penjualan {$request->jumlah_jual} tabung.");

            DB::commit();
            return response()->json(['success' => true]);
        } catch (\Exception $e) {
            DB::rollback();
            return response()->json(['success' => false, 'message' => $e->getMessage()], 500);
        }
    }

    // 2. Ambil Riwayat Pergerakan Stok (Masuk & Keluar)
    public function getPangkalanStockMovement($id_user) {
        $pangkalan = DB::table('pangkalan')->where('id_user', $id_user)->first();

        // Ambil riwayat barang MASUK (dari konfirmasi_pangkalan)
        $masuk = DB::table('konfirmasi_pangkalan')
            ->join('distribusi_pangkalan', 'konfirmasi_pangkalan.id_distribusi', '=', 'distribusi_pangkalan.id_distribusi')
            ->where('distribusi_pangkalan.id_pangkalan', $pangkalan->id_pangkalan)
            ->where('konfirmasi_pangkalan.status', 'Diterima')
            ->select('konfirmasi_pangkalan.waktu_konfirmasi as tanggal', DB::raw("'MASUK' as tipe"), 'distribusi_pangkalan.jumlah_isi_dikirim as jumlah');

        // Ambil riwayat barang KELUAR (dari pangkalan_usage)
        $keluar = DB::table('pangkalan_usage')
            ->where('id_pangkalan', $pangkalan->id_pangkalan)
            ->select('created_at as tanggal', DB::raw("'KELUAR' as tipe"), 'jumlah_terjual as jumlah');

        // Gabungkan dan urutkan berdasarkan waktu terbaru
        $history = $masuk->union($keluar)->orderBy('tanggal', 'desc')->get();

        return response()->json([
            'info' => $pangkalan,
            'history' => $history
        ]);
    }


}