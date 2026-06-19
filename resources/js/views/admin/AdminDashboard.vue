<template>
    <div class="dashboard-wrapper">
        <!-- HEADER: Fokus pada Aset Internal Perusahaan -->
        <header class="header-section">
            <div class="title-meta">
                <span class="breadcrumb">Internal Asset Management</span>
                <h1>Inventory Mapping Center</h1>
            </div>
            
            <!-- AUDIT WIDGET: Sekarang HANYA Gudang + Armada -->
            <div class="system-status" :class="{ 'warning-border': totalInternalAset !== 5906 }">
                <div class="status-indicator">
                    <span class="pulse-dot" :class="{ 'red': totalInternalAset !== 5906 }"></span>
                    <span class="status-text">{{ totalInternalAset === 5906 ? 'Internal Aset: Sinkron' : 'Internal Aset: Selisih' }}</span>
                </div>
                <div class="main-count">
                    <span class="current">{{ totalInternalAset.toLocaleString() }}</span>
                    <span class="target">/ 5,906</span>
                </div>
            </div>
        </header>

        <!-- QUICK METRICS -->
        <div class="stats-grid">
            <div class="stat-card">
                <div class="stat-icon blue"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg></div>
                <div class="stat-content">
                    <p>Stok di Gudang (L1-L3)</p>
                    <h3>{{ totalGudang.toLocaleString() }}</h3>
                </div>
            </div>
            <div class="stat-card">
                <div class="stat-icon orange"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div>
                <div class="stat-content">
                    <p>Stok di Armada (Logistik)</p>
                    <h3>{{ totalArmada.toLocaleString() }}</h3>
                </div>
            </div>
            <!-- Pangkalan ditaruh di sini sebagai INFO saja -->
            <div class="stat-card pangkalan-info">
                <div class="stat-icon green"><svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"></path><polyline points="9 22 9 12 15 12 15 22"></polyline></svg></div>
                <div class="stat-content">
                    <p>Tersebar di Pangkalan</p>
                    <h3 class="text-green">{{ map.pangkalan.toLocaleString() }}</h3>
                </div>
            </div>
        </div>

        <div class="main-grid">
            <!-- WAREHOUSE MAPPING -->
            <section class="visual-card">
                <div class="v-header">
                    <h3>🏢 Warehouse Floor Mapping</h3>
                    <div class="v-legend">
                        <span class="l-isi">Isi</span>
                        <span class="l-kosong">Kosong</span>
                    </div>
                </div>
                <div class="floor-stack">
                    <div v-for="i in [3,2,1]" :key="i" class="floor-row">
                        <div class="floor-label">
                            <span class="num">0{{ i }}</span>
                            <span class="tag">{{ i===1?'LOADING DOCK':i===2?'MAIN STORAGE':'BUFFER' }}</span>
                        </div>
                        <div class="floor-gauge-wrapper">
                            <div class="floor-gauge">
                                <div class="fill-isi" :style="{ width: (map.jadug_hub['lantai_'+i].isi / 5906 * 200) + '%' }"></div>
                                <div class="fill-kosong" :style="{ width: (map.jadug_hub['lantai_'+i].kosong / 5906 * 200) + '%' }"></div>
                            </div>
                            <div class="gauge-values">
                                <span>ISI: <b>{{ map.jadug_hub['lantai_'+i].isi }}</b></span>
                                <span>KOSONG: <b>{{ map.jadug_hub['lantai_'+i].kosong }}</b></span>
                            </div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- ANALYTICS -->
            <section class="visual-card">
                <div class="v-header">
                    <h3>📊 Internal Asset Composition</h3>
                </div>
                <div class="chart-content">
                    <div class="doughnut-container">
                        <canvas id="compositionChart"></canvas>
                        <div class="center-label">
                            <span class="sub">ASSET</span>
                            <span class="main">5.9K</span>
                        </div>
                    </div>
                    <div class="bar-container">
                        <p class="chart-desc">Rasio perbandingan ketersediaan stok fisik di area internal perusahaan (Gudang & Armada).</p>
                    </div>
                </div>
            </section>
        </div>

        <div class="bottom-insight-grid mt-24">
            
            <!-- FITUR 1: PANGKALAN KRITIS (Low Stock) -->
            <section class="visual-card alert-section">
                <div class="v-header">
                    <h3>⚠️ Prioritas Alokasi (Stok Rendah)</h3>
                    <span class="count-badge">{{ lowStockPangkalans.length }} Pangkalan</span>
                </div>
                <div class="alert-list">
                    <div v-for="p in lowStockPangkalans" :key="p.id_pangkalan" class="alert-item">
                        <div class="p-info">
                            <span class="p-name">{{ p.nama_pangkalan }}</span>
                            <span class="p-addr">{{ p.alamat.substring(0, 30) }}...</span>
                        </div>
                        <div class="p-stock danger">
                            {{ p.stok_saat_ini }} <small>Tabung</small>
                        </div>
                    </div>
                    <div v-if="lowStockPangkalans.length === 0" class="no-alert">
                        ✅ Semua stok pangkalan mencukupi.
                    </div>
                </div>
            </section>

            <!-- FITUR 2: LIVE ACTIVITY FEED -->
            <section class="visual-card feed-section">
                <div v-header>
                    <h3>🕒 Aktivitas Terkini</h3>
                </div>
                <div class="feed-list">
                    <div v-for="log in recentLogs" :key="log.id_log" class="feed-item">
                        <div class="feed-dot" :class="log.role"></div>
                        <div class="feed-content">
                            <p><b>{{ log.name }}</b> {{ log.aktivitas }}</p>
                            <span>{{ formatTime(log.waktu_log) }}</span>
                        </div>
                    </div>
                </div>
            </section>

        </div>

        <!-- FLEET TRACKING -->
        <section class="visual-card mt-24">
            <div class="v-header">
                <h3>🚚 Active Fleet Monitoring</h3>
            </div>
            <div class="fleet-list">
                <div v-for="t in map.armada" :key="t.plat_no" class="fleet-row">
                    <div class="f-info">
                        <span class="f-plat">{{ t.plat_no }}</span>
                        <span class="f-type">{{ t.kategori_kendaraan === 'mobil_colt' ? 'Colt Diesel' : 'Truck' }}</span>
                    </div>
                    <div class="f-progress">
                        <div class="p-bar"><div class="p-fill" :style="{ width: (t.muatan / 560 * 100) + '%' }"></div></div>
                        <span class="p-val">{{ t.muatan }} Tabung</span>
                    </div>
                    <div class="f-status">
                        <span class="status-pill active">In Transit</span>
                    </div>
                </div>
                <div v-if="map.armada.length === 0" class="no-data">Seluruh armada sedang standby di gudang.</div>
            </div>
        </section>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';
import Chart from 'chart.js/auto';

const map = ref({
    jadug_hub: {
        lantai_1: { isi: 0, kosong: 0 },
        lantai_2: { isi: 0, kosong: 0 },
        lantai_3: { isi: 0, kosong: 0 }
    },
    armada: [],
    pangkalan: 0
});
const lowStockPangkalans = ref([]);
const recentLogs = ref([]);

// HITUNG STOK GUDANG (INTERNAL)
const totalGudang = computed(() => {
    let sum = 0;
    for (let i = 1; i <= 3; i++) {
        sum += map.value.jadug_hub['lantai_'+i].isi + map.value.jadug_hub['lantai_'+i].kosong;
    }
    return sum;
});

// HITUNG STOK ARMADA (INTERNAL)
const totalArmada = computed(() => {
    let sum = 0;
    map.value.armada.forEach(t => sum += parseInt(t.muatan));
    return sum;
});

// TOTAL INTERNAL ASSET = GUDANG + ARMADA (Tanpa Pangkalan)
const totalInternalAset = computed(() => totalGudang.value + totalArmada.value);

let compChart;

const initCharts = () => {
    compChart = new Chart(document.getElementById('compositionChart'), {
        type: 'doughnut',
        data: {
            labels: ['Gudang', 'Armada'],
            datasets: [{
                data: [0, 0],
                backgroundColor: ['#2563eb', '#f59e0b'],
                borderWidth: 0,
                cutout: '85%'
            }]
        },
        options: { plugins: { legend: { display: false } } }
    });
};

const updateCharts = () => {
    if (!compChart) return;
    compChart.data.datasets[0].data = [totalGudang.value, totalArmada.value];
    compChart.update();
};

const fetchData = async () => {
    try {
        const res = await axios.get('/api/inventory-mapping');
        map.value = res.data;
        
        // AMBIL DATA TAMBAHAN
        const resExtra = await axios.get('/api/admin-dashboard-extra');
        lowStockPangkalans.value = resExtra.data.low_stock;
        recentLogs.value = resExtra.data.logs;

        updateCharts();
    } catch (e) { console.error(e); }
};

const formatTime = (t) => {
    return new Date(t).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

onMounted(() => {
    initCharts();
    fetchData();
    setInterval(fetchData, 30000);
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css2?family=Inter:wght@400;600;700;900&display=swap');

.dashboard-wrapper { padding: 32px; background-color: #f8fafc; min-height: 100vh; font-family: 'Inter', sans-serif; }
.text-green { color: #10b981 !important; }
.pangkalan-info { border-left: 4px solid #10b981; }
.chart-desc { font-size: 13px; color: #64748b; line-height: 1.6; margin-top: 20px; }

/* HEADER */
.header-section {
    display: flex;
    justify-content: space-between;
    align-items: center;
    margin-bottom: 40px;
}
.breadcrumb { font-size: 11px; font-weight: 700; color: #2563eb; text-transform: uppercase; letter-spacing: 1px; }
.header-section h1 { font-size: 32px; font-weight: 900; margin: 4px 0 0 0; letter-spacing: -1px; }

.system-status {
    background: white;
    padding: 12px 24px;
    border-radius: 16px;
    border: 1px solid #e2e8f0;
    text-align: right;
}
.warning-border { border-color: #f43f5e; background: #fff1f2; }
.status-indicator { display: flex; align-items: center; justify-content: flex-end; gap: 8px; font-size: 11px; font-weight: 700; margin-bottom: 4px; }
.pulse-dot { width: 8px; height: 8px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite; }
.pulse-dot.red { background: #f43f5e; }
.main-count { font-size: 24px; font-weight: 900; }
.main-count .target { color: #94a3b8; font-size: 16px; margin-left: 4px; }

/* STATS CARDS */
.stats-grid { display: grid; grid-template-columns: repeat(3, 1fr); gap: 24px; margin-bottom: 32px; }
.stat-card {
    background: white;
    padding: 24px;
    border-radius: 20px;
    border: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
    gap: 20px;
}
.stat-icon { width: 48px; height: 48px; border-radius: 14px; display: flex; align-items: center; justify-content: center; }
.stat-icon.blue { background: #eff6ff; color: #2563eb; }
.stat-icon.green { background: #f0fdf4; color: #10b981; }
.stat-icon.orange { background: #fff7ed; color: #f59e0b; }
.stat-content p { font-size: 12px; font-weight: 600; color: #64748b; margin: 0; }
.stat-content h3 { font-size: 24px; font-weight: 800; margin: 4px 0 0 0; }

/* MAIN GRID */
.main-grid { display: grid; grid-template-columns: 1.2fr 1fr; gap: 24px; }
.visual-card { background: white; border: 1px solid #e2e8f0; border-radius: 24px; padding: 32px; }
.v-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 32px; }
.v-header h3 { font-size: 16px; font-weight: 800; margin: 0; color: #334155; }

/* WAREHOUSE STACK */
.floor-stack { display: flex; flex-direction: column; gap: 24px; }
.floor-row { display: grid; grid-template-columns: 140px 1fr; gap: 24px; align-items: center; }
.floor-label .num { font-size: 28px; font-weight: 900; color: #e2e8f0; display: block; line-height: 1; }
.floor-label .tag { font-size: 10px; font-weight: 800; color: #64748b; }

.floor-gauge { height: 40px; background: #f1f5f9; border-radius: 12px; overflow: hidden; display: flex; margin-bottom: 8px; }
.fill-isi { background: #2563eb; transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
.fill-kosong { background: #cbd5e1; transition: 0.8s cubic-bezier(0.4, 0, 0.2, 1); }
.gauge-values { display: flex; gap: 16px; font-size: 11px; font-weight: 700; color: #64748b; }
.gauge-values b { color: #1e3a8a; }

/* CHART STYLING */
.chart-content { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; align-items: center; }
.doughnut-container { position: relative; width: 160px; height: 160px; margin: 0 auto; }
.center-label { position: absolute; top: 50%; left: 50%; transform: translate(-50%, -50%); text-align: center; }
.center-label .sub { font-size: 10px; font-weight: 700; color: #94a3b8; display: block; }
.center-label .main { font-size: 20px; font-weight: 900; }

/* FLEET ROW STYLE */
.fleet-list { display: flex; flex-direction: column; gap: 12px; }
.fleet-row {
    display: grid; grid-template-columns: 180px 1fr 150px; align-items: center;
    padding: 16px 24px; background: #f8fafc; border-radius: 16px; transition: 0.2s;
}
.fleet-row:hover { background: #f1f5f9; }
.f-plat { display: block; font-weight: 800; font-family: monospace; font-size: 15px; }
.f-type { font-size: 11px; font-weight: 600; color: #94a3b8; text-transform: uppercase; }
.p-bar { height: 6px; background: #e2e8f0; border-radius: 10px; overflow: hidden; width: 150px; margin-bottom: 4px; }
.p-fill { height: 100%; background: #2563eb; }
.p-val { font-size: 12px; font-weight: 700; color: #1e3a8a; }
.status-pill { padding: 4px 12px; border-radius: 50px; font-size: 10px; font-weight: 700; background: #dbeafe; color: #1e40af; }

.mt-24 { margin-top: 24px; }
.v-legend { display: flex; gap: 16px; font-size: 11px; font-weight: 600; }
.l-isi::before { content: ''; display: inline-block; width: 8px; height: 8px; background: #2563eb; border-radius: 50%; margin-right: 6px; }
.l-kosong::before { content: ''; display: inline-block; width: 8px; height: 8px; background: #cbd5e1; border-radius: 50%; margin-right: 6px; }

.bottom-insight-grid {
    display: grid;
    grid-template-columns: 1fr 1fr;
    gap: 24px;
}

/* Alert List */
.alert-list { display: flex; flex-direction: column; gap: 12px; }
.alert-item {
    display: flex; justify-content: space-between; align-items: center;
    padding: 12px 16px; background: #fff1f2; border-radius: 12px; border: 1px solid #fecdd3;
}
.p-name { display: block; font-weight: 700; font-size: 14px; color: #991b1b; }
.p-addr { font-size: 11px; color: #f43f5e; }
.p-stock.danger { font-weight: 900; color: #e11d48; font-size: 18px; }
.p-stock small { font-size: 10px; font-weight: 600; }

/* Activity Feed */
.feed-list { display: flex; flex-direction: column; gap: 20px; position: relative; }
.feed-item { display: flex; gap: 15px; position: relative; }
.feed-dot { width: 10px; height: 10px; border-radius: 50%; background: #cbd5e1; margin-top: 5px; z-index: 2; }
.feed-dot.admin { background: #2563eb; box-shadow: 0 0 8px #2563eb; }
.feed-dot.operator { background: #f59e0b; box-shadow: 0 0 8px #f59e0b; }
.feed-dot.customer { background: #10b981; box-shadow: 0 0 8px #10b981; }

.feed-content p { margin: 0; font-size: 13px; line-height: 1.4; color: #334155; }
.feed-content span { font-size: 11px; color: #94a3b8; font-weight: 600; }

.count-badge { background: #f1f5f9; padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; color: #64748b; }

@keyframes pulse { 0% { opacity: 1; } 50% { opacity: 0.4; } 100% { opacity: 1; } }
</style>