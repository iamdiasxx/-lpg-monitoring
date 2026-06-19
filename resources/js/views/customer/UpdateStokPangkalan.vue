<template>
    <div class="usage-container">
        <!-- HEADER SECTION -->
        <header class="page-header">
            <div class="title-block">
                <h1>Update Penjualan</h1>
                <p>Laporkan pergerakan stok keluar hari ini.</p>
            </div>
            <div class="date-badge">
                {{ todayDate }}
            </div>
        </header>

        <!-- HERO CARD: CURRENT STOCK -->
        <div class="stock-hero">
            <div class="hero-content">
                <div class="main-info">
                    <span class="label">Stok di Toko Saat Ini</span>
                    <h2 class="value">{{ currentStock }} <small>Tabung</small></h2>
                </div>
                <div class="hero-icon">
                    <svg xmlns="http://www.w3.org/2000/svg" width="40" height="40" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 16V8a2 2 0 0 0-1-1.73l-7-4a2 2 0 0 0-2 0l-7 4A2 2 0 0 0 3 8v8a2 2 0 0 0 1 1.73l7 4a2 2 0 0 0 2 0l7-4A2 2 0 0 0 21 16z"></path></svg>
                </div>
            </div>
            <div class="hero-bg-pattern"></div>
        </div>

        <!-- FORM UPDATE -->
        <div class="card form-card shadow-sm">
            <div class="f-group">
                <label>Jumlah Tabung Terjual</label>
                <div class="input-wrapper">
                    <button class="qty-btn" @click="qty > 0 ? qty-- : 0" :disabled="loading">-</button>
                    <input type="number" v-model.number="qty" placeholder="0" class="input-usage" min="0" :max="currentStock">
                    <button class="qty-btn" @click="qty < currentStock ? qty++ : currentStock" :disabled="loading">+</button>
                </div>
                <p class="input-hint">Gunakan tombol atau ketik manual jumlah penjualan.</p>
            </div>

            <!-- IDE BARU: PREVIEW SISA STOK -->
            <div class="prediction-box" v-if="qty > 0">
                <div class="pred-item">
                    <span>Estimasi Sisa Stok</span>
                    <b :class="{ 'text-danger': (currentStock - qty) < 20 }">
                        {{ currentStock - qty }} Unit
                    </b>
                </div>
                <div class="pred-bar">
                    <div class="pred-fill" :style="{ width: ((currentStock - qty) / 200 * 100) + '%' }"></div>
                </div>
            </div>

            <button @click="submitUsage" :disabled="loading || qty <= 0 || qty > currentStock" class="btn-submit-ocean">
                <svg v-if="!loading" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" style="margin-right: 8px;"><path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z"></path><polyline points="17 21 17 13 7 13 7 21"></polyline><polyline points="7 3 7 8 15 8"></polyline></svg>
                <span>{{ loading ? 'Sedang Memproses...' : 'Simpan Laporan Penjualan' }}</span>
            </button>
        </div>

        <!-- INFO BOX -->
        <div class="info-footer-box">
            <div class="i-icon">ℹ️</div>
            <p>Data penjualan yang Anda kirimkan akan membantu Manager merencanakan pengiriman alokasi berikutnya tepat waktu.</p>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const currentStock = ref(0);
const qty = ref(0);
const loading = ref(false);
const user = JSON.parse(sessionStorage.getItem('user'));

const todayDate = computed(() => {
    return new Date().toLocaleDateString('id-ID', { weekday: 'long', day: 'numeric', month: 'long', year: 'numeric' });
});

const fetchData = async () => {
    try {
        const res = await axios.get(`/api/pangkalan-dashboard/${user.id}`);
        currentStock.value = res.data.info.stok_saat_ini;
    } catch (e) {
        console.error("Gagal memuat data.");
    }
};

const submitUsage = async () => {
    if (qty.value > currentStock.value) return alert("Maaf, jumlah penjualan tidak boleh melebihi stok yang ada!");
    
    loading.value = true;
    try {
        await axios.post('/api/pangkalan/update-usage', {
            user_id: user.id,
            jumlah_jual: qty.value
        });
        alert("Laporan penjualan berhasil disimpan!");
        qty.value = 0;
        fetchData(); // Muat ulang data stok terbaru
    } catch (e) {
        alert("Terjadi kendala saat mengirim laporan.");
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
</script>

<style scoped>
.usage-container { padding: 25px; background-color: #f0f7ff; min-height: 100vh; font-family: 'Inter', sans-serif; color: #1e3a8a; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: flex-start; margin-bottom: 30px; }
.title-block h1 { font-size: 24px; font-weight: 900; margin: 0; color: #1e3a8a; letter-spacing: -0.5px; }
.title-block p { font-size: 13px; color: #60a5fa; margin-top: 4px; font-weight: 500; }
.date-badge { background: #ffffff; padding: 6px 12px; border-radius: 10px; font-size: 11px; font-weight: 700; color: #3b82f6; border: 1px solid #d1e9ff; }

/* STOCK HERO */
.stock-hero { 
    background: linear-gradient(135deg, #2563eb, #3b82f6); color: white; 
    padding: 30px; border-radius: 24px; position: relative; overflow: hidden;
    box-shadow: 0 15px 30px rgba(37, 99, 235, 0.2); margin-bottom: 30px;
}
.hero-content { display: flex; justify-content: space-between; align-items: center; position: relative; z-index: 2; }
.hero-icon { opacity: 0.2; }
.main-info .label { font-size: 12px; font-weight: 700; opacity: 0.8; text-transform: uppercase; letter-spacing: 1px; }
.main-info .value { font-size: 48px; font-weight: 900; margin: 10px 0 0 0; line-height: 1; }
.main-info .value small { font-size: 16px; font-weight: 400; opacity: 0.8; }
.hero-bg-pattern { position: absolute; bottom: -20px; right: -20px; width: 120px; height: 120px; background: rgba(255,255,255,0.1); border-radius: 50%; }

/* FORM CARD */
.form-card { background: white; padding: 30px; border-radius: 24px; border: 1px solid #e2e8f0; }
.f-group label { display: block; font-size: 14px; font-weight: 800; color: #1e3a8a; margin-bottom: 15px; text-transform: uppercase; text-align: center; }

.input-wrapper { display: flex; align-items: center; gap: 15px; }
.qty-btn { 
    width: 45px; height: 45px; border-radius: 12px; border: 1.5px solid #d1e9ff; 
    background: #f8fafc; font-size: 20px; font-weight: bold; color: #2563eb; cursor: pointer; transition: 0.2s;
}
.qty-btn:hover:not(:disabled) { background: #eff6ff; border-color: #2563eb; }
.qty-btn:disabled { opacity: 0.5; cursor: not-allowed; }

.input-usage { 
    flex: 1; padding: 15px; border-radius: 15px; border: 2px solid #d1e9ff; 
    font-size: 28px; font-weight: 900; text-align: center; color: #1e3a8a; outline: none; transition: 0.2s;
}
.input-usage:focus { border-color: #2563eb; background: #f0f7ff; }
.input-hint { font-size: 11px; color: #94a3b8; text-align: center; margin-top: 12px; }

/* PREDICTION */
.prediction-box { margin-top: 30px; padding-top: 20px; border-top: 1px dashed #e2e8f0; }
.pred-item { display: flex; justify-content: space-between; font-size: 13px; font-weight: 700; margin-bottom: 10px; }
.pred-item b { color: #10b981; }
.pred-item b.text-danger { color: #ef4444; }
.pred-bar { height: 6px; background: #f1f5f9; border-radius: 10px; overflow: hidden; }
.pred-fill { height: 100%; background: #10b981; transition: 0.5s ease; }

.btn-submit-ocean { 
    width: 100%; margin-top: 30px; padding: 18px; border-radius: 16px; border: none; 
    background: #1e293b; color: white; font-weight: 800; font-size: 15px; cursor: pointer; 
    transition: 0.3s; display: flex; align-items: center; justify-content: center;
}
.btn-submit-ocean:hover:not(:disabled) { background: #2563eb; transform: translateY(-2px); box-shadow: 0 10px 20px rgba(37, 99, 235, 0.2); }
.btn-submit-ocean:disabled { background: #cbd5e1; cursor: not-allowed; }

/* INFO FOOTER */
.info-footer-box { display: flex; gap: 12px; margin-top: 30px; padding: 15px; background: #e0efff; border-radius: 15px; border: 1px solid #bfdbfe; }
.i-icon { font-size: 18px; }
.info-footer-box p { margin: 0; font-size: 12px; color: #1e40af; line-height: 1.5; font-weight: 500; }

/* CHROME FIX */
input::-webkit-outer-spin-button, input::-webkit-inner-spin-button { -webkit-appearance: none; margin: 0; }
</style>