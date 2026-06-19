<template>
    <div class="operator-container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="title-block">
                <h1>Ekspedisi & Pengambilan SPBE</h1>
                <p>Eksekusi pengambilan tabung isi per armada truk.</p>
            </div>
            <div class="current-date">
                <span class="label">Tanggal Operasional</span>
                <span class="value">{{ today }}</span>
            </div>
        </div>

        <!-- TASK LIST -->
        <div v-for="task in tasks" :key="task.id" class="allocation-batch">
            <div class="batch-header">
                <div class="spbe-info">
                    <span class="badge-blue">SUMBER: {{ task.nama_spbe }}</span>
                    <h2>Total Target: {{ task.total_rencana_isi }} Tabung</h2>
                </div>
                <div class="progress-text">
                    Proses: {{ task.truk_tereksekusi }} / {{ task.jumlah_truk }} Armada Selesai
                </div>
            </div>

            <!-- GRID SLOT TRUK -->
            <div class="truck-slots">
                <div v-for="n in task.jumlah_truk" :key="n" class="slot-card" 
                     :class="{ 'completed': n <= task.truk_tereksekusi, 'next': n === task.truk_tereksekusi + 1 }">
                    
                    <div class="slot-icon">
                        <svg v-if="n <= task.truk_tereksekusi" xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    </div>

                    <div class="slot-details">
                        <h4>Armada Ke-{{ n }}</h4>
                        <p v-if="n <= task.truk_tereksekusi">Status: <b>Sudah di SPBE</b></p>
                        <p v-else-if="n === task.truk_tereksekusi + 1">Status: <b>Siap Diberangkatkan</b></p>
                        <p v-else>Status: <b>Menunggu Giliran</b></p>
                    </div>

                    <!-- Tombol hanya muncul di slot berikutnya yang belum diisi -->
                    <button 
                        v-if="n === task.truk_tereksekusi + 1" 
                        @click="openModal(task)" 
                        class="btn-slot-execute"
                    >
                        Isi Realisasi
                    </button>
                </div>
            </div>
        </div>

        <!-- EMPTY STATE -->
        <div v-if="tasks.length === 0" class="empty-state-card">
            <div class="empty-icon">✅</div>
            <h3>Semua Tugas Selesai</h3>
            <p>Tidak ada antrean pengambilan SPBE.</p>
        </div>

                    <!-- TABEL RIWAYAT TRANSAKSI SPBE (Baru) -->
            <div class="history-section mt-40">
                <div class="section-title">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"></path></svg>
                    <h3>Riwayat Penukaran SPBE Terbaru</h3>
                </div>
                
                <div class="card history-card">
                    <table class="modern-table">
                        <thead>
                            <tr>
                                <th>Waktu</th>
                                <th>Sumber SPBE</th>
                                <th>Armada / Supir</th>
                                <th class="text-right">Jumlah Isi</th>
                            </tr>
                        </thead>
                        <tbody>
                            <tr v-if="history.length === 0">
                                <td colspan="4" class="text-center">Belum ada transaksi terekam.</td>
                            </tr>
                            <tr v-for="h in history" :key="h.id_trans_spbe">
                                <td>
                                    <div class="t-date">{{ formatDate(h.waktu_transaksi) }}</div>
                                    <div class="t-time">{{ formatTime(h.waktu_transaksi) }}</div>
                                </td>
                                <td><b>{{ h.nama_spbe }}</b></td>
                                <td>
                                    <div class="t-plat">{{ h.plat_no }}</div>
                                    <div class="t-driver">{{ h.nama_supir }}</div>
                                </td>
                                <td class="text-right">
                                    <span class="qty-pill">+ {{ h.jumlah_isi_masuk }} Unit</span>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

        <!-- MODAL INPUT REALISASI -->
        <div v-if="showModal" class="modal-overlay">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Konfirmasi Muatan per Armada</h3>
                    <button @click="showModal = false" class="close-btn">&times;</button>
                </div>
                
                <div class="modal-body">
                    <!-- Teks Target diubah agar tidak membingungkan -->
                    <div class="alert-box">
                        Target Muatan per Truk: <strong>560 Tabung (1 DO)</strong>
                    </div>

                    <div class="form-group">
                        <label>Pilih Armada Truk</label>
                        <select v-model="form.id_truk" class="modal-input" @change="handleTruckChange">
                            <option value="">-- Pilih Truk --</option>
                            <option v-for="t in fleets.truk" :key="t.id_truk" :value="t.id_truk">
                                {{ t.plat_no }} (Muatan Kosong: {{ t.stok_kosong }})
                            </option>
                        </select>

                        <!-- PERBAIKAN: Bandingkan dengan form.jumlah (560), bukan total batch -->
                        <div v-if="form.id_truk && selectedTruckStock < form.jumlah" class="error-msg">
                            ⚠️ Truk ini hanya membawa {{ selectedTruckStock }} tabung kosong. 
                            Dibutuhkan minimal 560 tabung.
                        </div>
                    </div>

                    <div class="form-group">
                        <label>Pilih Supir Bertugas</label>
                        <select v-model="form.id_karyawan" class="modal-input">
                            <option value="">-- Pilih Supir --</option>
                            <option v-for="s in fleets.supir" 
                                    :key="s.id_karyawan" 
                                    :value="s.id_karyawan"
                                    :disabled="s.status_tugas === 'BERTUGAS'">
                                {{ s.nama }} {{ s.status_tugas === 'BERTUGAS' ? '(SEDANG DI JALAN)' : '' }}
                            </option>
                        </select>
                        
                        <!-- Pesan Peringatan -->
                        <p v-if="isSupirSelectedBusy" class="error-msg">
                            ⚠️ Supir ini sedang tidak berada di tempat.
                        </p>
                    </div>

                    <div class="form-group">
                        <label>Jumlah yang akan ditukar (Isi)</label>
                        <!-- Nilainya dikunci di 560 -->
                        <input type="number" v-model.number="form.jumlah" class="modal-input bg-gray" readonly>
                    </div>
                </div>

                <div class="modal-footer">
                    <!-- PERBAIKAN: Tombol menyala jika stok truk >= 560 -->
                    <button 
                        @click="submitExecution" 
                        :disabled="submitting || !form.id_truk || !form.id_karyawan || selectedTruckStock < form.jumlah" 
                        class="btn-confirm"
                    >
                        {{ submitting ? 'Memproses...' : 'Konfirmasi & Terima 560 Tabung Isi' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const tasks = ref([]);
const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', year: 'numeric', month: 'long', day: 'numeric' });

const showModal = ref(false);
const submitting = ref(false);
const selectedTask = ref(null);
const selectedTruckStock = ref(0); // State untuk memantau stok truk terpilih
const fleets = reactive({ truk: [], supir: [] });
const history = ref([]);

const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: '2-digit', month: 'short' });
const formatTime = (t) => new Date(t).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

const form = reactive({
    id_truk: '',
    id_karyawan: '',
    jumlah: 0
});

const warehouseStock = ref({ isi: 0, kosong: 0 });

const fetchData = async () => {
    try {
        // 1. Ambil Antrean Tugas
        const resTask = await axios.get('/api/operator-tasks');
        tasks.value = resTask.data;

        // 2. Ambil Riwayat Penukaran (Baru)
        const resHistory = await axios.get('/api/operator-spbe-history');
        history.value = resHistory.data;

        // 3. Ambil info stok gudang
        const resStok = await axios.get('/api/fleet-resources');
        warehouseStock.value = {
            isi: resStok.data.stok_saat_ini.jumlah_isi,
            kosong: resStok.data.stok_saat_ini.jumlah_kosong
        };
    } catch (e) {
        console.error("Gagal memuat data");
    }
};

const checkStock = (needed) => warehouseStock.value.kosong >= needed;
const getStockPercentage = (needed) => Math.min((warehouseStock.value.kosong / needed) * 100, 100);

const handleTruckChange = () => {
    const truck = fleets.truk.find(t => t.id_truk === form.id_truk);
    selectedTruckStock.value = truck ? truck.stok_kosong : 0;
};

const openModal = async (task) => {
    selectedTask.value = task;
    
    // PAKSA ke 560 karena kita memproses per truk (1 DO)
    form.jumlah = 560; 
    
    try {
        const res = await axios.get('/api/fleet-resources');
        fleets.truk = res.data.truk;
        fleets.supir = res.data.supir;
        
        // Reset pilihan truk setiap buka modal agar validasi refresh
        form.id_truk = '';
        selectedTruckStock.value = 0;
        
        showModal.value = true;
    } catch (e) {
        alert("Gagal mengambil data armada.");
    }
};

const submitExecution = async () => {
    if(!form.id_truk || !form.id_karyawan) return alert("Lengkapi data!");

    const userSession = JSON.parse(sessionStorage.getItem('user'));
    submitting.value = true;

    try {
        const payload = {
            id_header: selectedTask.value.id,
            id_spbe: selectedTask.value.id_spbe,
            id_truk: form.id_truk,
            id_karyawan: form.id_karyawan,
            jumlah: form.jumlah,
            user_id: userSession.id
        };

        const res = await axios.post('/api/transaksi-spbe-store', payload);
        if(res.data.success) {
            alert("Transaksi SPBE Berhasil! Stok truk telah dikonversi menjadi ISI.");
            showModal.value = false;
            fetchData(); // PANGGIL INI agar tugas yang baru saja selesai HILANG dari daftar
        }
    } catch (e) {
        alert(e.response?.data?.message || "Gagal menyimpan");
    } finally {
        submitting.value = false;
    }
};

const isSupirSelectedBusy = computed(() => {
    const supir = fleets.supir.find(s => s.id_karyawan === form.id_karyawan);
    return supir ? supir.status_tugas === 'BERTUGAS' : false;
});

onMounted(fetchData);
</script>

<style scoped>
.operator-container { padding: 25px; max-width: 1200px; margin: 0 auto; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.title-block h1 { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0; }
.title-block p { color: #64748b; margin-top: 5px; font-size: 14px; }

.current-date { text-align: right; }
.current-date .label { display: block; font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.current-date .value { font-size: 14px; font-weight: 600; color: #1e40af; }

/* FILTER */
.filter-bar { margin-bottom: 25px; }
.search-box { 
    display: flex; align-items: center; gap: 10px; 
    background: white; border: 1px solid #e2e8f0; 
    padding: 10px 15px; border-radius: 12px; width: 300px;
}
.search-box input { border: none; outline: none; width: 100%; font-size: 14px; }

/* TASK CARD */
.task-grid { display: grid; grid-template-columns: repeat(auto-fill, minmax(450px, 1fr)); gap: 25px; }
.task-card { 
    background: white; border-radius: 20px; border: 1px solid #e2e8f0; 
    overflow: hidden; transition: all 0.3s; 
}
.task-card:hover { border-color: #2563eb; box-shadow: 0 15px 30px rgba(0,0,0,0.05); }

.task-card-header { 
    padding: 20px; border-bottom: 1px solid #f1f5f9; 
    display: flex; justify-content: space-between; align-items: center; 
}
.spbe-badge { 
    background: #eff6ff; color: #2563eb; 
    padding: 6px 12px; border-radius: 8px; 
    font-size: 13px; font-weight: 700; display: flex; align-items: center; gap: 8px;
}
.status-tag { font-size: 12px; color: #f59e0b; font-weight: 700; }

.task-card-body { padding: 25px; }
.main-metrics { display: flex; gap: 40px; margin-bottom: 25px; }
.metric .m-label { display: block; font-size: 12px; color: #64748b; font-weight: 600; margin-bottom: 5px; }
.metric .m-value { font-size: 28px; font-weight: 800; color: #1e293b; }
.metric .m-value small { font-size: 14px; font-weight: 500; color: #94a3b8; }
.text-blue { color: #2563eb !important; }

/* READINESS CHECK */
.readiness-check { padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; }
.readiness-check.ready { background: #f0fdf4; border-color: #bcf0da; }
.readiness-check.not-ready { background: #fff1f2; border-color: #fecdd3; }

.check-header { display: flex; justify-content: space-between; margin-bottom: 10px; }
.check-title { font-size: 12px; font-weight: 700; color: #334155; }
.check-status { font-size: 11px; font-weight: 800; text-transform: uppercase; }
.ready .check-status { color: #16a34a; }
.not-ready .check-status { color: #dc2626; }

.progress-bar { height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; margin-bottom: 10px; }
.progress-fill { height: 100%; transition: 0.5s; }
.ready .progress-fill { background: #22c55e; }
.not-ready .progress-fill { background: #ef4444; }
.check-desc { font-size: 12px; color: #64748b; margin: 0; }

/* FOOTER */
.task-card-footer { padding: 20px 25px; background: #f8fafc; }
.btn-execute { 
    width: 100%; background: #2563eb; color: white; border: none; 
    padding: 15px; border-radius: 12px; font-weight: 700; 
    cursor: pointer; transition: 0.2s; 
}
.btn-execute:hover:not(:disabled) { background: #1e40af; transform: translateY(-2px); }
.btn-execute:disabled { background: #cbd5e1; cursor: not-allowed; }

.ref-id { font-size: 11px; color: #94a3b8; text-align: center; margin-top: 12px; }

/* UTILS */
.empty-state-card { 
    background: white; padding: 60px; text-align: center; 
    border-radius: 20px; border: 2px dashed #e2e8f0; color: #94a3b8; 
}
.empty-icon { font-size: 40px; margin-bottom: 15px; }

/* Tambahkan style modal */
.modal-overlay {
    position: fixed; top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000;
}
.modal-card { background: white; width: 450px; border-radius: 20px; overflow: hidden; animation: slideUp 0.3s ease; }
.modal-header { padding: 20px; background: #f8fafc; border-bottom: 1px solid #e2e8f0; display: flex; justify-content: space-between; }
.modal-body { padding: 25px; }
.alert-box { background: #eff6ff; padding: 15px; border-radius: 10px; color: #1e40af; margin-bottom: 20px; font-size: 14px; }
.form-group { margin-bottom: 15px; }
.modal-input { width: 100%; padding: 12px; border: 1px solid #e2e8f0; border-radius: 10px; margin-top: 5px; }
.btn-confirm { width: 100%; background: #2563eb; color: white; padding: 15px; border: none; border-radius: 12px; font-weight: bold; cursor: pointer; }

.error-msg { color: #e11d48; font-size: 12px; margin-top: 8px; font-weight: 600; background: #fff1f2; padding: 8px; border-radius: 6px; }
.status-tag.pending { background: #fef3c7; color: #d97706; }
.empty-state-card { background: white; padding: 60px; text-align: center; border-radius: 24px; border: 2px dashed #e2e8f0; color: #94a3b8; margin: 40px auto; max-width: 500px; }
.empty-icon { font-size: 50px; margin-bottom: 10px; }

.allocation-batch {
    background: white;
    border-radius: 20px;
    padding: 25px;
    margin-bottom: 30px;
    border: 1px solid #e2e8f0;
}

.batch-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 25px;
    border-bottom: 2px dashed #f1f5f9;
    padding-bottom: 15px;
}

.batch-header h2 { font-size: 20px; font-weight: 800; color: #1e3a8a; }

.truck-slots {
    display: grid;
    grid-template-columns: repeat(auto-fill, minmax(280px, 1fr));
    gap: 20px;
}

.slot-card {
    padding: 20px;
    border-radius: 16px;
    display: flex;
    align-items: center;
    gap: 15px;
    border: 1px solid #e2e8f0;
    background: #f8fafc;
    position: relative;
}

.slot-card.completed {
    background: #f0fdf4;
    border-color: #bcf0da;
    color: #16a34a;
}

.slot-card.next {
    border: 2px solid #2563eb;
    background: white;
    box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.1);
}

.slot-icon {
    width: 50px;
    height: 50px;
    border-radius: 12px;
    background: #fff;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 6px rgba(0,0,0,0.05);
}

.completed .slot-icon { color: #16a34a; }
.next .slot-icon { color: #2563eb; }

.slot-details h4 { margin: 0; font-size: 16px; }
.slot-details p { margin: 2px 0 0; font-size: 12px; color: #64748b; }

.btn-slot-execute {
    margin-left: auto;
    background: #2563eb;
    color: white;
    border: none;
    padding: 8px 15px;
    border-radius: 8px;
    font-weight: 700;
    cursor: pointer;
}

.btn-slot-execute:hover { background: #1e40af; }

.mt-40 { margin-top: 40px; }
.history-card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; overflow: hidden; padding: 0 !important; }
.modern-table { width: 100%; border-collapse: collapse; }
.modern-table th { text-align: left; padding: 15px 20px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
.modern-table td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }

.t-date { font-weight: 700; color: #1e293b; }
.t-time { font-size: 11px; color: #94a3b8; }
.t-plat { font-weight: 800; color: #1e3a8a; font-family: monospace; }
.t-driver { font-size: 11px; color: #64748b; }
.qty-pill { background: #f0fdf4; color: #16a34a; padding: 4px 12px; border-radius: 6px; font-weight: 800; }

@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>