
<template>
    <div class="pangkalan-container">
        <!-- HEADER SECTION -->
        <header class="page-header">
            <div class="title-block">
                <h1>Konfirmasi Kedatangan</h1>
                <p>Validasi fisik tabung LPG yang Anda terima.</p>
            </div>
            <div class="status-summary">
                <div class="pulse-dot"></div>
                <span>Sistem Monitoring Aktif</span>
            </div>
        </header>

        <!-- LOADING STATE -->
        <div v-if="loading" class="skeleton-wrapper">
            <div class="skeleton-card"></div>
            <div class="skeleton-card"></div>
        </div>

        <!-- EMPTY STATE -->
        <div v-else-if="notifs.length === 0" class="empty-state">
            <div class="empty-img">
                <svg xmlns="http://www.w3.org/2000/svg" width="64" height="64" viewBox="0 0 24 24" fill="none" stroke="#cbd5e1" stroke-width="1" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            </div>
            <h3>Tidak Ada Jadwal Pengiriman</h3>
            <p>Belum ada distribusi yang menuju lokasi Anda saat ini.</p>
        </div>

        <!-- LIST PENGIRIMAN (KARTU TUGAS MODERN) -->
        <div v-else class="shipment-grid">
            <div v-for="n in notifs" :key="n.id_alokasi" class="shipment-card">
                <div class="card-status-header">
                    <span class="status-badge" :class="getDisplayStatus(n).class">
                        {{ getDisplayStatus(n).label }}
                    </span>
                    <span class="date-text">{{ n.tanggal }}</span>
                </div>

                <div class="card-main-content">
                    <div class="qty-section">
                        <div class="qty-circle">
                            <span class="qty-num">{{ n.jumlah_alokasi }}</span>
                            <span class="qty-unit">UNIT</span>
                        </div>
                    </div>
                    
                    <div class="info-section">
                        <label>SUMBER PENGIRIMAN</label>
                        <h4>{{ n.nama_spbe || 'STOK TITIPAN GUDANG' }}</h4>
                        
                        <div class="delivery-note" :class="{ 'in-transit': n.id_distribusi }">
                            <svg v-if="!n.id_distribusi" xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg>
                            <svg v-else xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
                            <span>{{ n.id_distribusi ? 'Truk sedang menuju lokasi Anda.' : 'Menunggu jadwal keberangkatan supir.' }}</span>
                        </div>
                    </div>
                </div>

                <div class="card-actions">
                    <button @click="openRejectModal(n)" class="btn-outline-red">Tolak</button>
                    <button 
                        @click="openConfirmModal(n)" 
                        class="btn-primary-blue"
                        :disabled="!n.id_distribusi"
                    >
                        {{ n.id_distribusi ? 'Konfirmasi Terima' : 'Belum Berangkat' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- MODAL REJECT (ALASAN) -->
        <div v-if="showRejectModal" class="modal-overlay" @click.self="showRejectModal = false">
            <div class="modal-box anim-up">
                <div class="modal-header">
                    <h3>Laporan Penolakan</h3>
                    <button @click="showRejectModal = false" class="btn-close-text">&times;</button>
                </div>
                <div class="modal-body">
                    <p class="modal-hint">Mohon pilih alasan penolakan untuk dikirim ke Manager.</p>
                    <div class="f-group">
                        <label>Alasan Utama</label>
                        <select v-model="rejectReason" class="modern-input">
                            <option value="Tabung Bocor / Rusak">Tabung Bocor / Rusak</option>
                            <option value="Jumlah Tidak Sesuai">Jumlah Tidak Sesuai</option>
                            <option value="Pangkalan Tutup">Pangkalan Tutup</option>
                            <option value="Lainnya">Lainnya (Tulis di catatan)</option>
                        </select>
                    </div>
                    <div class="f-group">
                        <label>Catatan Tambahan</label>
                        <textarea v-model="rejectNote" class="modern-input" placeholder="Jelaskan detail kendala..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="submitReject" class="btn-final-red">Kirim Laporan Penolakan</button>
                </div>
            </div>
        </div>

        <!-- MODAL CONFIRM (FOTO) -->
        <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
            <div class="modal-box anim-up">
                <div class="modal-header">
                    <h3>Serah Terima Barang</h3>
                    <button @click="showModal = false" class="btn-close-text">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="info-banner">
                        Pastikan Anda telah menerima <b>{{ selectedItem.jumlah_alokasi }} Tabung Isi</b>.
                    </div>
                    <div class="f-group">
                        <label>Foto Bukti Penerimaan</label>
                        <div class="upload-box" @click="$refs.fileInput.click()">
                            <div v-if="!form.preview" class="upload-trigger">
                                <div class="cam-icon">📸</div>
                                <span>Klik untuk ambil foto</span>
                            </div>
                            <img v-else :src="form.preview" class="preview-img">
                            <input type="file" ref="fileInput" @change="onFileChange" accept="image/*" capture="camera" hidden>
                        </div>
                    </div>
                    <div class="f-group">
                        <label>Catatan Opsional</label>
                        <textarea v-model="form.catatan" class="modern-input" placeholder="Contoh: Kondisi segel baik..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="submitKonfirmasi" :disabled="submitting || !form.foto" class="btn-final-blue">
                        {{ submitting ? 'Memproses...' : 'Konfirmasi & Update Stok' }}
                    </button>
                </div>
            </div>
        </div>

        <!-- HISTORY SECTION (DIPERBAIKI VISUALNYA) -->
        <div class="history-section">
            <div class="section-title">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                <h3>Riwayat Penerimaan</h3>
            </div>

            <div class="history-list">
                <div v-if="history.length === 0" class="no-history-card">Belum ada riwayat transaksi.</div>
                <div v-for="h in history" :key="h.id_konfirmasi" class="history-row-card">
                    <div class="h-date-box">
                        <span class="h-day">{{ h.tanggal.split('-')[2] }}</span>
                        <span class="h-month">{{ h.tanggal.split('-')[1] }}</span>
                    </div>
                    <div class="h-info">
                        <span class="h-source">{{ h.nama_spbe || 'TITIPAN GUDANG' }}</span>
                        <span class="h-time">Dikonfirmasi pukul {{ formatTime(h.waktu_konfirmasi) }}</span>
                    </div>
                    <div class="h-qty">
                        <b>+{{ h.jumlah_alokasi }}</b>
                        <span class="status-done">SELESAI</span>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<style scoped>
.pangkalan-container { padding: 25px; background-color: #f0f7ff; min-height: 100vh; font-family: 'Inter', sans-serif; color: #1e3a8a; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
.title-block h1 { font-size: 24px; font-weight: 900; margin: 0; color: #1e3a8a; letter-spacing: -0.5px; }
.title-block p { font-size: 13px; color: #60a5fa; margin-top: 4px; font-weight: 500; }
.status-summary { background: white; padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 8px; border: 1px solid #dcfce7; }
.pulse-dot { width: 8px; height: 8px; border-radius: 50%; background: #10b981; animation: pulse 2s infinite; }

/* SHIPMENT CARD */
.shipment-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; margin-bottom: 25px; overflow: hidden; box-shadow: 0 10px 20px -5px rgba(30, 58, 138, 0.05); }
.card-status-header { padding: 15px 20px; background: #f8fafc; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.status-badge { font-size: 10px; font-weight: 800; padding: 4px 12px; border-radius: 6px; text-transform: uppercase; }
.status-badge.pending { background: #f1f5f9; color: #94a3b8; }
.status-badge.spbe { background: #fff7ed; color: #f97316; }
.status-badge.distribusi { background: #eff6ff; color: #2563eb; }
.date-text { font-size: 12px; font-weight: 600; color: #94a3b8; }

.card-main-content { padding: 25px; display: flex; align-items: center; gap: 25px; }
.qty-circle { width: 75px; height: 75px; border-radius: 50%; border: 4px solid #f0f7ff; display: flex; flex-direction: column; align-items: center; justify-content: center; background: white; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.1); }
.qty-num { font-size: 28px; font-weight: 900; color: #2563eb; line-height: 1; }
.qty-unit { font-size: 9px; font-weight: 800; color: #94a3b8; }

.info-section { flex: 1; }
.info-section label { font-size: 10px; font-weight: 800; color: #94a3b8; letter-spacing: 1px; }
.info-section h4 { font-size: 16px; margin: 4px 0; font-weight: 800; color: #1e3a8a; }
.delivery-note { display: flex; align-items: center; gap: 6px; font-size: 12px; color: #94a3b8; margin-top: 10px; }
.delivery-note.in-transit { color: #10b981; font-weight: 700; }

.card-actions { padding: 15px 20px; background: #fafafa; display: flex; gap: 10px; }
.btn-outline-red { flex: 1; padding: 12px; border: 1.5px solid #fecaca; background: white; color: #ef4444; border-radius: 14px; font-weight: 700; cursor: pointer; font-size: 13px; }
.btn-primary-blue { flex: 2; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 14px; font-weight: 700; cursor: pointer; font-size: 13px; box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2); }
.btn-primary-blue:disabled { background: #cbd5e1; box-shadow: none; cursor: not-allowed; }

/* MODALS */
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.7); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 9999; padding: 20px; }
.modal-box { background: white; width: 100%; max-width: 450px; border-radius: 28px; overflow: hidden; box-shadow: 0 25px 50px -12px rgba(0,0,0,0.5); }
.modal-header { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.btn-close-text { background: none; border: none; font-size: 28px; color: #94a3b8; cursor: pointer; }

.modal-body { padding: 25px; }
.info-banner { background: #eff6ff; color: #1e40af; padding: 12px 18px; border-radius: 14px; font-size: 13px; margin-bottom: 20px; line-height: 1.5; border-left: 5px solid #2563eb; }
.f-group { margin-bottom: 20px; }
.f-group label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 8px; }
.modern-input { width: 100%; padding: 12px 16px; border: 1.5px solid #e2e8f0; border-radius: 12px; font-family: inherit; outline: none; }
.modern-input:focus { border-color: #2563eb; background: #f0f7ff; }

.upload-box { border: 2px dashed #d1e9ff; background: #f8fafc; height: 160px; border-radius: 18px; display: flex; align-items: center; justify-content: center; cursor: pointer; overflow: hidden; }
.upload-trigger { text-align: center; color: #3b82f6; }
.cam-icon { font-size: 32px; margin-bottom: 5px; }
.preview-img { width: 100%; height: 100%; object-fit: cover; }

.btn-final-blue { width: 100%; padding: 16px; background: #2563eb; color: white; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; }
.btn-final-red { width: 100%; padding: 16px; background: #ef4444; color: white; border: none; border-radius: 14px; font-weight: 800; cursor: pointer; }

/* HISTORY ROW CARD */
.history-section { margin-top: 50px; padding-bottom: 30px; }
.section-title { display: flex; align-items: center; gap: 10px; margin-bottom: 20px; color: #1e3a8a; }
.section-title h3 { font-size: 18px; font-weight: 800; margin: 0; }

.history-row-card { background: white; padding: 15px 20px; border-radius: 18px; margin-bottom: 12px; display: flex; align-items: center; gap: 20px; border: 1px solid #e2e8f0; }
.h-date-box { background: #eff6ff; min-width: 50px; padding: 8px; border-radius: 12px; display: flex; flex-direction: column; align-items: center; }
.h-day { font-size: 18px; font-weight: 900; color: #2563eb; line-height: 1; }
.h-month { font-size: 10px; font-weight: 700; color: #60a5fa; text-transform: uppercase; }

.h-info { flex: 1; }
.h-source { display: block; font-weight: 800; font-size: 14px; color: #1e293b; }
.h-time { font-size: 11px; color: #94a3b8; font-weight: 600; }

.h-qty { text-align: right; }
.h-qty b { display: block; font-size: 18px; color: #10b981; }
.status-done { font-size: 9px; font-weight: 800; color: #10b981; text-transform: uppercase; letter-spacing: 0.5px; }

/* ANIMATIONS */
.anim-up { animation: slideUp 0.3s ease-out; }
@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
@keyframes pulse { 0% { transform: scale(1); opacity: 1; } 50% { transform: scale(1.5); opacity: 0.5; } 100% { transform: scale(1); opacity: 1; } }

@media (max-width: 600px) {
    .card-main-content { flex-direction: column; text-align: center; }
    .delivery-note { justify-content: center; }
}
</style>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const loading = ref(true);
const showModal = ref(false);
const submitting = ref(false);
const notifs = ref([]);
const selectedItem = ref(null);
const user = JSON.parse(sessionStorage.getItem('user'));
const history = ref([]);
const showRejectModal = ref(false);
const rejectReason = ref('Tabung Bocor / Rusak');
const rejectNote = ref('');

const openRejectModal = (item) => {
    selectedItem.value = item;
    showRejectModal.value = true;
};

const submitReject = async () => {
    if(!confirm("Anda yakin ingin menolak kiriman ini?")) return;
    const userSession = JSON.parse(sessionStorage.getItem('user'));
    
    try {
        await axios.post('/api/pangkalan-reject', {
            id_distribusi: selectedItem.value.id_distribusi,
            alasan: rejectReason.value + (rejectNote.value ? ': ' + rejectNote.value : ''),
            user_id: userSession.id
        });
        alert("Laporan penolakan terkirim. Tabung dikembalikan ke gudang via Supir.");
        showRejectModal.value = false;
        fetchData();
    } catch (e) {
        alert("Gagal mengirim laporan.");
    }
};

const form = reactive({
    foto: null,
    preview: null,
    catatan: ''
});

const fetchData = async () => {
    loading.value = true;
    try {
        const resNotif = await axios.get(`/api/customer-notif/${user.id}`);
        notifs.value = resNotif.data;

        // AMBIL DATA RIWAYAT
        const resHistory = await axios.get(`/api/customer-history/${user.id}`);
        history.value = resHistory.data;
    } catch (e) {
        console.error("Gagal muat data");
    } finally {
        loading.value = false;
    }
};

const openConfirmModal = (item) => {
    selectedItem.value = item;
    showModal.value = true;
};

const onFileChange = (e) => {
    const file = e.target.files[0];
    if (file) {
        form.foto = file;
        form.preview = URL.createObjectURL(file);
    }
};

const submitKonfirmasi = async () => {
    if (!form.foto) return alert("Wajib lampirkan foto bukti fisik!");
    
    submitting.value = true;
    try {
        const userSession = JSON.parse(sessionStorage.getItem('user'));
        
        // Gunakan FormData karena kita mengirim file (image)
        const formData = new FormData();
        formData.append('id_distribusi', selectedItem.value.id_distribusi); // Pastikan ini id_distribusi dari tabel distribusi_pangkalan
        formData.append('foto', form.foto);
        formData.append('catatan', form.catatan);
        formData.append('user_id', userSession.id);

        const res = await axios.post('/api/pangkalan-konfirmasi', formData, {
            headers: { 'Content-Type': 'multipart/form-data' }
        });

        if (res.data.success) {
            alert("Terima kasih! Stok Pangkalan Anda telah diperbarui.");
            showModal.value = false;
            fetchData(); // Refresh list agar kartu hilang karena sudah diterima
        }
    } catch (e) {
        alert(e.response?.data?.message || "Gagal melakukan konfirmasi.");
    } finally {
        submitting.value = false;
    }
};

const formatTime = (t) => new Date(t).toLocaleTimeString('id-ID', {hour: '2-digit', minute:'2-digit'});

const getDisplayStatus = (item) => {
    // 1. Jika belum ada id_distribusi, berarti masih proses internal gudang
    if (!item.id_distribusi) {
        if (item.status_global === 'Pending') return { label: 'DIJADWALKAN', class: 'pending' };
        if (item.status_global === 'SPBE') return { label: 'PROSES SPBE', class: 'spbe' };
        return { label: 'MENUNGGU TRUK', class: 'waiting' }; // Jika batch sudah mulai jalan tapi pangkalan ini belum
    }
    
    // 2. Jika sudah ada id_distribusi, berarti status beneran pengiriman
    return { label: 'DALAM PENGIRIMAN', class: 'distribusi' };
};

onMounted(fetchData);
</script>
