<template>
    <div class="monitor-pangkalan-container">
        <!-- HEADER SECTION -->
        <header class="page-header">
            <div class="title-block">
                <h1>Buku Kendali Stok</h1>
                <p>Pantau sirkulasi aset secara transparan dan real-time.</p>
            </div>
            <div class="refresh-status">
                <div class="dot active"></div>
                <span>Data Terupdate</span>
            </div>
        </header>

        <!-- SUMMARY CARDS: DUAL BALANCING -->
        <div class="summary-grid">
            <div class="s-card gradient-blue">
                <div class="card-content">
                    <label>Persediaan di Toko</label>
                    <div class="val-group">
                        <h3>{{ stockInfo.stok_saat_ini }}</h3>
                        <span>Unit</span>
                    </div>
                </div>
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="m7.5 4.27 9 5.15"></path><path d="M21 8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16Z"></path><path d="m3.3 7 8.7 5 8.7-5"></path><path d="M12 22V12"></path></svg>
                </div>
            </div>

            <div class="s-card gradient-amber">
                <div class="card-content">
                    <label>Titipan di Gudang</label>
                    <div class="val-group">
                        <h3>{{ stockInfo.stok_titipan }}</h3>
                        <span>Unit</span>
                    </div>
                </div>
                <div class="card-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="32" height="32" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect width="18" height="18" x="3" y="3" rx="2"></rect><path d="M3 9h18"></path><path d="M9 21V9"></path></svg>
                </div>
            </div>
        </div>

        <!-- MOVEMENT HISTORY (TIMELINE STYLE) -->
        <div class="history-section">
            <h3 class="section-title">Log Aktivitas Stok</h3>
            
            <div v-if="history.length === 0" class="empty-state">
                <div class="empty-icon">📂</div>
                <p>Belum ada rekaman sirkulasi stok.</p>
            </div>

            <div v-else class="timeline-list">
                <div v-for="(h, index) in history" :key="index" class="timeline-item">
                    <!-- Penanda Tipe -->
                    <div class="type-indicator" :class="h.tipe.toLowerCase()">
                        <svg v-if="h.tipe === 'MASUK'" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="7 10 12 15 17 10"></polyline><line x1="12" y1="15" x2="12" y2="3"></line></svg>
                        <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 14 12 9 7 14"></polyline><line x1="12" y1="9" x2="12" y2="21"></line></svg>
                    </div>

                    <!-- Isi Informasi -->
                    <div class="item-content">
                        <div class="item-main">
                            <h4>{{ h.tipe === 'MASUK' ? 'Pengiriman Diterima' : 'Laporan Penjualan' }}</h4>
                            <span class="item-date">{{ formatDateTime(h.tanggal) }}</span>
                        </div>
                        <div class="item-qty" :class="h.tipe === 'MASUK' ? 'text-green' : 'text-red'">
                            {{ h.tipe === 'MASUK' ? '+' : '-' }}{{ h.jumlah }}
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import axios from 'axios';

const stockInfo = ref({});
const history = ref([]);
const user = JSON.parse(sessionStorage.getItem('user'));

const fetchData = async () => {
    try {
        const res = await axios.get(`/api/pangkalan/stock-movement/${user.id}`);
        stockInfo.value = res.data.info;
        history.value = res.data.history;
    } catch (e) {
        console.error("Gagal memuat data monitoring");
    }
};

const formatDateTime = (t) => {
    const d = new Date(t);
    return d.toLocaleDateString('id-ID', { day: '2-digit', month: 'short' }) + ' • ' + 
           d.toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

onMounted(fetchData);
</script>

<style scoped>
.monitor-pangkalan-container { padding: 25px; background-color: #f0f7ff; min-height: 100vh; font-family: 'Inter', sans-serif; color: #1e3a8a; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
.title-block h1 { font-size: 24px; font-weight: 900; margin: 0; color: #1e3a8a; letter-spacing: -0.5px; }
.title-block p { font-size: 13px; color: #60a5fa; margin-top: 4px; font-weight: 500; }
.refresh-status { background: white; padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 8px; border: 1px solid #dcfce7; }
.refresh-status .dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; }

/* SUMMARY CARDS */
.summary-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 35px; }
.s-card { 
    padding: 20px; border-radius: 24px; color: white; display: flex; 
    justify-content: space-between; align-items: center; position: relative; 
    overflow: hidden; box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.2);
}
.gradient-blue { background: linear-gradient(135deg, #2563eb, #3b82f6); }
.gradient-amber { background: linear-gradient(135deg, #f59e0b, #fbbf24); }

.s-card label { font-size: 10px; font-weight: 800; opacity: 0.8; text-transform: uppercase; letter-spacing: 0.5px; }
.val-group { display: flex; align-items: baseline; gap: 5px; margin-top: 5px; }
.val-group h3 { font-size: 32px; font-weight: 900; margin: 0; line-height: 1; }
.val-group span { font-size: 12px; font-weight: 600; opacity: 0.8; }
.card-icon { opacity: 0.25; }

/* TIMELINE LIST */
.section-title { font-size: 15px; font-weight: 800; color: #1e3a8a; margin-bottom: 15px; text-transform: uppercase; }
.timeline-list { display: flex; flex-direction: column; gap: 12px; }
.timeline-item { 
    background: white; padding: 16px; border-radius: 20px; 
    display: flex; align-items: center; gap: 15px; 
    border: 1.5px solid #eef2ff; transition: 0.3s;
}
.timeline-item:hover { border-color: #d1e9ff; transform: translateX(5px); }

.type-indicator { width: 42px; height: 42px; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.type-indicator.masuk { background: #f0fdf4; color: #10b981; }
.type-indicator.keluar { background: #fff1f2; color: #ef4444; }

.item-content { flex: 1; display: flex; justify-content: space-between; align-items: center; }
.item-main h4 { margin: 0; font-size: 14px; font-weight: 800; color: #1e293b; }
.item-date { font-size: 11px; color: #94a3b8; font-weight: 600; }

.item-qty { font-size: 18px; font-weight: 900; }
.text-green { color: #10b981; }
.text-red { color: #ef4444; }

.empty-state { text-align: center; padding: 50px 0; color: #94a3b8; }
.empty-icon { font-size: 40px; margin-bottom: 10px; }

@media (max-width: 600px) {
    .summary-grid { grid-template-columns: 1fr; }
    .item-qty { font-size: 16px; }
}
</style>