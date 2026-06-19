<template>
    <div class="reconciliation-wrapper">
        <!-- HEADER: Audit Context -->
        <header class="page-header">
            <div class="title-meta">
                <span class="badge-tag">Internal Audit</span>
                <h1>Rekonsiliasi Stok Fisik</h1>
                <p>Verifikasi integritas aset antara catatan digital dan perhitungan lapangan.</p>
            </div>
            
            <div class="global-audit-pill" :class="{ 'has-diff': totalDiscrepancy !== 0 }">
                <div class="pill-info">
                    <span class="pill-label">Total Selisih Aset</span>
                    <h2 class="pill-value">{{ totalDiscrepancy > 0 ? '+' : '' }}{{ totalDiscrepancy }} <span>Unit</span></h2>
                </div>
                <div class="pill-status">
                    <svg v-if="totalDiscrepancy === 0" xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                    <svg v-else xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="8" x2="12" y2="12"></line><line x1="12" y1="16" x2="12.01" y2="16"></line></svg>
                    {{ totalDiscrepancy === 0 ? 'Data Akurat' : 'Perlu Penyesuaian' }}
                </div>
            </div>
        </header>

        <!-- MAIN AUDIT INTERFACE -->
        <div class="audit-card">
            <div class="table-container">
                <table class="audit-table">
                    <thead>
                        <tr>
                            <th rowspan="2" class="col-location">Lokasi Inventaris</th>
                            <th colspan="2" class="col-group sys">Catatan Sistem (Digital)</th>
                            <th colspan="2" class="col-group field">Hitung Fisik (Lapangan)</th>
                            <th rowspan="2" class="col-diff">Selisih</th>
                        </tr>
                        <tr>
                            <th class="sub-th">Isi</th>
                            <th class="sub-th">Kosong</th>
                            <th class="sub-th">Isi</th>
                            <th class="sub-th">Kosong</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="item in auditItems" :key="item.key" :class="{ 'row-warning': (item.isi_fisik + item.kosong_fisik) !== (item.isi_sys + item.kosong_sys) }">
                            <td class="cell-loc">
                                <div class="loc-wrapper">
                                    <div class="loc-icon">{{ item.type === 'floor' ? '🏢' : '🚛' }}</div>
                                    <div class="loc-text">
                                        <span class="n-main">{{ item.nama }}</span>
                                        <span class="n-sub">{{ item.type === 'floor' ? 'Gudang Pusat' : 'Armada Logistik' }}</span>
                                    </div>
                                </div>
                            </td>
                            <!-- System Data (Read Only) -->
                            <td class="cell-sys">{{ item.isi_sys }}</td>
                            <td class="cell-sys border-right">{{ item.kosong_sys }}</td>
                            
                            <!-- Field Input (Editable) -->
                            <td class="cell-input">
                                <input type="number" v-model.number="item.isi_fisik" class="audit-input" @focus="$event.target.select()">
                            </td>
                            <td class="cell-input">
                                <input type="number" v-model.number="item.kosong_fisik" class="audit-input" @focus="$event.target.select()">
                            </td>

                            <!-- Calculation Result -->
                            <td class="cell-diff" :class="{ 'red-text': (item.isi_fisik + item.kosong_fisik) !== (item.isi_sys + item.kosong_sys) }">
                                <span class="diff-chip">
                                    {{ (item.isi_fisik + item.kosong_fisik) - (item.isi_sys + item.kosong_sys) }}
                                </span>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- AUDIT FOOTER -->
            <div class="audit-footer">
                <div class="footer-left">
                    <label>Catatan & Justifikasi Audit</label>
                    <textarea v-model="catatan" placeholder="Wajib menjelaskan alasan jika terdapat selisih stok (misal: kebocoran, kehilangan, atau kerusakan tabung)..."></textarea>
                </div>
                <div class="footer-right">
                    <div class="info-box">
                        <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><line x1="12" y1="16" x2="12" y2="12"></line><line x1="12" y1="8" x2="12.01" y2="8"></line></svg>
                        <p>Konfirmasi akan merubah saldo aset sistem secara permanen dan mencatat log aktivitas ke dashboard Manager.</p>
                    </div>
                    <button @click="handleReconcile" :disabled="loading" class="btn-submit">
                        <span v-if="!loading">Publish & Sinkronisasi Saldo</span>
                        <span v-else>Memproses Audit...</span>
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const auditItems = ref([]);
const catatan = ref('');
const loading = ref(false);

const fetchData = async () => {
    try {
        const res = await axios.get('/api/stock/audit-data');
        const items = [];
        
        for (let i = 1; i <= 3; i++) {
            items.push({
                key: 'floor-'+i, id: i, type: 'floor', nama: 'Lantai ' + i,
                isi_sys: res.data.warehouse['lantai_'+i+'_isi'],
                kosong_sys: res.data.warehouse['lantai_'+i+'_kosong'],
                isi_fisik: res.data.warehouse['lantai_'+i+'_isi'],
                kosong_fisik: res.data.warehouse['lantai_'+i+'_kosong']
            });
        }
        res.data.trucks.forEach(t => {
            items.push({
                key: 'truck-'+t.id_truk, id: t.id_truk, type: 'truck', nama: t.plat_no,
                isi_sys: t.stok_isi, kosong_sys: t.stok_kosong,
                isi_fisik: t.stok_isi, kosong_fisik: t.stok_kosong
            });
        });
        auditItems.value = items;
    } catch (e) {
        console.error("Gagal sinkron data audit.");
    }
};

const totalDiscrepancy = computed(() => {
    return auditItems.value.reduce((acc, item) => {
        return acc + ((item.isi_fisik + item.kosong_fisik) - (item.isi_sys + item.kosong_sys));
    }, 0);
});

const handleReconcile = async () => {
    // 1. Validasi manual sebelum kirim
    if (!catatan.value) {
        return alert("Wajib mengisi catatan alasan penyesuaian!");
    }

    const userData = sessionStorage.getItem('user');
    if (!userData) {
        return alert("Sesi login habis, silahkan login kembali.");
    }
    
    const user = JSON.parse(userData);

    if (!confirm("Tindakan ini adalah final dan akan merubah saldo sistem secara permanen. Lanjutkan?")) return;

    loading.value = true;
    try {
        const payload = {
            user_id: user.id, // Pastikan di database fieldnya 'id'
            catatan: catatan.value,
            items: auditItems.value
        };

        console.log("Data yang dikirim:", payload); // Cek di console apakah data lengkap

        const res = await axios.post('/api/stock/reconcile', payload);
        
        if (res.data.success) {
            alert("Rekonsiliasi Berhasil!");
            fetchData();
            catatan.value = '';
        }
    } catch (e) {
        // Tampilkan pesan error validasi dari Laravel jika ada
        if (e.response && e.response.status === 422) {
            console.error("Detail Error Validasi:", e.response.data.errors);
            alert("Gagal Validasi: " + JSON.stringify(e.response.data.errors));
        } else {
            alert("Gagal memproses rekonsiliasi: " + (e.response?.data?.message || "Internal Server Error"));
        }
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
</script>

<style scoped>
.reconciliation-wrapper { padding: 32px; background: #f8fafc; min-height: 100vh; font-family: 'Inter', sans-serif; color: #0f172a; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: flex-end; margin-bottom: 32px; }
.badge-tag { font-size: 11px; font-weight: 800; color: #2563eb; background: #eff6ff; padding: 4px 12px; border-radius: 6px; text-transform: uppercase; letter-spacing: 1px; }
.page-header h1 { font-size: 28px; font-weight: 900; margin: 8px 0 4px 0; letter-spacing: -1px; }
.page-header p { color: #64748b; font-size: 14px; }

.global-audit-pill { background: white; padding: 16px 24px; border-radius: 20px; border: 1px solid #e2e8f0; display: flex; align-items: center; gap: 24px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.global-audit-pill.has-diff { border-color: #f43f5e; background: #fff1f2; }
.pill-label { display: block; font-size: 10px; font-weight: 800; color: #94a3b8; letter-spacing: 0.5px; }
.pill-value { font-size: 24px; font-weight: 900; margin: 0; color: #1e3a8a; }
.pill-value span { font-size: 12px; font-weight: 600; color: #94a3b8; }
.pill-status { font-size: 12px; font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 8px; }
.has-diff .pill-status { color: #e11d48; }

/* TABLE CARD */
.audit-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; overflow: hidden; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.05); }
.table-container { overflow-x: auto; }
.audit-table { width: 100%; border-collapse: collapse; }

.audit-table th { padding: 16px 20px; font-size: 11px; font-weight: 800; text-transform: uppercase; text-align: left; background: #f8fafc; color: #64748b; border-bottom: 1px solid #e2e8f0; }
.col-group { text-align: center; border-left: 1px solid #e2e8f0; }
.sub-th { text-align: center !important; font-size: 10px !important; background: #fff !important; color: #94a3b8 !important; }

.audit-table td { padding: 16px 20px; border-bottom: 1px solid #f1f5f9; transition: 0.2s; }
.row-warning { background: #fff8f8; }

.cell-loc { min-width: 250px; }
.loc-wrapper { display: flex; align-items: center; gap: 15px; }
.loc-icon { font-size: 24px; width: 44px; height: 44px; background: #f1f5f9; border-radius: 12px; display: flex; align-items: center; justify-content: center; }
.n-main { display: block; font-weight: 800; font-size: 14px; color: #1e293b; }
.n-sub { font-size: 11px; color: #94a3b8; font-weight: 600; }

.cell-sys { text-align: center; font-weight: 700; color: #94a3b8; background: #fcfcfc; width: 80px; }
.border-right { border-right: 1px solid #f1f5f9; }

.cell-input { width: 100px; text-align: center; }
.audit-input { width: 70px; padding: 10px; border-radius: 10px; border: 2px solid #e2e8f0; text-align: center; font-weight: 800; color: #2563eb; outline: none; transition: 0.2s; }
.audit-input:focus { border-color: #2563eb; background: #eff6ff; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }

.cell-diff { text-align: center; font-weight: 900; color: #cbd5e1; width: 80px; }
.red-text { color: #e11d48; }
.diff-chip { background: #f1f5f9; padding: 4px 10px; border-radius: 8px; }
.red-text .diff-chip { background: #fee2e2; }

/* FOOTER */
.audit-footer { padding: 32px; display: grid; grid-template-columns: 1fr 350px; gap: 40px; background: #fcfcfc; }
.footer-left label { display: block; font-size: 13px; font-weight: 800; color: #334155; margin-bottom: 12px; }
.footer-left textarea { width: 100%; height: 110px; border-radius: 16px; border: 1px solid #e2e8f0; padding: 16px; font-family: inherit; font-size: 14px; outline: none; transition: 0.2s; }
.footer-left textarea:focus { border-color: #2563eb; box-shadow: 0 4px 12px rgba(0,0,0,0.05); }

.info-box { display: flex; gap: 12px; background: #fffbeb; border: 1px solid #fef3c7; padding: 16px; border-radius: 12px; margin-bottom: 20px; }
.info-box p { font-size: 12px; color: #92400e; line-height: 1.5; margin: 0; font-weight: 500; }

.btn-submit { width: 100%; padding: 18px; border-radius: 16px; border: none; background: #0f172a; color: white; font-weight: 800; font-size: 15px; cursor: pointer; transition: 0.3s; box-shadow: 0 10px 15px -3px rgba(15, 23, 42, 0.3); }
.btn-submit:hover { background: #2563eb; transform: translateY(-2px); box-shadow: 0 20px 25px -5px rgba(37, 99, 235, 0.4); }
.btn-submit:disabled { background: #cbd5e1; cursor: not-allowed; box-shadow: none; }
</style>