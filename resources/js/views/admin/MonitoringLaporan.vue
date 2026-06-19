<template>
    <div class="monitoring-container">
        <!-- HEADER SECTION -->
        <div class="page-header">
            <div class="title-area">
                <h1>Monitoring Distribusi</h1>
                <p>Pantau sirkulasi stok LPG secara real-time dari hulu ke hilir.</p>
            </div>
            <div class="action-area">
                <button @click="fetchData" class="btn-refresh" :class="{ 'rotate': loading }">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6m-7 10a9 9 0 1 1 3-7.7L23 10"/></svg>
                    Refresh Data
                </button>
            </div>
        </div>

        <!-- REKOMENDASI: STATS OVERVIEW (Agar Manager langsung tahu angka besar hari ini) -->
        <div class="stats-overview">
            <div class="stat-box blue">
                <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"/></svg></div>
                <div class="stat-info">
                    <span class="st-label">Total LPG Didistribusi</span>
                    <span class="st-value">{{ totalLpg }} <small>Tabung</small></span>
                </div>
            </div>
            <div class="stat-box orange">
                <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg></div>
                <div class="stat-info">
                    <span class="st-label">Total Armada Keluar</span>
                    <span class="st-value">{{ totalTruk }} <small>Truk</small></span>
                </div>
            </div>
            <div class="stat-box green">
                <div class="stat-icon"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg></div>
                <div class="stat-info">
                    <span class="st-label">Alokasi Terselesaikan</span>
                    <span class="st-value">{{ reports.length }} <small>Batch</small></span>
                </div>
            </div>
        </div>

        <!-- FILTER & SEARCH AREA -->
        <div class="filter-card">
            <div class="search-input">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" v-model="search" placeholder="Cari berdasarkan nama SPBE...">
            </div>
        </div>

        <!-- MAIN REPORTS GRID -->
        <div v-if="loading" class="skeleton-wrapper">
             <div v-for="i in 3" :key="i" class="skeleton-card"></div>
        </div>

        <div v-else-if="filteredReports.length === 0" class="empty-state">
            <img src="https://illustrations.popsy.co/blue/searching.svg" alt="Empty" width="150">
            <h3>Data Tidak Ditemukan</h3>
            <p>Tidak ada laporan distribusi yang sesuai dengan pencarian Anda.</p>
        </div>

        <div v-else class="reports-list">
            <div v-for="report in reports" :key="report.id" class="report-item">
                <div class="ri-date">
                    <span class="day">{{ getDay(report.tanggal) }}</span>
                    <span class="month">{{ getMonth(report.tanggal) }}</span>
                </div>
                
                <div class="ri-content">
                    <div class="ri-header">
                        <div class="spbe-info">
                            <h3>{{ report.nama_spbe }}</h3>
                            <span class="status-pill" :class="report.status.toLowerCase()">{{ report.status }}</span>
                        </div>
                        <div class="status-indicator">
                            <span class="dot pulse"></span> Terkunci & Aktif
                        </div>
                    </div>

                    <div class="ri-details">
                        <div class="det">
                            <span class="det-label">Volume Distribusi</span>
                            <span class="det-value">{{ report.total_rencana_isi }} <b>Tabung</b></span>
                        </div>
                        <div class="det">
                            <span class="det-label">Kapasitas Armada</span>
                            <span class="det-value">{{ report.jumlah_truk }} <b>Truk (DO)</b></span>
                        </div>
                        <div class="det">
                            <span class="det-label">Waktu Penetapan</span>
                            <span class="det-value">{{ formatTime(report.created_at) }}</span>
                        </div>
                    </div>
                </div>

                <div class="ri-action">
                    <button @click="openTracking(report.id)" class="btn-track">
                        Track Posisi Stok
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL TRACKING STEPPER -->
        <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
            <div class="modal-tracking">
                <div class="modal-header">
                    <h3>Live Tracking Alokasi #{{ trackingData.header.id }}</h3>
                    <button @click="showModal = false" class="close-x">&times;</button>
                    <button @click="printPDF(trackingData.header.id)" class="btn-print-pdf">
                        📄 Download PDF
                    </button>
                </div>
                
                <div class="modal-body">
                    <!-- STEPPER VISUAL -->
                    <div class="stepper-wrapper">
                        <div class="step" :class="getStatusClass(1)">
                            <div class="step-icon">📝</div>
                            <span>Alokasi</span>
                        </div>
                        <div class="step-line" :class="getStatusClass(1)"></div>
                        <div class="step" :class="getStatusClass(2)">
                            <div class="step-icon">⛽</div>
                            <span>SPBE</span>
                        </div>
                        <div class="step-line" :class="getStatusClass(2)"></div>
                        <div class="step" :class="getStatusClass(3)">
                            <div class="step-icon">🚚</div>
                            <span>Distribusi</span>
                        </div>
                        <div class="step-line" :class="getStatusClass(3)"></div>
                        <div class="step" :class="getStatusClass(4)">
                            <div class="step-icon">🏠</div>
                            <span>Selesai</span>
                        </div>
                    </div>

                    <!-- DETAIL PANGKALAN -->
                    <div class="pangkalan-status-list">
                        <h4>Status Penerimaan Pangkalan</h4>
                        <div v-for="p in trackingData.details" :key="p.id" class="p-item">
                            <div class="p-info">
                                <span class="block font-bold">{{ p.nama_pangkalan }}</span>
                                <span class="p-qty">{{ p.jumlah_alokasi }} Tabung</span>
                            </div>
                            
                            <div class="p-action">
                                <span v-if="!p.waktu_konfirmasi" class="p-badge pending">Belum Diterima</span>
                                
                                <!-- JIKA SUDAH TERIMA, MUNCUL TOMBOL LIHAT FOTO -->
                                <button v-else @click="viewPhoto(p.foto_bukti, p.pesan_keterangan)" class="btn-view-photo">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 19a2 2 0 0 1-2 2H3a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h4l2-3h6l2 3h4a2 2 0 0 1 2 2z"/><circle cx="12" cy="13" r="4"/></svg>
                                    Lihat Bukti
                                </button>
                            </div>
                        </div>
                    </div>
                </div>
                                        
            </div>
        </div>

        <!-- MODAL OVERLAY UNTUK ZOOM FOTO -->
        <div v-if="showPhotoModal" class="modal-overlay photo-zoom" @click.self="showPhotoModal = false">
            <div class="modal-photo-card">
                <div class="photo-header">
                    <h3>Bukti Serah Terima Fisik</h3>
                    <button @click="showPhotoModal = false" class="close-x">&times;</button>
                </div>
                <div class="photo-body">
                    <img :src="selectedPhoto" alt="Bukti Foto" class="full-img">
                    <div class="note-box">
                        <label>Catatan Pangkalan:</label>
                        <p>{{ selectedNote }}</p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const reports = ref([]);
const loading = ref(true);
const search = ref('');
const showModal = ref(false);
const trackingData = ref({ header: {}, details: [] });
const showPhotoModal = ref(false);
const selectedPhoto = ref('');
const selectedNote = ref('');

const viewPhoto = (path, note) => {
    if (!path) {
        selectedPhoto.value = 'https://via.placeholder.com/400x300?text=Foto+Tidak+Tersedia';
    } else {
        // Kita hanya butuh nama filenya saja (misal: CC85zv...jpg)
        // Karena di database tersimpan: 'bukti_penerimaan/CC85zv...jpg'
        const filename = path.split('/').pop();
        
        // Kita arahkan ke rute baru kita: /display-bukti/nama_file.jpg
        selectedPhoto.value = `/display-bukti/${filename}`;
    }
    selectedNote.value = note || 'Tidak ada catatan.';
    showPhotoModal.value = true;
};

const fetchData = async () => {
    loading.value = true; // Mulai loading
    try {
        const res = await axios.get('/api/admin-monitoring');
        reports.value = res.data;
    } catch (e) {
        console.error("Gagal mengambil data monitoring");
    } finally {
        loading.value = false; // WAJIB: Matikan loading baik sukses maupun gagal
    }
};

const printPDF = (id) => {
    window.open(`/api/export/alokasi-pdf/${id}`, '_blank');
};

// COMPUTED STATS
const totalLpg = computed(() => reports.value.reduce((acc, curr) => acc + curr.total_rencana_isi, 0));
const totalTruk = computed(() => reports.value.reduce((acc, curr) => acc + curr.jumlah_truk, 0));

const filteredReports = computed(() => {
    return reports.value.filter(r => r.nama_spbe.toLowerCase().includes(search.value.toLowerCase()));
});

// FORMATTERS
const getDay = (d) => new Date(d).getDate();
const getMonth = (d) => new Date(d).toLocaleString('id-ID', { month: 'short' }).toUpperCase();
const formatTime = (t) => new Date(t).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' }) + ' WIB';

const openTracking = async (id) => {
    const res = await axios.get(`/api/tracking-detail/${id}`);
    trackingData.value = res.data;
    showModal.value = true;
};

const getStatusClass = (step) => {
    const status = trackingData.value.header.status;
    const map = { 'Pending': 1, 'SPBE': 2, 'Distribusi': 3, 'Selesai': 4 };
    const currentStep = map[status] || 1;
    if (currentStep > step) return 'completed';
    if (currentStep === step) return 'active';
    return 'upcoming';
};

onMounted(fetchData);
</script>

<style scoped>
.monitoring-container { padding: 20px; color: #1e293b; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
.title-area h1 { font-size: 28px; font-weight: 800; color: #0f172a; margin: 0; }
.title-area p { color: #64748b; margin: 5px 0 0 0; font-size: 14px; }

.btn-refresh {
    display: flex; align-items: center; gap: 8px;
    background: white; border: 1px solid #e2e8f0; padding: 10px 18px;
    border-radius: 12px; cursor: pointer; font-weight: 600; color: #475569;
    transition: all 0.2s;
}
.btn-refresh:hover { background: #f8fafc; border-color: #cbd5e1; color: #2563eb; }
.rotate svg { animation: spin 1s linear infinite; }
@keyframes spin { from { transform: rotate(0deg); } to { transform: rotate(360deg); } }

/* STATS OVERVIEW */
.stats-overview { display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 20px; margin-bottom: 30px; }
.stat-box {
    background: white; padding: 20px; border-radius: 16px; display: flex; align-items: center; gap: 15px;
    border: 1px solid #e2e8f0; transition: transform 0.2s;
}
.stat-box:hover { transform: translateY(-3px); }
.stat-icon { width: 50px; height: 50px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.blue .stat-icon { background: #eff6ff; color: #2563eb; }
.orange .stat-icon { background: #fff7ed; color: #f97316; }
.green .stat-icon { background: #f0fdf4; color: #22c55e; }

.st-label { display: block; font-size: 12px; color: #64748b; font-weight: 600; }
.st-value { display: block; font-size: 22px; font-weight: 800; color: #0f172a; }
.st-value small { font-size: 13px; font-weight: 500; color: #94a3b8; }

/* FILTER */
.filter-card { background: white; padding: 15px; border-radius: 12px; border: 1px solid #e2e8f0; margin-bottom: 20px; }
.search-input { display: flex; align-items: center; gap: 10px; color: #94a3b8; }
.search-input input { border: none; outline: none; width: 100%; font-size: 15px; color: #1e293b; }

/* REPORT LIST (Modern Row Style) */
.reports-list { display: flex; flex-direction: column; gap: 15px; }
.report-item {
    background: white; border-radius: 16px; border: 1px solid #e2e8f0;
    display: flex; align-items: center; padding: 20px; transition: all 0.3s;
}
.report-item:hover { border-color: #2563eb; box-shadow: 0 10px 25px rgba(37, 99, 235, 0.08); }

.ri-date {
    display: flex; flex-direction: column; align-items: center; justify-content: center;
    background: #f8fafc; padding: 10px; border-radius: 12px; min-width: 65px; border: 1px solid #f1f5f9;
}
.ri-date .day { font-size: 22px; font-weight: 800; color: #2563eb; line-height: 1; }
.ri-date .month { font-size: 11px; font-weight: 700; color: #64748b; margin-top: 4px; }

.ri-content { flex: 1; padding: 0 30px; }
.ri-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 15px; }
.spbe-info h3 { margin: 0; font-size: 18px; font-weight: 800; color: #1e3a8a; }
.id-tag { font-size: 11px; font-weight: 700; color: #94a3b8; letter-spacing: 0.5px; }

.status-indicator { display: flex; align-items: center; gap: 8px; font-size: 12px; font-weight: 700; color: #15803d; }
.dot { width: 8px; height: 8px; background: #22c55e; border-radius: 50%; }
.pulse { animation: pulse-green 2s infinite; }

@keyframes pulse-green {
	0% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0.7); }
	70% { transform: scale(1); box-shadow: 0 0 0 6px rgba(34, 197, 94, 0); }
	100% { transform: scale(0.95); box-shadow: 0 0 0 0 rgba(34, 197, 94, 0); }
}

.ri-details { display: flex; gap: 40px; }
.det-label { display: block; font-size: 11px; color: #94a3b8; font-weight: 600; text-transform: uppercase; margin-bottom: 4px; }
.det-value { display: block; font-size: 15px; font-weight: 700; color: #334155; }
.det-value b { color: #94a3b8; font-weight: 500; font-size: 13px; margin-left: 3px; }

.btn-detail {
    display: flex; align-items: center; gap: 8px;
    background: #f1f5f9; color: #475569; border: none; padding: 10px 16px;
    border-radius: 10px; font-weight: 700; font-size: 13px; cursor: pointer; transition: 0.2s;
}
.btn-detail:hover { background: #2563eb; color: white; }

/* UTILS */
.empty-state { text-align: center; padding: 60px; }
.empty-state h3 { margin: 15px 0 5px; color: #1e293b; }
.empty-state p { color: #64748b; font-size: 14px; }

/* SKELETON LOADING */
.skeleton-card { height: 120px; background: #edf2f7; border-radius: 16px; margin-bottom: 15px; animation: pulse 1.5s infinite; }
@keyframes pulse { 0% { opacity: 0.6; } 50% { opacity: 1; } 100% { opacity: 0.6; } }

@media (max-width: 768px) {
    .ri-details { flex-direction: column; gap: 10px; }
    .report-item { flex-direction: column; align-items: flex-start; gap: 20px; }
    .ri-content { padding: 0; }
    .btn-detail { width: 100%; justify-content: center; }
}

/* Tambahkan Style Stepper */
.stepper-wrapper { display: flex; align-items: center; justify-content: space-between; margin-bottom: 40px; padding: 20px 0; }
.step { text-align: center; display: flex; flex-direction: column; align-items: center; gap: 8px; flex: 1; }
.step-icon { width: 45px; height: 45px; border-radius: 50%; background: #f1f5f9; display: flex; align-items: center; justify-content: center; font-size: 20px; transition: 0.3s; border: 3px solid #fff; box-shadow: 0 0 0 2px #e2e8f0; }
.step.active .step-icon { background: #2563eb; color: white; box-shadow: 0 0 0 2px #2563eb; }
.step.completed .step-icon { background: #10b981; color: white; box-shadow: 0 0 0 2px #10b981; }
.step span { font-size: 11px; font-weight: 700; color: #64748b; text-transform: uppercase; }

.step-line { flex: 1; height: 4px; background: #e2e8f0; margin-top: -25px; }
.step-line.completed { background: #10b981; }

/* Status Pill */
.status-pill { font-size: 10px; padding: 3px 10px; border-radius: 20px; font-weight: 800; text-transform: uppercase; }
.status-pill.pending { background: #fef3c7; color: #d97706; }
.status-pill.spbe { background: #dcfce7; color: #15803d; }
.status-pill.distribusi { background: #dbeafe; color: #1e40af; }

/* Pangkalan List */
.pangkalan-status-list h4 { font-size: 14px; color: #1e293b; margin-bottom: 15px; border-bottom: 1px solid #f1f5f9; padding-bottom: 10px; }
.p-item { display: flex; justify-content: space-between; align-items: center; padding: 10px 0; border-bottom: 1px solid #f8fafc; font-size: 13px; }
.p-qty { font-weight: 700; color: #64748b; }
.p-badge { font-size: 10px; padding: 2px 8px; border-radius: 4px; font-weight: 700; }
.p-badge.received { background: #dcfce7; color: #15803d; }
.p-badge.pending { background: #f1f5f9; color: #94a3b8; }

/* Modal Styles */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); z-index: 1000; display: flex; align-items: center; justify-content: center; }
.modal-tracking { background: white; width: 600px; border-radius: 20px; padding: 30px; box-shadow: 0 20px 40px rgba(0,0,0,0.2); }
.modal-header { display: flex; justify-content: space-between; margin-bottom: 20px; }
.btn-track { background: #f0f7ff; color: #2563eb; border: 1px solid #d1e9ff; padding: 8px 15px; border-radius: 10px; cursor: pointer; font-weight: 700; display: flex; align-items: center; gap: 8px; }

.btn-view-photo {
    background: #ecfdf5;
    color: #10b981;
    border: 1px solid #10b981;
    padding: 5px 12px;
    border-radius: 6px;
    font-size: 11px;
    font-weight: 700;
    cursor: pointer;
    display: flex;
    align-items: center;
    gap: 5px;
}

.btn-view-photo:hover {
    background: #10b981;
    color: white;
}

/* Overlay Hitam Transparan */
.photo-zoom {
    background: rgba(15, 23, 42, 0.85) !important;
    backdrop-filter: blur(4px);
    z-index: 9999;
}

/* Kotak Putih Modal */
.modal-photo-card {
    background: white;
    width: 95%;
    max-width: 500px;
    border-radius: 24px;
    overflow: hidden;
    box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.5);
    animation: zoomIn 0.3s ease-out;
}

.photo-header {
    padding: 20px 25px;
    display: flex;
    justify-content: space-between;
    align-items: center;
    border-bottom: 1px solid #f1f5f9;
}

.photo-header h3 {
    font-size: 18px;
    font-weight: 800;
    color: #1e3a8a;
    margin: 0;
}

/* Frame Foto */
.photo-body {
    padding: 0;
    background: #f8fafc;
}

.full-img {
    width: 100%;
    display: block;
    max-height: 450px;
    object-fit: contain; /* Agar foto tidak terpotong */
    background: #000; /* Background hitam di belakang foto jika foto tegak */
}

/* Kotak Catatan */
.note-box {
    padding: 25px;
    background: white;
    border-top: 1px solid #f1f5f9;
}

.note-box label {
    font-size: 11px;
    font-weight: 800;
    color: #94a3b8;
    text-transform: uppercase;
    letter-spacing: 1px;
    display: block;
    margin-bottom: 8px;
}

.note-box p {
    margin: 0;
    color: #334155;
    font-size: 15px;
    line-height: 1.6;
}

/* Tombol Close Modern */
.close-x {
    background: #f1f5f9;
    border: none;
    width: 35px;
    height: 35px;
    border-radius: 10px;
    font-size: 20px;
    cursor: pointer;
    color: #64748b;
    display: flex;
    align-items: center;
    justify-content: center;
    transition: 0.2s;
}

.close-x:hover {
    background: #fee2e2;
    color: #ef4444;
}

@keyframes zoomIn {
    from { opacity: 0; transform: scale(0.95); }
    to { opacity: 1; transform: scale(1); }
}
</style>