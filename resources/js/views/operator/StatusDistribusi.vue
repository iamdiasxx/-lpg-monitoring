<template>
    <div class="dispatch-container">
        <div class="page-header">
            <div class="title">
                <h1>Penugasan & Keberangkatan</h1>
                <p>Pilih pangkalan dan tugaskan personel pengiriman.</p>
            </div>
        </div>

        <div class="main-layout">
            <!-- SISI KIRI: DAFTAR PANGKALAN DARI MANAGER -->
            <div class="pangkalan-list card">
                <div class="card-header">
                    <h3>Antrean Pangkalan (Ready)</h3>
                    <span class="count">{{ pendingPangkalans.length }} Lokasi</span>
                </div>
                <div class="list-body">
                    <div v-for="p in pendingPangkalans" :key="p.id_alokasi" class="p-item-check">
                        <input type="checkbox" :id="'p-' + p.id_alokasi" v-model="selectedPangkalan" :value="p">
                        <label :for="'p-' + p.id_alokasi">
                            <span class="p-name">{{ p.nama_pangkalan }}</span>
                            <span class="p-qty">{{ p.jumlah_alokasi }} Tabung</span>
                        </label>
                    </div>
                </div>
            </div>

            <!-- SISI KANAN: FORM PENUGASAN (SUPIR & KERNET) -->
            <div class="assign-form card">
                <h3>Form Penugasan Armada</h3>
                <div class="form-body">
                    <div class="form-group">
                        <label>Armada Truk</label>
                        <select v-model="form.id_truk" @change="updateSelectedTruckStock">
                            <option value="">-- Pilih Truk --</option>
                            <option v-for="t in resources.truk" 
                                    :key="t.id_truk" 
                                    :value="'truck-'+t.id_truk"
                                    :disabled="t.status_jalan === 'DI JALAN'">
                                {{ t.plat_no }} (Tersedia: {{ t.stok_isi }} Isi)
                            </option>
                        </select>
                        
                        <!-- Info Validasi Kapasitas -->
                        <p v-if="form.id_truk && selectedTruckStock < totalSelectedQty" class="error-text">
                            ⚠️ Stok Isi di truk ini ({{ selectedTruckStock }}) tidak cukup untuk mengirim {{ totalSelectedQty }} tabung!
                        </p>
                    </div>
                    <div class="form-group">
                        <label>Pilih Supir (Data dari Manager)</label>
                        <select v-model="form.id_supir">
                            <option value="">-- Pilih Supir --</option>
                            <option v-for="s in resources.supir" :key="s.id_karyawan" :value="s.id_karyawan">{{ s.nama }}</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>Pilih Kernet (Data dari Manager)</label>
                        <select v-model="form.id_kernet">
                            <option value="">-- Pilih Kernet --</option>
                            <option v-for="k in resources.kernet" :key="k.id_karyawan" :value="k.id_karyawan">{{ k.nama }}</option>
                        </select>
                    </div>

                    <div class="summary-box" v-if="selectedPangkalan.length > 0">
                        <div class="sum-row">
                            <span>Total Lokasi:</span> <b>{{ selectedPangkalan.length }} Pangkalan</b>
                        </div>
                        <div class="sum-row">
                            <span>Total Tabung:</span> <b>{{ totalSelectedQty }} Tabung Isi</b>
                        </div>
                    </div>

                    <button @click="submitAssignment" class="btn-dispatch" :disabled="selectedPangkalan.length === 0">
                        Konfirmasi & Truk Berangkat
                    </button>
                </div>
            </div>
        </div>

        <div class="history-section card" style="margin-top: 30px;">
            <h3 style="margin-bottom: 15px;">🚚 Pengiriman Terbaru (Hari Ini)</h3>
            <table class="history-table">
                <thead>
                    <tr>
                        <th>Waktu</th>
                        <th>Pangkalan</th>
                        <th>Tabung</th>
                        <th>Armada / Supir</th>
                        <th>Status</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="h in history" :key="h.id_distribusi">
                        <td>{{ formatTime(h.waktu_berangkat) }}</td>
                        <td><b>{{ h.nama_pangkalan }}</b></td>
                        <td><span class="qty-pill">{{ h.jumlah_isi_dikirim }}</span></td>
                        <td>{{ h.plat_no }} / {{ h.nama_supir }}</td>
                        <td>
                            <!-- Status Badge -->
                            <span class="badge" :class="h.status_penerimaan.toLowerCase()">
                                {{ h.status_penerimaan }}
                            </span>

                            <!-- TOMBOL AKSI DINAMIS -->
                            <template v-if="h.status_penerimaan === 'Diterima'">
                                <button @click="openReturnModal(h, 'kosong')" class="btn-return-action">
                                    Bongkar Kosong
                                </button>
                            </template>

                            <template v-else-if="h.status_penerimaan === 'Ditolak'">
                                <button @click="openReturnModal(h, 'isi')" class="btn-return-action btn-red">
                                    Bongkar Retur Isi
                                </button>
                            </template>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- MODAL BONGKAR MUAT DINAMIS (Ditaruh di paling bawah) -->
        <div v-if="showReturnModal" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>Konfirmasi Bongkar Muat</h3>
                    <span class="badge-type" :class="currentReturnType">
                        MENGELOLA TABUNG {{ currentReturnType.toUpperCase() }}
                    </span>
                </div>
                <div class="modal-body">
                    <p>Pangkalan: <b>{{ selectedHistory.nama_pangkalan }}</b></p>
                    <p>Jumlah: <b>{{ selectedHistory.jumlah_isi_dikirim }} Unit</b></p>
                    
                    <div class="form-group mt-15">
                        <label>Pindahkan Ke Lokasi:</label>
                        <select v-model="targetLocation" class="modal-select">
                            <option value="" disabled>-- Pilih Lokasi Tujuan --</option>
                            <optgroup label="Gudang">
                                <option value="floor-1">Lantai 1 (Loading)</option>
                                <option value="floor-2">Lantai 2 (Storage)</option>
                                <option value="floor-3">Lantai 3 (Buffer)</option>
                            </optgroup>
                            <optgroup label="Truk Lain">
                                <option v-for="t in resources.truk" :key="t.id_truk" :value="'truck-'+t.id_truk">
                                    {{ t.plat_no }}
                                </option>
                            </optgroup>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="showReturnModal = false" class="btn-cancel">Batal</button>
                    <button @click="submitReturn" class="btn-confirm-return">Eksekusi Perpindahan</button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import axios from 'axios';

const pendingPangkalans = ref([]);
const resources = reactive({ supir: [], kernet: [], truk: [] });
const selectedPangkalan = ref([]);
const history = ref([]);
const selectedTruckStock = ref(0);
const showReturnModal = ref(false);
const selectedHistory = ref(null);
const targetLocation = ref('');
const currentReturnType = ref('');

const form = reactive({
    id_truk: '',
    id_supir: '',
    id_kernet: ''
});

// --- FUNGSI FORMAT WAKTU (Tambahkan ini untuk memperbaiki error) ---
const formatTime = (t) => {
    if (!t) return '-';
    return new Date(t).toLocaleTimeString('id-ID', { 
        hour: '2-digit', 
        minute: '2-digit' 
    }) + ' WIB';
};

const openReturnModal = (h, type) => {
    selectedHistory.value = h;
    currentReturnType.value = type; // Tentukan apakah mau bongkar ISI atau KOSONG
    targetLocation.value = '';
    showReturnModal.value = true;
};

const totalSelectedQty = computed(() => {
    return selectedPangkalan.value.reduce((acc, curr) => acc + curr.jumlah_alokasi, 0);
});

const fetchData = async () => {
    try {
        // 1. Ambil Antrean Pangkalan
        const res = await axios.get('/api/dispatch-pending');
        pendingPangkalans.value = res.data.pangkalans;
        resources.supir = res.data.supir;
        resources.kernet = res.data.kernet;
        resources.truk = res.data.truk;

        // 2. Ambil Riwayat Pengiriman (Yang barusan kamu buat rutenya)
        const resHistory = await axios.get('/api/operator-dispatch-history');
        history.value = resHistory.data;
    } catch (e) {
        console.error("Gagal memuat data:", e);
    }
};

const updateSelectedTruckStock = () => {
    // Kita hapus kata 'truck-' untuk mendapatkan ID aslinya
    const truckId = form.id_truk.replace('truck-', '');
    
    // Cari di resources.truk (pastikan menggunakan id_truk)
    const truck = resources.truk.find(t => t.id_truk == truckId);
    selectedTruckStock.value = truck ? truck.stok_isi : 0;
};

const submitAssignment = async () => {
    if(!form.id_truk || !form.id_supir) return alert("Pilih Truk dan Supir!");
    if(selectedPangkalan.value.length === 0) return alert("Pilih pangkalan yang akan dikirim!");

    // Validasi stok sekali lagi sebelum kirim
    if (selectedTruckStock.value < totalSelectedQty.value) {
        return alert("Stok isi di truk tidak cukup!");
    }

    const userSession = JSON.parse(sessionStorage.getItem('user'));

    try {
        await axios.post('/api/dispatch-assign', {
            header_id: selectedPangkalan.value[0].header_id,
            assignments: selectedPangkalan.value.map(p => p.id_alokasi),
            // Kirim ID asli tanpa prefix 'truck-'
            id_truk: form.id_truk.replace('truck-', ''), 
            id_supir: form.id_supir,
            id_kernet: form.id_kernet,
            user_id: userSession.id
        });
        
        alert("Truk Berhasil Diberangkatkan!");
        selectedPangkalan.value = []; // Reset pilihan
        fetchData(); // Refresh antrean dan history
    } catch (e) {
        alert("Gagal: " + (e.response?.data?.message || "Internal Error"));
    }
};

const submitReturn = async () => {
    const user = JSON.parse(sessionStorage.getItem('user'));
    try {
        const [type, id] = targetLocation.value.split('-');
        await axios.post('/api/truck/confirm-return', {
            id_distribusi: selectedHistory.value.id_distribusi,
            to_type: type,
            to_id: id,
            jenis_stok: currentReturnType.value, // Kirim jenis stok ke backend
            user_id: user.id
        });

        alert("Bongkar muat berhasil diselesaikan.");
        showReturnModal.value = false;
        fetchData(); 
    } catch (e) {
        alert(e.response?.data?.message || "Gagal");
    }
};

onMounted(fetchData);
</script>

<style scoped>
.main-layout { display: grid; grid-template-columns: 1fr 400px; gap: 25px; margin-top: 20px; }
.card { background: white; border-radius: 15px; border: 1px solid #e2e8f0; padding: 20px; }
.p-item-check { display: flex; align-items: center; gap: 15px; padding: 15px; border-bottom: 1px solid #f8fafc; }
.p-item-check label { display: flex; justify-content: space-between; width: 100%; cursor: pointer; }
.p-name { font-weight: 700; color: #1e3a8a; }
.p-qty { font-weight: 800; color: #2563eb; }
.form-group { margin-bottom: 20px; }
.form-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 8px; color: #475569; }
.form-group select { width: 100%; padding: 12px; border: 1.5px solid #d1e9ff; border-radius: 10px; }
.summary-box { background: #f0f7ff; padding: 15px; border-radius: 10px; margin-bottom: 20px; }
.sum-row { display: flex; justify-content: space-between; font-size: 14px; margin-bottom: 5px; }
.btn-dispatch { width: 100%; padding: 15px; background: #10b981; color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; }

.history-table { width: 100%; border-collapse: collapse; margin-top: 10px; }
.history-table th { text-align: left; padding: 12px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; border-bottom: 2px solid #e2e8f0; }
.history-table td { padding: 12px; border-bottom: 1px solid #f1f5f9; font-size: 13px; }

.qty-pill { background: #eff6ff; color: #2563eb; padding: 3px 8px; border-radius: 6px; font-weight: 800; }
.badge { font-size: 10px; font-weight: 800; padding: 3px 8px; border-radius: 4px; text-transform: uppercase; }
.badge.proses { background: #fef3c7; color: #d97706; } /* Warna Kuning untuk On the way */
.badge.diterima { background: #dcfce7; color: #15803d; } /* Warna Hijau untuk Sampai */

.error-text {
    color: #e11d48;
    font-size: 12px;
    font-weight: 600;
    margin-top: 8px;
    background: #fff1f2;
    padding: 8px;
    border-radius: 6px;
}

.btn-dispatch:disabled {
    background: #cbd5e1;
    cursor: not-allowed;
}

.btn-return-action {
    margin-left: 10px;
    background: #1e293b;
    color: white;
    border: none;
    padding: 5px 10px;
    border-radius: 6px;
    font-size: 11px;
    cursor: pointer;
}
.modal-select { width: 100%; padding: 10px; margin: 15px 0; border-radius: 8px; border: 1px solid #ddd; }
.btn-confirm-return { background: #2563eb; color: white; border: none; padding: 10px; border-radius: 8px; cursor: pointer; }
/* ... (Style modal overlay sama seperti sebelumnya) ... */

/* MODAL STYLES */
.modal-overlay {
    position: fixed;
    top: 0;
    left: 0;
    width: 100%;
    height: 100%;
    background: rgba(0, 0, 0, 0.6); /* Warna gelap transparan */
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 9999; /* Pastikan di atas segalanya */
}

.modal-box {
    background: white;
    width: 90%;
    max-width: 450px;
    padding: 30px;
    border-radius: 20px;
    box-shadow: 0 15px 30px rgba(0, 0, 0, 0.2);
}

.modal-header {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 20px;
}

.close-x {
    background: none;
    border: none;
    font-size: 24px;
    cursor: pointer;
    color: #94a3b8;
}

.modal-select {
    width: 100%;
    padding: 12px;
    border: 1px solid #d1e9ff;
    border-radius: 10px;
    margin-top: 10px;
    font-size: 14px;
}

.modal-btns {
    display: flex;
    gap: 10px;
    margin-top: 25px;
}

.modal-btns button:first-child {
    flex: 1;
    padding: 12px;
    background: #f1f5f9;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 600;
}

.btn-confirm-return {
    flex: 2;
    padding: 12px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 10px;
    cursor: pointer;
    font-weight: 700;
}

/* Fix untuk dropdown truk di form atas */
/* Ubah v-for="t in trucks" menjadi v-for="t in resources.truk" di template kamu */

.btn-return-action {
    background: #1e293b;
    color: white;
    border: none;
    padding: 6px 12px;
    border-radius: 6px;
    font-size: 11px;
    cursor: pointer;
    margin-left: 10px;
}

.btn-red { background: #dc2626 !important; }
.badge-type { font-size: 10px; padding: 4px 8px; border-radius: 4px; font-weight: 800; }
.badge-type.isi { background: #dbeafe; color: #1e40af; }
.badge-type.kosong { background: #f1f5f9; color: #475569; }
.mt-15 { margin-top: 15px; }
</style>