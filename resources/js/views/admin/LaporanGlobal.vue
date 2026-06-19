<template>
    <div class="rekap-container">
        <div class="page-header">
            <div class="title-group">
                <h1>Rekapitulasi & Log Sistem</h1>
                <p>Ekstraksi data operasional dan jejak digital ke format Excel.</p>
            </div>
        </div>

        <!-- RENTANG TANGGAL GLOBAL (Satu filter untuk dua jenis laporan) -->
        <div class="card date-filter-global">
            <div class="date-inputs">
                <div class="f-group">
                    <label>Dari Tanggal</label>
                    <input type="date" v-model="filter.start" class="modern-input">
                </div>
                <div class="f-group">
                    <label>Sampai Tanggal</label>
                    <input type="date" v-model="filter.end" class="modern-input">
                </div>
            </div>
        </div>

        <div class="grid-layout mt-24">
            <!-- KARTU 1: LAPORAN DISTRIBUSI -->
            <div class="card export-card blue">
                <div class="card-body">
                    <div class="icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg></div>
                    <h3>Rekap Distribusi LPG</h3>
                    <p>Data pengiriman, sumber SPBE, dan status penerimaan pangkalan.</p>
                    <button @click="downloadDistribusi" class="btn-action">
                        📗 Download Distribusi (.xlsx)
                    </button>
                </div>
            </div>

            <!-- KARTU 2: LOG AUDIT (REKOMENDASI KAMU) -->
            <div class="card export-card orange">
                <div class="card-body">
                    <div class="icon-box"><svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"></circle><polyline points="12 6 12 12 16 14"></polyline></svg></div>
                    <h3>Audit Trail Activity</h3>
                    <p>Jejak digital seluruh aktivitas user, login, dan perubahan stok gudang.</p>
                    <button @click="downloadAudit" class="btn-action">
                        📙 Download Audit Trail (.xlsx)
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive } from 'vue';

const filter = reactive({
    start: new Date().toISOString().substr(0, 10),
    end: new Date().toISOString().substr(0, 10)
});

const downloadDistribusi = () => {
    window.open(`/api/export/rekap-excel?start=${filter.start}&end=${filter.end}`, '_blank');
};

const downloadAudit = () => {
    window.open(`/api/export/audit-excel?start=${filter.start}&end=${filter.end}`, '_blank');
};
</script>

<style scoped>
.rekap-container { padding: 30px; background: #f8fafc; min-height: 100vh; }
.page-header h1 { font-size: 26px; font-weight: 900; color: #1e3a8a; }

.date-filter-global { background: white; padding: 25px; border-radius: 16px; margin-bottom: 24px; border: 1px solid #e2e8f0; }
.date-inputs { display: flex; gap: 20px; max-width: 500px; }
.f-group { flex: 1; }
.f-group label { display: block; font-size: 11px; font-weight: 800; color: #94a3b8; margin-bottom: 8px; text-transform: uppercase; }
.modern-input { width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #d1e9ff; outline: none; font-weight: 600; }

.grid-layout { display: grid; grid-template-columns: 1fr 1fr; gap: 24px; }
.export-card { background: white; border-radius: 24px; border: 1px solid #e2e8f0; transition: 0.3s; }
.export-card:hover { transform: translateY(-5px); box-shadow: 0 10px 25px -5px rgba(0,0,0,0.1); }
.card-body { padding: 30px; }

.icon-box { width: 50px; height: 50px; border-radius: 14px; display: flex; align-items: center; justify-content: center; margin-bottom: 20px; }
.blue .icon-box { background: #eff6ff; color: #2563eb; }
.orange .icon-box { background: #fff7ed; color: #f59e0b; }

.export-card h3 { font-size: 18px; font-weight: 800; margin: 0 0 10px 0; }
.export-card p { font-size: 13px; color: #64748b; margin-bottom: 25px; line-height: 1.5; height: 40px; }

.btn-action { 
    width: 100%; padding: 14px; border-radius: 12px; border: none; font-weight: 700; cursor: pointer; 
    transition: 0.2s; color: white;
}
.blue .btn-action { background: #2563eb; }
.orange .btn-action { background: #f59e0b; }
.btn-action:hover { opacity: 0.9; }

.mt-24 { margin-top: 24px; }
</style>