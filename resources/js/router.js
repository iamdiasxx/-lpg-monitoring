import { createRouter, createWebHistory } from 'vue-router';
// ... (Import file yang sudah ada tetap dipertahankan)
import Login from './views/Login.vue';
import AdminDashboard from './views/admin/AdminDashboard.vue';
import OperatorDashboard from './views/operator/OperatorDashboard.vue';
import CustomerDashboard from './views/customer/CustomerDashboard.vue';
import ManageUsers from './views/admin/ManageUsers.vue';
import AlokasiForm from './views/admin/AlokasiForm.vue';
import TransaksiSpbe from './views/operator/TransaksiSpbe.vue'; 
import KonfirmasiPenerimaan from './views/customer/KonfirmasiPenerimaan.vue';
import MonitoringLaporan from './views/admin/MonitoringLaporan.vue';
import StatusDistribusi from './views/operator/StatusDistribusi.vue';
import MasterData from './views/admin/MasterData.vue';
import AuditTrail from './views/admin/AuditTrail.vue';
import UpdateStokGudang from './views/operator/UpdateStokGudang.vue';
import Rekonsiliasi from './views/operator/Rekonsiliasi.vue';
import LaporanGlobal from './views/admin/LaporanGlobal.vue';
import UpdateStokPangkalan from './views/customer/UpdateStokPangkalan.vue';
import MonitoringStok from './views/customer/MonitoringStok.vue';
import Profile from './views/Profile.vue';

const routes = [
    { path: '/', component: Login },

    // --- ADMIN / MANAGER ROUTES ---
    { path: '/admin', component: AdminDashboard, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/audit', component: AuditTrail, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/admin/laporan', component: MonitoringLaporan, meta: { requiresAuth: true, role: 'admin' } },
    { 
        path: '/admin/rekap', 
        component: LaporanGlobal, 
        meta: { requiresAuth: true, role: 'admin' } 
    },
    { path: '/admin/alokasi', component: AlokasiForm, meta: { requiresAuth: true, role: 'admin' } },
    { 
    path: '/admin/master', 
    component: MasterData, 
    meta: { requiresAuth: true, role: 'admin' } 
    },
    { path: '/admin/users', component: ManageUsers, meta: { requiresAuth: true, role: 'admin' } },

    // --- OPERATOR / GUDANG ROUTES ---
    { path: '/operator', component: OperatorDashboard, meta: { requiresAuth: true, role: 'operator' } },
    { path: '/operator/transaksi-spbe', component: TransaksiSpbe, meta: { requiresAuth: true, role: 'operator' } },
    { path: '/operator/distribusi', component: StatusDistribusi, meta: { requiresAuth: true, role: 'operator' } },
    // Menghapus duplikasi path /operator/distribusi yang lama
    { path: '/operator/stok-gudang', component: UpdateStokGudang, meta: { requiresAuth: true, role: 'operator' } },
    { path: '/operator/rekonsiliasi', component: Rekonsiliasi, meta: { requiresAuth: true, role: 'operator' } },
    { path: '/operator/monitoring-pangkalan', component: MonitoringStok, meta: { requiresAuth: true, role: 'operator' } },

    // --- CUSTOMER / PANGKALAN ROUTES ---
    { path: '/customer', component: CustomerDashboard, meta: { requiresAuth: true, role: 'customer' } },
    { path: '/customer/konfirmasi', component: KonfirmasiPenerimaan, meta: { requiresAuth: true, role: 'customer' } },
    { path: '/customer/update-stok', component: UpdateStokPangkalan, meta: { requiresAuth: true, role: 'customer' } },
    { 
        path: '/customer/monitoring', 
        component: MonitoringStok, // Ganti dari Default.vue ke MonitoringStok.vue
        meta: { requiresAuth: true, role: 'customer' } 
    },


    { path: '/admin/profile', component: Profile, meta: { requiresAuth: true, role: 'admin' } },
    { path: '/operator/profile', component: Profile, meta: { requiresAuth: true, role: 'operator' } },
    { path: '/customer/profile', component: Profile, meta: { requiresAuth: true, role: 'customer' } },
    
    // Catch All
    { path: '/:pathMatch(.*)*', redirect: '/' }
];

const router = createRouter({
    history: createWebHistory(),
    routes,
});

// Guard tetep sama...
router.beforeEach((to, from, next) => {
    const userData = sessionStorage.getItem('user');
    const user = userData ? JSON.parse(userData) : null;
    if (to.meta.requiresAuth && !user) {
        next('/');
    } else if (to.meta.role && user.role.toLowerCase() !== to.meta.role.toLowerCase()) {
        next('/'); 
    } else {
        next();
    }
});

export default router;