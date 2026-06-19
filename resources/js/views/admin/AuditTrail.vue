<template>
    <div class="audit-container">
        <div class="page-header">
            <div class="title-group">
                <h1>Log Audit Aktivitas</h1>
                <p>Rekaman seluruh jejak transaksi dan perubahan data sistem.</p>
                <button @click="exportExcel" class="btn-export-excel">
                    📗 Export ke Excel
                </button>
            </div>
            <div class="filter-group">
                <select v-model="filterRole" class="filter-select">
                    <option value="">Semua Role</option>
                    <option value="admin">Manager</option>
                    <option value="operator">Petugas Gudang</option>
                    <option value="customer">Pangkalan</option>
                </select>
            </div>
        </div>

        <div class="timeline-card card">
            <div v-if="loading" class="text-center p-10">Memuat log...</div>
            
            <div v-else class="timeline">
                <div v-for="log in filteredLogs" :key="log.id_log" class="timeline-item">
                    <!-- Penanda Waktu -->
                    <div class="time-side">
                        <div class="full-date">{{ formatDate(log.waktu_log) }}</div>
                        <div class="clock">{{ formatTime(log.waktu_log) }}</div>
                    </div>

                    <!-- Garis & Titik -->
                    <div class="line-side">
                        <div class="dot" :class="log.role"></div>
                        <div class="line"></div>
                    </div>

                    <!-- Detail Aktivitas -->
                    <div class="content-side">
                        <div class="user-info">
                            <span class="u-name">{{ log.name }}</span>
                            <span class="u-role-badge" :class="log.role">{{ log.role }}</span>
                        </div>
                        <p class="activity-text">{{ log.aktivitas }}</p>
                        <div class="device-tag">Sistem Terverifikasi</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const logs = ref([]);
const loading = ref(true);
const filterRole = ref('');

const exportExcel = () => {
    window.open('/api/export/audit-excel', '_blank');
};

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/audit-logs');
        logs.value = res.data;
    } finally {
        loading.value = false;
    }
};

const filteredLogs = computed(() => {
    if (!filterRole.value) return logs.value;
    return logs.value.filter(l => l.role === filterRole.value);
});

const formatDate = (d) => new Date(d).toLocaleDateString('id-ID', { day: 'numeric', month: 'short', year: 'numeric' });
const formatTime = (d) => new Date(d).toLocaleTimeString('id-ID', { hour: '2-digit', minute: '2-digit' });

onMounted(fetchData);
</script>

<style scoped>
.audit-container { padding: 30px; background: #f8fafc; min-height: 100vh; }
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 30px; }
.title-group h1 { font-size: 26px; font-weight: 800; color: #1e3a8a; margin: 0; }

.filter-select { padding: 10px 20px; border-radius: 12px; border: 1.5px solid #d1e9ff; outline: none; font-weight: 600; color: #475569; }

.timeline-card { background: white; border-radius: 20px; padding: 40px; border: 1px solid #e2e8f0; }

.timeline { display: flex; flex-direction: column; gap: 0; }
.timeline-item { display: flex; gap: 30px; min-height: 100px; }

.time-side { min-width: 120px; text-align: right; }
.full-date { font-size: 13px; font-weight: 700; color: #1e293b; }
.clock { font-size: 12px; color: #94a3b8; }

.line-side { display: flex; flex-direction: column; align-items: center; }
.dot { width: 14px; height: 14px; border-radius: 50%; border: 3px solid white; box-shadow: 0 0 0 2px #cbd5e1; z-index: 2; }
.dot.admin { box-shadow: 0 0 0 2px #2563eb; background: #2563eb; }
.dot.operator { box-shadow: 0 0 0 2px #f97316; background: #f97316; }
.dot.customer { box-shadow: 0 0 0 2px #10b981; background: #10b981; }

.line { width: 2px; flex-grow: 1; background: #e2e8f0; margin: 5px 0; }

.content-side { padding-bottom: 40px; flex: 1; }
.user-info { display: flex; align-items: center; gap: 10px; margin-bottom: 8px; }
.u-name { font-weight: 800; color: #1e293b; font-size: 15px; }
.u-role-badge { font-size: 10px; font-weight: 800; text-transform: uppercase; padding: 2px 8px; border-radius: 4px; }
.u-role-badge.admin { background: #eff6ff; color: #2563eb; }
.u-role-badge.operator { background: #fff7ed; color: #f97316; }
.u-role-badge.customer { background: #f0fdf4; color: #10b981; }

.activity-text { color: #475569; line-height: 1.6; margin: 0; font-size: 14px; }
.device-tag { font-size: 11px; color: #94a3b8; margin-top: 10px; font-style: italic; }
</style>