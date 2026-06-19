<template>
    <div class="sirkulasi-container">
        <!-- HEADER SECTION -->
        <div class="page-header">
            <div class="title-area">
                <h1>Pusat Manajemen Sirkulasi Aset</h1>
                <p>Monitoring posisi 5.906 tabung dan eksekusi bongkar muat (transfer).</p>
            </div>
            <div class="summary-pills">
                <div class="pill">
                    <span class="p-label">Total Isi</span>
                    <span class="p-val text-blue">{{ totalIsi }}</span>
                </div>
                <div class="pill">
                    <span class="p-label">Total Kosong</span>
                    <span class="p-val text-gray">{{ totalKosong }}</span>
                </div>
            </div>
        </div>

        <div class="main-grid">
            <!-- SISI KIRI: VISUALISASI LOKASI -->
            <div class="visual-section">
                <!-- GEDUNG JADUG -->
                <div class="location-group">
                    <div class="group-header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 9l9-7 9 7v11a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2z"/><polyline points="9 22 9 12 15 12 15 22"/></svg>
                        <h3>Gudang Pusat (Jadug)</h3>
                    </div>
                    <div class="floor-grid">
                        <div v-for="i in [3, 2, 1]" :key="i" class="floor-card" :class="'f-'+i">
                            <div class="floor-info">
                                <span class="floor-name">Lantai {{ i }}</span>
                                <span class="floor-desc">{{ i === 1 ? 'Loading Area' : i === 2 ? 'Main Storage' : 'Buffer' }}</span>
                            </div>
                            <div class="floor-stats">
                                <div class="stat isi">
                                    <label>ISI</label>
                                    <span>{{ warehouse['lantai_'+i+'_isi'] || 0 }}</span>
                                </div>
                                <div class="stat kosong">
                                    <label>KOSONG</label>
                                    <span>{{ warehouse['lantai_'+i+'_kosong'] || 0 }}</span>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- ARMADA KENDARAAN -->
                <div class="location-group mt-30">
                    <div class="group-header">
                        <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                        <h3>Armada Logistik Aktif</h3>
                    </div>
                    <div class="truck-grid">
                        <div v-for="t in trucks" :key="t.id_truk" class="truck-card">
                            <div class="truck-plat">{{ t.plat_no }}</div>
                            <div class="truck-bars">
                                <div class="bar-group">
                                    <div class="bar-label">ISI: {{ t.stok_isi }}</div>
                                    <div class="bar-bg"><div class="bar-fill blue" :style="{width: (t.stok_isi/560*100)+'%'}"></div></div>
                                </div>
                                <div class="bar-group">
                                    <div class="bar-label">KOSONG: {{ t.stok_kosong }}</div>
                                    <div class="bar-bg"><div class="bar-fill gray" :style="{width: (t.stok_kosong/560*100)+'%'}"></div></div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- SISI KANAN: FORM TRANSFER -->
            <div class="action-section">
                <div class="card sticky-form">
                    <div class="card-header-icon">
                        <div class="icon-circle"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="17 1 21 5 17 9"/><path d="M3 11V9a4 4 0 0 1 4-4h14"/><polyline points="7 23 3 19 7 15"/><path d="M21 13v2a4 4 0 0 1-4 4H3"/></svg></div>
                        <h3>Eksekusi Pemindahan</h3>
                    </div>
                    
                    <div class="transfer-form">
                        <div class="form-row">
                            <div class="input-block">
                                <label>SUMBER ASAL</label>
                                <select v-model="form.from" class="modern-select">
                                    <option value="" disabled>Pilih Asal...</option>
                                    <optgroup label="Gudang">
                                        <option value="floor-1">Lantai 1</option>
                                        <option value="floor-2">Lantai 2</option>
                                        <option value="floor-3">Lantai 3</option>
                                    </optgroup>
                                    <optgroup label="Armada">
                                        <option v-for="t in trucks" 
                                                :key="t.id_truk" 
                                                :value="'truck-'+t.id_truk"
                                                :disabled="t.status_jalan === 'DI JALAN'">
                                            {{ t.plat_no }} {{ t.status_jalan === 'DI JALAN' ? '(SEDANG KIRIM)' : '' }}
                                        </option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div class="transfer-divider">
                            <div class="line"></div>
                            <div class="down-arrow">▼</div>
                        </div>

                        <div class="form-row">
                            <div class="input-block">
                                <label>TUJUAN PEMINDAHAN</label>
                                <select v-model="form.to" class="modern-select">
                                    <option value="" disabled>Pilih Tujuan...</option>
                                    <optgroup label="Gudang">
                                        <option value="floor-1">Lantai 1</option>
                                        <option value="floor-2">Lantai 2</option>
                                        <option value="floor-3">Lantai 3</option>
                                    </optgroup>
                                    <optgroup label="Armada">
                                        <option v-for="t in trucks" :value="'truck-'+t.id_truk">{{ t.plat_no }}</option>
                                    </optgroup>
                                </select>
                            </div>
                        </div>

                        <div class="form-row flex-gap">
                            <div class="input-block flex-2">
                                <label>JUMLAH TABUNG</label>
                                <input type="number" v-model="form.qty" class="modern-input" placeholder="0">
                            </div>
                            <div class="input-block flex-1">
                                <label>JENIS</label>
                                <select v-model="form.type" class="modern-select">
                                    <option value="isi">ISI</option>
                                    <option value="kosong">KOSONG</option>
                                </select>
                            </div>
                        </div>

                        <button @click="executeTransfer" :disabled="loading" class="btn-execute-main">
                            {{ loading ? 'Memproses...' : 'KONFIRMASI PEMINDAHAN' }}
                        </button>
                    </div>
                </div>
            </div>
        </div>

        <!-- TABEL HISTORI PERGERAKAN (TAMBAHAN BARU) -->
        <div class="card history-section mt-30">
            <div class="group-header">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M12 8v4l3 3m6-3a9 9 0 11-18 0 9 9 0 0118 0z"/></svg>
                <h3>Riwayat Pergerakan Aset Internal</h3>
            </div>
            
            <div class="table-wrapper">
                <table>
                    <thead>
                        <tr>
                            <th>Waktu</th>
                            <th>Aktivitas</th>
                            <th>Jenis</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="log in recentLogs" :key="log.id_log">
                            <td class="time-col">{{ formatTime(log.waktu_log) }}</td>
                            <td class="act-col">{{ log.aktivitas }}</td>
                            <td>
                                <span class="badge-mini" :class="log.aktivitas.includes('isi') ? 'isi' : 'kosong'">
                                    {{ log.aktivitas.includes('isi') ? 'ISI' : 'KOSONG' }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import axios from 'axios';

const warehouse = ref({});
const trucks = ref([]);
const loading = ref(false);
const form = reactive({ from: '', to: '', qty: 0, type: 'kosong' });

const totalIsi = computed(() => {
    let sum = (warehouse.value.lantai_1_isi || 0) + (warehouse.value.lantai_2_isi || 0) + (warehouse.value.lantai_3_isi || 0);
    trucks.value.forEach(t => sum += t.stok_isi);
    return sum;
});

const totalKosong = computed(() => {
    let sum = (warehouse.value.lantai_1_kosong || 0) + (warehouse.value.lantai_2_kosong || 0) + (warehouse.value.lantai_3_kosong || 0);
    trucks.value.forEach(t => sum += t.stok_kosong);
    return sum;
});

const fetchData = async () => {
    try {
        const res = await axios.get('/api/fleet-resources');
        warehouse.value = res.data.stok_saat_ini || {};
        trucks.value = res.data.truk || [];
        
        // PANGGIL LOGS DI SINI
        await fetchLogs(); 
    } catch (e) {
        console.error("Gagal memuat data");
    }
};

const executeTransfer = async () => {
    if(!form.from || !form.to || form.qty <= 0) return alert("Lengkapi data pemindahan!");
    if(form.from === form.to) return alert("Sumber dan tujuan tidak boleh sama!");
    
    loading.value = true;
    const user = JSON.parse(sessionStorage.getItem('user'));
    
    try {
        const [fType, fId] = form.from.split('-');
        const [tType, tId] = form.to.split('-');

        await axios.post('/api/stock/transfer', {
            from_type: fType, from_id: fId,
            to_type: tType, to_id: tId,
            qty: form.qty, type: form.type,
            user_id: user.id
        });
        
        alert("Aset Berhasil Dipindahkan!");
        form.qty = 0;
        
        // REFRESH DATA & LOGS SETELAH BERHASIL
        await fetchData(); 
    } catch (e) {
        alert(e.response?.data?.message || "Terjadi kesalahan");
    } finally {
        loading.value = false;
    }
};

const recentLogs = ref([]);

const fetchLogs = async () => {
     try {
        const res = await axios.get('/api/audit-logs');
        const user = JSON.parse(sessionStorage.getItem('user'));
        // Ambil 10 log terbaru milik operator ini
        recentLogs.value = res.data.filter(l => l.id_user === user.id).slice(0, 10);
    } catch (e) {
        console.error("Gagal memuat log");
    }
};

const formatTime = (t) => {
    return new Date(t).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });
};

// Panggil fetchLogs() di dalam fetchData() dan executeTransfer()

onMounted(fetchData);
</script>

<style scoped>
.sirkulasi-container { padding: 30px; background: #f8fafc; min-height: 100vh; color: #1e293b; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
.title-area h1 { font-size: 26px; font-weight: 800; color: #0f172a; margin: 0; }
.title-area p { color: #64748b; margin-top: 5px; }
.summary-pills { display: flex; gap: 15px; }
.pill { background: white; padding: 10px 20px; border-radius: 12px; border: 1px solid #e2e8f0; display: flex; flex-direction: column; }
.p-label { font-size: 10px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.p-val { font-size: 18px; font-weight: 800; }

/* LAYOUT */
.main-grid { display: grid; grid-template-columns: 1fr 380px; gap: 30px; }

/* VISUAL LOCATIONS */
.location-group { background: transparent; }
.group-header { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; color: #1e3a8a; }
.group-header h3 { font-size: 16px; font-weight: 700; margin: 0; }

.floor-grid { display: flex; flex-direction: column; gap: 10px; }
.floor-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 20px; display: flex; justify-content: space-between; align-items: center; transition: 0.3s; }
.floor-card:hover { border-color: #2563eb; transform: translateX(5px); }
.floor-name { display: block; font-weight: 800; color: #1e293b; }
.floor-desc { font-size: 12px; color: #94a3b8; }

.floor-stats { display: flex; gap: 20px; }
.stat { text-align: center; }
.stat label { font-size: 9px; font-weight: 800; display: block; margin-bottom: 2px; }
.stat span { font-size: 20px; font-weight: 900; }
.stat.isi { color: #2563eb; }
.stat.kosong { color: #64748b; }

/* TRUCK VISUALS */
.truck-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; }
.truck-card { background: white; border: 1px solid #e2e8f0; border-radius: 16px; padding: 15px; }
.truck-plat { font-weight: 800; font-size: 14px; margin-bottom: 10px; color: #1e3a8a; }
.bar-group { margin-bottom: 8px; }
.bar-label { font-size: 10px; font-weight: 700; margin-bottom: 3px; color: #64748b; }
.bar-bg { height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
.bar-fill { height: 100%; transition: 0.5s ease; }
.bar-fill.blue { background: #2563eb; }
.bar-fill.gray { background: #94a3b8; }

/* ACTION FORM */
.card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
.sticky-form { padding: 30px; position: sticky; top: 30px; }
.card-header-icon { display: flex; align-items: center; gap: 15px; margin-bottom: 30px; }
.icon-circle { width: 48px; height: 48px; background: #eff6ff; color: #2563eb; border-radius: 12px; display: flex; align-items: center; justify-content: center; }

.input-block { margin-bottom: 20px; }
.input-block label { display: block; font-size: 11px; font-weight: 800; color: #94a3b8; margin-bottom: 8px; letter-spacing: 0.5px; }
.modern-select, .modern-input { width: 100%; padding: 12px 15px; border-radius: 12px; border: 1.5px solid #e2e8f0; outline: none; font-weight: 600; font-family: inherit; transition: 0.2s; }
.modern-select:focus, .modern-input:focus { border-color: #2563eb; background: #f0f7ff; }

.transfer-divider { display: flex; flex-direction: column; align-items: center; margin: -10px 0 10px 0; }
.transfer-divider .line { height: 20px; width: 2px; background: #cbd5e1; }
.down-arrow { font-size: 10px; color: #cbd5e1; }

.flex-gap { display: flex; gap: 15px; }
.flex-2 { flex: 2; }
.flex-1 { flex: 1; }

.btn-execute-main { width: 100%; padding: 16px; border-radius: 16px; border: none; background: #1e293b; color: white; font-weight: 800; cursor: pointer; transition: 0.2s; margin-top: 10px; }
.btn-execute-main:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 10px 20px -5px rgba(37, 99, 235, 0.4); }
.btn-execute-main:disabled { background: #cbd5e1; cursor: not-allowed; }

.mt-30 { margin-top: 30px; }
.text-blue { color: #2563eb; }
.text-gray { color: #64748b; }

/* STYLE UNTUK TABEL HISTORI */
.history-section {
    padding: 30px;
    margin-bottom: 50px;
}

.history-section h3 {
    font-size: 16px;
    font-weight: 700;
    margin: 0;
}

.table-wrapper {
    margin-top: 20px;
    overflow-x: auto;
}

.table-wrapper table {
    width: 100%;
    border-collapse: collapse;
}

.table-wrapper th {
    text-align: left;
    font-size: 11px;
    color: #94a3b8;
    text-transform: uppercase;
    padding: 12px 15px;
    border-bottom: 2px solid #f1f5f9;
}

.table-wrapper td {
    padding: 15px;
    border-bottom: 1px solid #f8fafc;
    font-size: 13px;
}

.time-col {
    font-weight: 700;
    color: #64748b;
    white-space: nowrap;
}

.act-col {
    color: #1e293b;
    font-weight: 500;
}

.badge-mini {
    padding: 4px 8px;
    border-radius: 6px;
    font-size: 10px;
    font-weight: 800;
}

.badge-mini.isi {
    background: #eff6ff;
    color: #2563eb;
}

.badge-mini.kosong {
    background: #f1f5f9;
    color: #64748b;
}

/* Animasi Putar Icon Refresh saat Loading */
.rotate svg {
    animation: spin 1s linear infinite;
}

@keyframes spin {
    from { transform: rotate(0deg); }
    to { transform: rotate(360deg); }
}
</style>