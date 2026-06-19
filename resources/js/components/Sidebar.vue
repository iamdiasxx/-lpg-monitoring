<template>
    <div class="sidebar" :class="{ 'collapsed': isCollapsed }">

        <!-- TOMBOL TOGGLE (MENGAMBANG DI PINGGIR) -->
        <button class="toggle-btn" @click="$emit('toggle')">
            <svg v-if="!isCollapsed" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m15 18-6-6 6-6"/></svg>
            <svg v-else xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m9 18 6-6-6-6"/></svg>
        </button>

        <div class="sidebar-header">
            <div class="logo-area">
                <svg xmlns="http://www.w3.org/2000/svg" width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#2563eb" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" class="logo-icon-fix">
                    <path d="M6 9h12"></path><path d="M9 5h6"></path><path d="M7 5a2 2 0 0 1 2-2h6a2 2 0 0 1 2 2v2H7V5Z"></path><path d="M5 9v10a2 2 0 0 0 2 2h10a2 2 0 0 0 2-2V9"></path>
                </svg>
                <span v-if="!isCollapsed" class="logo-text">LPG-MONITORING</span>
            </div>
        </div>

        <nav class="menu">
            <!-- DASHBOARD UMUM -->
            <router-link v-if="user.role !== 'guest'" :to="'/' + user.role" class="menu-item" title="Dashboard">
                <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="3" y="3" width="7" height="7"/><rect x="14" y="3" width="7" height="7"/><rect x="14" y="14" width="7" height="7"/><rect x="3" y="14" width="7" height="7"/></svg>
                <span v-if="!isCollapsed">Dashboard</span>
            </router-link>

            <!-- MENU ADMIN (Manager) -->
            <template v-if="user.role === 'admin'">
                <div v-if="!isCollapsed" class="menu-label">Pengawasan</div>
                <router-link to="/admin/audit" class="menu-item" title="Audit Trail">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                    <span v-if="!isCollapsed">Audit Trail</span>
                </router-link>
                <router-link to="/admin/laporan" class="menu-item" title="Monitoring">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="18" y1="20" x2="18" y2="10"/><line x1="12" y1="20" x2="12" y2="4"/><line x1="6" y1="20" x2="6" y2="14"/></svg>
                    <span v-if="!isCollapsed">Monitoring & Laporan</span>
                </router-link>
                <router-link to="/admin/rekap" class="menu-item" title="Rekap">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"></path><polyline points="14 2 14 8 20 8"></polyline><line x1="16" y1="13" x2="8" y2="13"></line><line x1="16" y1="17" x2="8" y2="17"></line></svg>
                    <span v-if="!isCollapsed">Laporan Rekapitulasi</span>
                </router-link>
                
                <div v-if="!isCollapsed" class="menu-label">Konfigurasi</div>
                <router-link to="/admin/alokasi" class="menu-item" title="Alokasi">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="8.5" cy="7" r="4"/><polyline points="17 11 19 13 23 9"/></svg>
                    <span v-if="!isCollapsed">Menetapkan Alokasi</span>
                </router-link>
                <router-link to="/admin/master" class="menu-item" title="Data Master">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><ellipse cx="12" cy="5" rx="9" ry="3"/><path d="M21 12c0 1.66-4 3-9 3s-9-1.34-9-3"/><path d="M3 5v14c0 1.66 4 3 9 3s9-1.34 9-3V5"/></svg>
                    <span v-if="!isCollapsed">Data Master</span>
                </router-link>
                <router-link to="/admin/users" class="menu-item" title="Users">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M17 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"/><circle cx="9" cy="7" r="4"/><path d="M23 21v-2a4 4 0 0 0-3-3.87"/><path d="M16 3.13a4 4 0 0 1 0 7.75"/></svg>
                    <span v-if="!isCollapsed">Manajemen User</span>
                </router-link>
            </template>

            <!-- MENU OPERATOR -->
            <template v-if="user.role === 'operator'">
                <div v-if="!isCollapsed" class="menu-label">Operasional</div>
                <router-link to="/operator/stok-gudang" class="menu-item" title="Stok">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M21 8V6a2 2 0 0 0-2-2H5a2 2 0 0 0-2 2v2"/><path d="M21 12v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-6"/><path d="M3 8h18"/><path d="M10 12h4"/></svg>
                    <span v-if="!isCollapsed">Update Stok Gudang</span>
                </router-link>
                <router-link to="/operator/transaksi-spbe" class="menu-item" title="SPBE">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M3 11l18-5v12l-18-5z"/><path d="M3 11v6"/><path d="M21 11v6"/></svg>
                    <span v-if="!isCollapsed">Transaksi SPBE</span>
                </router-link>
                <router-link to="/operator/distribusi" class="menu-item" title="Distribusi">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><rect x="1" y="3" width="15" height="13"/><polygon points="16 8 20 8 23 11 23 16 16 16 16 8"/><circle cx="5.5" cy="18.5" r="2.5"/><circle cx="18.5" cy="18.5" r="2.5"/></svg>
                    <span v-if="!isCollapsed">Status Distribusi</span>
                </router-link>
                <router-link to="/operator/rekonsiliasi" class="menu-item" title="Rekonsiliasi">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/><polyline points="14 2 14 8 20 8"/><line x1="16" y1="13" x2="8" y2="13"/><line x1="16" y1="17" x2="8" y2="17"/><polyline points="10 9 9 9 8 9"/></svg>
                    <span v-if="!isCollapsed">Rekonsiliasi</span>
                </router-link>
            </template>

            <!-- MENU CUSTOMER -->
            <template v-if="user.role === 'customer'">
                <div v-if="!isCollapsed" class="menu-label">Pangkalan</div>
                <router-link to="/customer/konfirmasi" class="menu-item" title="Terima">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span v-if="!isCollapsed">Konfirmasi Penerimaan</span>
                </router-link>
                <router-link to="/customer/update-stok" class="menu-item" title="Stok">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M23 4v6h-6"/><path d="M20.49 15a9 9 0 1 1-2.12-9.36L23 10"/></svg>
                    <span v-if="!isCollapsed">Update Stok Harian</span>
                </router-link>
                <router-link to="/customer/monitoring" class="menu-item" title="Pantau">
                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><circle cx="12" cy="12" r="10"/><line x1="2" y1="12" x2="22" y2="12"/><path d="M12 2a15.3 15.3 0 0 1 4 10 15.3 15.3 0 0 1-4 10 15.3 15.3 0 0 1-4-10 15.3 15.3 0 0 1 4-10z"/></svg>
                    <span v-if="!isCollapsed">Monitoring Stok</span>
                </router-link>
            </template>
        </nav>

        <!-- FOOTER SIDEBAR -->
        <div class="sidebar-footer">
            <div class="user-display" v-if="!isCollapsed">
                <div class="user-avatar">{{ user.name.charAt(0) }}</div>
                <div class="user-text">
                    <span class="u-name">{{ user.name }}</span>
                    <span class="u-role">{{ user.role }}</span>
                </div>
            </div>
            
            <router-link :to="'/' + user.role + '/profile'" class="menu-item" title="Profil">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M20 21v-2a4 4 0 0 0-4-4H8a4 4 0 0 0-4 4v2"></path><circle cx="12" cy="7" r="4"></circle></svg>
                <span v-if="!isCollapsed">Profil & Keamanan</span>
            </router-link>

            <button @click="handleLogout" class="logout-btn" title="Keluar">
                <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M9 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h4"/><polyline points="16 17 21 12 16 7"/><line x1="21" y1="12" x2="9" y2="12"/></svg>
                <span v-if="!isCollapsed">Keluar</span>
            </button>
        </div>
    </div>
</template>

<script setup>
import { useRouter } from 'vue-router';
import { computed, defineProps } from 'vue'; // Tambahkan defineProps

// Terima props isCollapsed dari App.vue
const props = defineProps({
    isCollapsed: Boolean
});

const router = useRouter();

const user = computed(() => {
    const userData = sessionStorage.getItem('user');
    return userData ? JSON.parse(userData) : { name: 'Guest', role: 'guest' };
});

const handleLogout = () => {
    sessionStorage.removeItem('user');
    router.push('/');
};
</script>

<style scoped>
.sidebar {
    width: 260px;
    background: #f0f7ff;
    color: #1e3a8a;
    height: 100vh;
    display: flex;
    flex-direction: column;
    position: fixed;
    left: 0; top: 0;
    border-right: 1px solid #d1e9ff;
    transition: all 0.3s cubic-bezier(0.4, 0, 0.2, 1);
    z-index: 1000;
}

/* KELAS KHUSUS SAAT TERTUTUP */
.sidebar.collapsed {
    width: 80px;
}

.sidebar-header {
    padding: 25px;
    border-bottom: 1px solid #d1e9ff;
}

.collapsed .sidebar-header {
    padding: 25px 0;
    text-align: center;
}

.logo-area { display: flex; align-items: center; gap: 10px; color: #2563eb; }
.collapsed .logo-area { justify-content: center; }

.logo-text { font-size: 15px; font-weight: 900; letter-spacing: 0.5px; color: #1e3a8a; }

.toggle-btn {
    position: absolute;
    right: -12px;
    top: 35px;
    width: 24px;
    height: 24px;
    background: #2563eb;
    color: white;
    border: none;
    border-radius: 50%;
    cursor: pointer;
    display: flex;
    align-items: center;
    justify-content: center;
    box-shadow: 0 4px 10px rgba(37, 99, 235, 0.2);
    z-index: 10;
}

.menu { padding: 15px; flex: 1; overflow-y: auto; overflow-x: hidden; }

.menu-label {
    font-size: 11px;
    color: #64748b;
    text-transform: uppercase;
    margin: 20px 0 8px 12px;
    font-weight: 700;
}

.menu-item {
    display: flex;
    align-items: center;
    gap: 12px;
    padding: 12px 15px;
    color: #475569;
    text-decoration: none;
    border-radius: 10px;
    margin-bottom: 4px;
    font-size: 14px;
    font-weight: 500;
    white-space: nowrap; /* Mencegah teks turun baris saat menciut */
}

.collapsed .menu-item {
    justify-content: center;
    padding: 12px 0;
    margin: 4px 10px;
}

.menu-item:hover { background: #e0efff; color: #2563eb; }

.router-link-active {
    background: #2563eb !important;
    color: white !important;
    box-shadow: 0 4px 12px rgba(37, 99, 235, 0.2);
}

/* FOOTER */
.sidebar-footer { padding: 20px; background: #e6f2ff; border-top: 1px solid #d1e9ff; }
.collapsed .sidebar-footer { padding: 20px 5px; }

.user-display { display: flex; align-items: center; gap: 10px; margin-bottom: 15px; }

.user-avatar {
    width: 36px; height: 36px;
    background: #2563eb; color: white;
    border-radius: 8px; display: flex;
    align-items: center; justify-content: center;
    font-weight: bold; flex-shrink: 0;
}

.u-name { font-size: 13px; font-weight: 700; color: #1e3a8a; }
.u-role { font-size: 11px; color: #64748b; text-transform: capitalize; }

.logout-btn {
    width: 100%;
    padding: 10px;
    background: transparent;
    color: #ef4444;
    border: 1px solid #fecaca;
    border-radius: 8px;
    cursor: pointer;
    font-weight: 600;
    display: flex;
    align-items: center;
    gap: 8px;
}
.collapsed .logout-btn { justify-content: center; border: none; }
</style>