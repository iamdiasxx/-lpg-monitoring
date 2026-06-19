<template>
    <div class="app-container" :class="{ 'sidebar-collapsed': isCollapsed }">
        <!-- Kirim status ke Sidebar dan terima perintah toggle -->
        <Sidebar v-if="isLoggedIn" :isCollapsed="isCollapsed" @toggle="toggleSidebar" />
        
        <main :class="{ 'main-content': isLoggedIn, 'full-width': !isLoggedIn }">
            <div class="content-header" v-if="isLoggedIn">
                <h2>{{ currentPageTitle }}</h2>
            </div>
            <div class="page-body">
                <router-view></router-view>
            </div>
        </main>
    </div>
</template>

<script setup>
import { ref, computed } from 'vue';
import { useRoute } from 'vue-router';
import Sidebar from './components/Sidebar.vue';

const route = useRoute();
const isCollapsed = ref(false);

const toggleSidebar = () => {
    isCollapsed.value = !isCollapsed.value;
};

const isLoggedIn = computed(() => route.path !== '/');
const currentPageTitle = computed(() => {
    if (route.path.includes('admin')) return 'Panel Manager';
    if (route.path.includes('operator')) return 'Panel Gudang';
    if (route.path.includes('customer')) return 'Panel Pangkalan';
    return '';
});
</script>

<style>
/* Reset dasar */
body { margin: 0; background: #f8fafc; font-family: 'Inter', sans-serif; }

.app-container { display: flex; transition: all 0.3s ease; }    

.main-content {
    margin-left: 260px; /* Lebar sidebar */
    width: calc(100% - 260px);
    min-height: 100vh;
    transition: all 0.3s ease;
}

/* Saat Sidebar Tertutup */
.sidebar-collapsed .main-content {
    margin-left: 80px;
    width: calc(100% - 80px);
}

.full-width { width: 100%; }

.content-header {
    background: white;
    padding: 20px 40px;
    border-bottom: 1px solid #e2e8f0;
    display: flex;
    align-items: center;
}

.content-header h2 { margin: 0; color: #1e293b; font-size: 20px; }

.page-body { padding: 40px; }

/* Responsive untuk HP */
@media (max-width: 900px) {
    .main-content { margin-left: 0; width: 100%; }
    /* Nanti bisa ditambah toggle burger menu di sini */
}
</style>    