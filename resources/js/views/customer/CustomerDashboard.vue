<template>
    <div class="dashboard-wrapper">
        <!-- HEADER -->
        <header class="main-header">
            <div class="header-content">
                <div class="profile-info">
                    <div class="avatar-box">
                        <span class="initial">{{ pangkalan.nama_pangkalan?.charAt(0) }}</span>
                    </div>
                    <div class="text-greet">
                        <p class="small-title">{{ greetingTime }},</p>
                        <h1 class="pangkalan-name">{{ pangkalan.nama_pangkalan }}</h1>
                    </div>
                </div>
                <div class="status-indicator">
                    <div class="pulse-dot"></div>
                    <span>Sistem Terhubung</span>
                </div>
            </div>
        </header>

        <!-- HERO CARD: DUAL STOCK MONITORING -->
        <div class="hero-card">
            <div class="hero-inner">
                <div class="stock-main-grid">
                    <div class="stock-item">
                        <span class="stock-label">Fisik di Toko</span>
                        <div class="main-number">
                            <span class="digit">{{ pangkalan.stok_saat_ini }}</span>
                            <span class="sub">Tabung</span>
                        </div>
                    </div>

                    <div class="v-divider"></div>

                    <div class="stock-item">
                        <span class="stock-label">Titipan di Gudang</span>
                        <div class="main-number">
                            <span class="digit text-gold">{{ pangkalan.stok_titipan }}</span>
                            <span class="sub">Tabung</span>
                        </div>
                    </div>
                </div>

                <div class="capacity-section">
                    <div class="cap-meta">
                        <span>Pemanfaatan Kapasitas Toko</span>
                        <span>{{ stockPercentage }}%</span>
                    </div>
                    <div class="progress-bar-bg">
                        <div class="progress-fill" :style="{ width: stockPercentage + '%' }"></div>
                    </div>
                </div>
            </div>
            <div class="bg-pattern"></div>
        </div>

        <!-- NOTIFIKASI PENGIRIMAN AKTIF -->
        <div v-if="upcomingDelivery" class="delivery-tracker">
            <div class="dt-icon">
                <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"></rect><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"></polygon><circle cx="5.5" cy="18.5" r="2.5"></circle><circle cx="18.5" cy="18.5" r="2.5"></circle></svg>
            </div>
            <div class="dt-info">
                <h4>Pengiriman Dalam Perjalanan</h4>
                <p>Armada sedang menuju lokasi Anda.</p>
            </div>
            <router-link to="/customer/konfirmasi" class="btn-check">Cek</router-link>
        </div>

        <!-- QUICK ACTION GRID -->
        <h3 class="section-label">Layanan Mandiri</h3>
        <div class="action-grid">
            <router-link to="/customer/konfirmasi" class="action-card">
                <div class="icon-circle bg-blue">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"></path><polyline points="22 4 12 14.01 9 11.01"></polyline></svg>
                </div>
                <div class="action-text">
                    <h4>Konfirmasi</h4>
                    <p>Terima Barang</p>
                </div>
            </router-link>

            <router-link to="/customer/monitoring" class="action-card">
                <div class="icon-circle bg-cyan">
                    <svg xmlns="http://www.w3.org/2000/svg" width="22" height="22" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"></line><line x1="12" y1="20" x2="12" y2="4"></line><line x1="6" y1="20" x2="6" y2="14"></line></svg>
                </div>
                <div class="action-text">
                    <h4>Monitoring</h4>
                    <p>Riwayat Stok</p>
                </div>
            </router-link>
        </div>

        <!-- CONTACT AREA -->
        <div class="contact-card">
            <div class="c-info">
                <p>Butuh bantuan stok?</p>
                <span class="manager-name">Hubungi Manager Area</span>
            </div>
            <a href="#" class="wa-btn">WhatsApp</a>
        </div>

        <!-- INFO ACCOUNT -->
        <div class="info-list-card">
            <div class="info-item">
                <span class="label">No. Registrasi</span>
                <span class="value">{{ pangkalan.no_registrasi }}</span>
            </div>
            <div class="info-item">
                <span class="label">Alamat Toko</span>
                <span class="value">{{ pangkalan.alamat }}</span>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue';
import axios from 'axios';

const pangkalan = ref({});
const upcomingDelivery = ref(null);
const user = JSON.parse(sessionStorage.getItem('user'));

const greetingTime = computed(() => {
    const hour = new Date().getHours();
    if (hour < 12) return 'Selamat Pagi';
    if (hour < 15) return 'Selamat Siang';
    if (hour < 18) return 'Selamat Sore';
    return 'Selamat Malam';
});

const stockPercentage = computed(() => {
    const limit = 200; // Asumsi kapasitas maksimal pangkalan
    const current = pangkalan.value.stok_saat_ini || 0;
    return Math.min(Math.round((current / limit) * 100), 100);
});

const fetchData = async () => {
    try {
        const res = await axios.get(`/api/pangkalan-dashboard/${user.id}`);
        pangkalan.value = res.data.info;
        
        const resNotif = await axios.get(`/api/customer-notif/${user.id}`);
        if(resNotif.data.length > 0) upcomingDelivery.value = resNotif.data[0];
    } catch (e) {
        console.error("Dashboard error");
    }
};

onMounted(fetchData);
</script>

<style scoped>
.dashboard-wrapper { background-color: #f4f9ff; min-height: 100vh; padding: 20px; font-family: 'Inter', sans-serif; color: #1e3a8a; }
.main-header { margin-bottom: 25px; }
.header-content { display: flex; justify-content: space-between; align-items: center; }
.profile-info { display: flex; align-items: center; gap: 12px; }
.avatar-box { width: 48px; height: 48px; background: #ffffff; color: #2563eb; border-radius: 14px; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 20px; box-shadow: 0 8px 15px rgba(37, 99, 235, 0.1); }
.small-title { font-size: 13px; color: #60a5fa; margin: 0; font-weight: 600; }
.pangkalan-name { font-size: 18px; margin: 0; font-weight: 800; }
.status-indicator { background: #ffffff; padding: 6px 12px; border-radius: 50px; font-size: 11px; font-weight: 700; color: #10b981; display: flex; align-items: center; gap: 8px; border: 1px solid #e2e8f0; }
.pulse-dot { width: 7px; height: 7px; background: #10b981; border-radius: 50%; animation: pulse 2s infinite; }

.hero-card { background: linear-gradient(135deg, #1e40af 0%, #3b82f6 100%); border-radius: 28px; padding: 25px; color: white; position: relative; overflow: hidden; box-shadow: 0 20px 30px -10px rgba(30, 64, 175, 0.3); margin-bottom: 25px; }
.hero-inner { position: relative; z-index: 2; }
.stock-main-grid { display: flex; align-items: center; margin-bottom: 25px; }
.stock-item { flex: 1; }
.v-divider { width: 1px; height: 40px; background: rgba(255,255,255,0.2); margin: 0 20px; }
.stock-label { font-size: 11px; text-transform: uppercase; letter-spacing: 1px; opacity: 0.8; font-weight: 700; display: block; margin-bottom: 8px; }
.main-number { display: flex; align-items: baseline; gap: 5px; }
.digit { font-size: 36px; font-weight: 900; line-height: 1; }
.sub { font-size: 12px; opacity: 0.7; font-weight: 600; }
.text-gold { color: #fbbf24; }

.capacity-section { border-top: 1px solid rgba(255,255,255,0.1); padding-top: 20px; }
.cap-meta { display: flex; justify-content: space-between; font-size: 11px; font-weight: 700; margin-bottom: 8px; }
.progress-bar-bg { height: 8px; background: rgba(255,255,255,0.15); border-radius: 10px; }
.progress-fill { height: 100%; background: #ffffff; border-radius: 10px; transition: width 1s ease; }
.bg-pattern { position: absolute; top: -10%; right: -10%; width: 180px; height: 180px; background: rgba(255,255,255,0.05); border-radius: 50%; }

.delivery-tracker { background: #fff; padding: 15px 20px; border-radius: 20px; display: flex; align-items: center; gap: 15px; margin-bottom: 25px; border: 1.5px solid #dbeafe; box-shadow: 0 4px 10px rgba(37, 99, 235, 0.05); }
.dt-icon { font-size: 24px; color: #2563eb; }
.dt-info { flex: 1; }
.dt-info h4 { margin: 0; font-size: 13px; font-weight: 800; color: #1e3a8a; }
.dt-info p { margin: 2px 0 0 0; font-size: 11px; color: #64748b; }
.btn-check { background: #eff6ff; color: #2563eb; text-decoration: none; padding: 6px 14px; border-radius: 10px; font-size: 12px; font-weight: 700; }

.section-label { font-size: 14px; font-weight: 800; color: #1e3a8a; margin: 0 0 15px 5px; text-transform: uppercase; }
.action-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 15px; margin-bottom: 25px; }
.action-card { background: white; padding: 20px; border-radius: 24px; text-decoration: none; border: 1px solid #eef2ff; display: flex; flex-direction: column; align-items: flex-start; transition: all 0.2s; }
.action-card:active { transform: scale(0.96); background: #f8faff; }
.icon-circle { width: 44px; height: 44px; border-radius: 12px; display: flex; align-items: center; justify-content: center; margin-bottom: 12px; }
.bg-blue { background: #eff6ff; color: #2563eb; }
.bg-cyan { background: #ecfeff; color: #0891b2; }
.action-text h4 { margin: 0; font-size: 14px; font-weight: 800; color: #1e3a8a; }
.action-text p { margin: 2px 0 0; font-size: 11px; color: #94a3b8; font-weight: 600; }

.contact-card { background: #e0f2fe; border-radius: 20px; padding: 20px; display: flex; justify-content: space-between; align-items: center; margin-bottom: 25px; border: 1px solid #bae6fd; }
.c-info p { margin: 0; font-size: 12px; color: #0369a1; font-weight: 600; }
.manager-name { font-size: 14px; font-weight: 800; color: #0c4a6e; }
.wa-btn { background: #10b981; color: white; text-decoration: none; padding: 8px 16px; border-radius: 12px; font-size: 12px; font-weight: 700; }

.info-list-card { background: white; border-radius: 20px; padding: 5px 20px; border: 1px solid #eef2ff; }
.info-item { display: flex; justify-content: space-between; padding: 15px 0; border-bottom: 1px solid #f1f5f9; }
.info-item:last-child { border: none; }
.info-item .label { font-size: 12px; color: #94a3b8; font-weight: 600; }
.info-item .value { font-size: 13px; color: #1e293b; font-weight: 700; text-align: right; max-width: 60%; }

@keyframes pulse {
    0% { transform: scale(1); opacity: 1; }
    50% { transform: scale(1.3); opacity: 0.5; }
    100% { transform: scale(1); opacity: 1; }
}
</style>