<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Barryvdh\DomPDF\Facade\Pdf;
use Maatwebsite\Excel\Facades\Excel;

class ExportController extends Controller
{
    // 1. Export PDF Laporan Alokasi (Surat Jalan)
    public function exportAlokasiPDF($id)
        {
            $header = DB::table('header_alokasi')
                ->join('spbe', 'header_alokasi.id_spbe', '=', 'spbe.id_spbe')
                ->where('header_alokasi.id', $id)
                ->first();

            $details = DB::table('alokasi')
                ->join('pangkalan', 'alokasi.id_pangkalan', '=', 'pangkalan.id_pangkalan')
                ->where('alokasi.header_id', $id)
                ->get();

            if (!$header) {
                return "Data tidak ditemukan";
            }

            // LANGKAH SAKTI: Ubah Blade menjadi HTML murni dulu
            $html = view('exports.alokasi_pdf', [
                'header' => $header,
                'details' => $details
            ])->render();

            // Masukkan HTML tadi ke mesin PDF
            $pdf = Pdf::loadHTML($html);

            // Atur ukuran kertas (Opsional: A4, Portrait)
            $pdf->setPaper('a4', 'portrait');

            return $pdf->download('Laporan_Distribusi_#'.$id.'.pdf');
        }

    // 2. Export Excel Audit Trail (Log Aktivitas)
    public function exportAuditExcel(Request $request) {
        $start = $request->query('start');
        $end = $request->query('end');

        $data = DB::table('audit_trail')
            ->join('users', 'audit_trail.id_user', '=', 'users.id')
            ->whereBetween('audit_trail.waktu_log', [
                $start . " 00:00:00", 
                $end . " 23:59:59"
            ])
            ->select('audit_trail.waktu_log', 'users.name', 'users.role', 'audit_trail.aktivitas')
            ->orderBy('audit_trail.waktu_log', 'asc')
            ->get();

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function collection() { return $this->data; }
            public function headings(): array {
                return ["Waktu Kejadian", "Nama Pelaksana", "Role", "Detail Aktivitas"];
            }
        }, "Audit_Trail_{$start}_to_{$end}.xlsx");
    }

    public function exportRekapExcel(Request $request) {
        $start = $request->query('start');
        $end = $request->query('end');

        $data = DB::table('distribusi_pangkalan')
            ->join('pangkalan', 'distribusi_pangkalan.id_pangkalan', '=', 'pangkalan.id_pangkalan')
            ->join('alokasi', 'distribusi_pangkalan.id_alokasi', '=', 'alokasi.id_alokasi')
            ->join('spbe', 'alokasi.id_spbe', '=', 'spbe.id_spbe')
            ->whereBetween('alokasi.tanggal', [$start, $end])
            ->select(
                'alokasi.tanggal',
                'pangkalan.nama_pangkalan',
                'spbe.nama_spbe',
                'distribusi_pangkalan.jumlah_isi_dikirim',
                'distribusi_pangkalan.status_penerimaan'
            )
            ->get();

        return Excel::download(new class($data) implements \Maatwebsite\Excel\Concerns\FromCollection, \Maatwebsite\Excel\Concerns\WithHeadings {
            private $data;
            public function __construct($data) { $this->data = $data; }
            public function collection() { return $this->data; }
            public function headings(): array {
                return ["Tanggal", "Nama Pangkalan", "Sumber SPBE", "Jumlah (Tabung)", "Status Akhir"];
            }
        }, "Rekap_Distribusi_{$start}_to_{$end}.xlsx");
    }
}