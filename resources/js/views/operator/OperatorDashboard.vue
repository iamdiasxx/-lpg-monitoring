<template>
    <div class="op-dash-container">
        <!-- HEADER: Greeting & Shift Info -->
        <header class="op-header">
            <div class="user-welcome">
                <span class="badge-shift">SHIFT AKTIF</span>
                <h1>Halo, {{ user.name }}</h1>
                <p>Gudang Pusat Jadug • {{ today }}</p>
            </div>
            <div class="quick-audit-card">
                <div class="audit-item">
                    <label>TOTAL ISI GUDANG</label>
                    <span class="val blue">{{ totalIsiGudang }}</span>
                </div>
                <div class="v-line"></div>
                <div class="audit-item">
                    <label>TOTAL KOSONG GUDANG</label>
                    <span class="val gray">{{ totalKosongGudang }}</span>
                </div>
            </div>
        </header>

        <!-- GRID UTAMA -->
        <div class="op-grid">
            
            <!-- SECTION 1: STATUS LANTAI GUDANG -->
            <section class="op-card">
                <div class="card-head">
                    <h3>🏢 Status Rak & Lantai</h3>
                    <router-link to="/operator/stok-gudang" class="link-text">Kelola Stok →</router-link>
                </div>
                <div class="floor-list">
                    <div v-for="i in [3, 2, 1]" :key="i" class="floor-item">
                        <div class="f-name">LANTAI {{ i }}</div>
                        <div class="f-stats">
                            <div class="s-pill isi">I: {{ warehouse['lantai_'+i+'_isi'] }}</div>
                            <div class="s-pill kosong">K: {{ warehouse['lantai_'+i+'_kosong'] }}</div>
                        </div>
                    </div>
                </div>
            </section>

            <!-- SECTION 2: TUGAS HARI INI (INSTRUKSI MANAGER) -->
            <section class="op-card">
                <div class="card-head">
                    <h3>📋 Antrean Tugas</h3>
                </div>
                <div class="task-summary">
                    <router-link to="/operator/transaksi-spbe" class="task-tile blue">
                        <div class="t-icon">⛽</div>
                        <div class="t-info">
                            <h4>Penukaran SPBE</h4>
                            <p>{{ pendingSpbe }} Tugas Menunggu</p>
                        </div>
                    </router-link>
                    
                    <router-link to="/operator/distribusi" class="task-tile green">
                        <div class="t-icon">🚛</div>
                        <div class="t-info">
                            <h4>Distribusi Pangkalan</h4>
                            <p>{{ pendingDispatch }} Pengiriman Siap</p>
                        </div>
                    </router-link>
                </div>
            </section>

            <!-- SECTION 3: ARMADA STANDBY -->
            <section class="op-card fleet-status">
                <div class="card-head">
                    <h3>🚚 Armada di Gudang</h3>
                    <span class="count-tag">{{ standbyTrucks.length }} Standby</span>
                </div>
                <div class="fleet-mini-grid">
                    <div v-for="t in standbyTrucks" :key="t.id_truk" class="mini-truck">
                        <b>{{ t.plat_no }}</b>
                        <small>{{ t.stok_isi > 0 ? 'Membawa Isi' : 'Kosong' }}</small>
                    </div>
                    <div v-if="standbyTrucks.length === 0" class="no-data">Semua armada sedang bertugas.</div>
                </div>
            </section>

            <!-- SECTION 4: ACTIVITY LOG SINGKAT -->
            <section class="op-card">
                <div class="card-head">
                    <h3>🕒 Aktivitas Terakhir Anda</h3>
                </div>
                <div class="log-mini">
                    <div v-for="log in myLogs" :key="log.id_log" class="log-item">
                        <span class="log-time">{{ formatTime(log.waktu_log) }}</span>
                        <p class="log-text">{{ log.aktivitas }}</p>
                    </div>
                </div>
            </section>

        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const user = JSON.parse(sessionStorage.getItem('user'));
const warehouse = ref({});
const trucks = ref([]);
const tasks = ref([]);
const myLogs = ref([]);
const today = new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long' });

const totalIsiGudang = computed(() => (warehouse.value.lantai_1_isi || 0) + (warehouse.value.lantai_2_isi || 0) + (warehouse.value.lantai_3_isi || 0));
const totalKosongGudang = computed(() => (warehouse.value.lantai_1_kosong || 0) + (warehouse.value.lantai_2_kosong || 0) + (warehouse.value.lantai_3_kosong || 0));

const pendingSpbe = computed(() => tasks.value.filter(t => t.status === 'Pending').length);
const pendingDispatch = ref(0); // Nanti dihitung dari API dispatch-pending

const standbyTrucks = computed(() => trucks.value.filter(t => t.status_jalan !== 'DI JALAN'));

const formatTime = (t) => new Date(t).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

const fetchData = async () => {
    try {
        const resFleet = await axios.get('/api/fleet-resources');
        warehouse.value = resFleet.data.stok_saat_ini;
        trucks.value = resFleet.data.truk;

        const resTask = await axios.get('/api/operator-tasks');
        tasks.value = resTask.data;

        const resDispatch = await axios.get('/api/dispatch-pending');
        pendingDispatch.value = resDispatch.data.pangkalans.length;

        const resLogs = await axios.get('/api/audit-logs');
        myLogs.value = resLogs.data.filter(l => l.id_user === user.id).slice(0, 4);
    } catch (e) { console.error(e); }
};

onMounted(fetchData);
</script>

<style scoped>
.op-dash-container { padding: 30px; background: #f8fafc; min-height: 100vh; font-family: 'Inter', sans-serif; color: #1e293b; }

/* HEADER */
.op-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 35px; }
.badge-shift { background: #dcfce7; color: #15803d; font-size: 10px; font-weight: 800; padding: 4px 10px; border-radius: 50px; }
.op-header h1 { font-size: 28px; font-weight: 900; color: #0f172a; margin: 5px 0; }
.op-header p { font-size: 14px; color: #64748b; margin: 0; }

.quick-audit-card { background: white; border: 1px solid #e2e8f0; padding: 15px 25px; border-radius: 20px; display: flex; align-items: center; gap: 25px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.audit-item label { font-size: 9px; font-weight: 800; color: #94a3b8; display: block; margin-bottom: 5px; }
.audit-item .val { font-size: 22px; font-weight: 900; }
.val.blue { color: #2563eb; }
.val.gray { color: #475569; }
.v-line { width: 1px; height: 40px; background: #f1f5f9; }

/* GRID */
.op-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 25px; }
.op-card { background: white; border: 1px solid #e2e8f0; border-radius: 20px; padding: 25px; }
.card-head { display: flex; justify-content: space-between; align-items: center; margin-bottom: 20px; }
.card-head h3 { font-size: 15px; font-weight: 800; color: #334155; margin: 0; }
.link-text { font-size: 12px; font-weight: 700; color: #2563eb; text-decoration: none; }

/* FLOORS */
.floor-item { display: flex; justify-content: space-between; align-items: center; padding: 12px 0; border-bottom: 1px solid #f8fafc; }
.f-name { font-size: 13px; font-weight: 700; color: #64748b; }
.f-stats { display: flex; gap: 10px; }
.s-pill { padding: 4px 12px; border-radius: 8px; font-size: 12px; font-weight: 800; }
.s-pill.isi { background: #eff6ff; color: #2563eb; }
.s-pill.kosong { background: #f1f5f9; color: #475569; }

/* TASKS */
.task-summary { display: flex; flex-direction: column; gap: 15px; }
.task-tile { display: flex; align-items: center; gap: 15px; padding: 15px; border-radius: 15px; text-decoration: none; transition: 0.2s; border: 1px solid transparent; }
.task-tile:hover { transform: translateX(5px); }
.task-tile.blue { background: #eff6ff; color: #1e40af; }
.task-tile.green { background: #f0fdf4; color: #166534; }
.t-icon { width: 45px; height: 45px; background: white; border-radius: 12px; display: flex; align-items: center; justify-content: center; font-size: 20px; }
.t-info h4 { margin: 0; font-size: 14px; font-weight: 800; }
.t-info p { margin: 2px 0 0; font-size: 11px; font-weight: 600; opacity: 0.8; }

/* FLEET */
.fleet-mini-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; }
.mini-truck { background: #f8fafc; padding: 10px 15px; border-radius: 12px; border: 1px solid #e2e8f0; }
.mini-truck b { display: block; font-size: 13px; font-family: monospace; color: #1e3a8a; }
.mini-truck small { font-size: 10px; color: #94a3b8; font-weight: 600; }
.count-tag { font-size: 10px; font-weight: 800; color: #64748b; background: #f1f5f9; padding: 2px 8px; border-radius: 4px; }

/* LOGS */
.log-item { display: flex; gap: 15px; margin-bottom: 12px; }
.log-time { font-size: 11px; font-weight: 700; color: #94a3b8; white-space: nowrap; }
.log-text { font-size: 12px; color: #475569; margin: 0; line-height: 1.4; }

.no-data { grid-column: span 2; text-align: center; padding: 20px; color: #94a3b8; font-size: 12px; font-style: italic; }

@media (max-width: 1024px) { .op-grid { grid-template-columns: 1fr; } }
</style>