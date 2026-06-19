<template>
    <div class="master-container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="title-group">
                <h1>Sistem Manajemen Data Master</h1>
                <p>Pusat kendali aset, personel, dan mitra distribusi.</p>
            </div>
            <div class="action-header">
                <button class="btn-add-main" @click="openModal(activeTab)">
                    <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"></line><line x1="5" y1="12" x2="19" y2="12"></line></svg>
                    Tambah {{ activeTab }}
                </button>
            </div>
        </div>

        <!-- TAB NAVIGATION -->
        <div class="tabs-wrapper">
            <div class="tabs-nav">
                <button v-for="tab in ['karyawan', 'truk', 'pangkalan', 'spbe']" 
                    :key="tab" @click="activeTab = tab" :class="{ active: activeTab === tab }">
                    {{ tab.toUpperCase() }}
                </button>
            </div>
        </div>

        <div class="tab-content card">
            <!-- TAB KARYAWAN -->
            <div v-if="activeTab === 'karyawan'">
                <table class="modern-table">
                    <thead><tr><th>Nama Lengkap</th><th>Jabatan</th><th>Kontak</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="k in data.karyawan" :key="k.id_karyawan">
                            <td><div class="font-bold">{{ k.nama }}</div></td>
                            <td><span class="badge" :class="k.jabatan.toLowerCase()">{{ k.jabatan }}</span></td>
                            <td class="text-secondary">{{ k.no_hp }}</td>
                            <td class="text-right">
                                <button class="btn-icon edit" @click="editItem('karyawan', k)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                                <button class="btn-icon delete" @click="confirmDelete('karyawan', k.id_karyawan)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 3 21 3 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TAB TRUK -->
            <div v-else-if="activeTab === 'truk'">
                <table class="modern-table">
                    <thead><tr><th>Plat Nomor</th><th>Tipe Unit</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="t in data.truk" :key="t.id_truk">
                            <td><div class="plat-no">{{ t.plat_no }}</div></td>
                            <td>{{ t.tipe_truk }}</td>
                            <td class="text-right">
                                <button class="btn-icon edit" @click="editItem('truk', t)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                                <button class="btn-icon delete" @click="confirmDelete('truk', t.id_truk)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- TAB PANGKALAN -->
            <div v-else-if="activeTab === 'pangkalan'">
                <div class="filter-area">
                    <input type="text" v-model="searchPangkalan" placeholder="🔍 Cari nama pangkalan..." class="search-box">
                </div>
                <div class="table-scroll">
                    <table class="modern-table">
                        <thead><tr><th>Nama Pangkalan</th><th>Registrasi</th><th>Alamat</th><th class="text-right">Aksi</th></tr></thead>
                        <tbody>
                            <tr v-for="p in filteredPangkalan" :key="p.id_pangkalan">
                                <td><div class="font-bold">{{ p.nama_pangkalan }}</div></td>
                                <td><code class="code-sm">{{ p.no_registrasi }}</code></td>
                                <td class="small-text">{{ p.alamat }}</td>
                                <td class="text-right">
                                    <button class="btn-icon edit" @click="editItem('pangkalan', p)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                                    <button class="btn-icon delete" @click="confirmDelete('pangkalan', p.id_pangkalan)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TAB SPBE -->
            <div v-else>
                <table class="modern-table">
                    <thead><tr><th>Nama Sumber SPBE</th><th>Tgl Terdaftar</th><th class="text-right">Aksi</th></tr></thead>
                    <tbody>
                        <tr v-for="s in data.spbe" :key="s.id_spbe">
                            <td><b>{{ s.nama_spbe }}</b></td>
                            <td>{{ new Date(s.created_at).toLocaleDateString('id-ID') }}</td>
                            <td class="text-right">
                                <button class="btn-icon edit" @click="editItem('spbe', s)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg></button>
                                <button class="btn-icon delete" @click="confirmDelete('spbe', s.id_spbe)"><svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg></button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL CRUD DINAMIS -->
        <div v-if="showModal" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>{{ form.id ? 'Edit' : 'Tambah' }} {{ activeTab }}</h3>
                    <button @click="showModal = false" class="btn-close-x">&times;</button>
                </div>
                <form @submit.prevent="saveData">
                    <div class="modal-body">
                        <!-- Form Karyawan -->
                        <div v-if="activeTab === 'karyawan'" class="form-grid">
                            <div class="f-group"><label>Nama Lengkap</label><input v-model="form.nama" required></div>
                            <div class="f-group"><label>Jabatan</label><select v-model="form.jabatan"><option value="Supir">Supir</option><option value="Kernet">Kernet</option></select></div>
                            <div class="f-group"><label>Nomor WhatsApp</label><input v-model="form.no_hp" required></div>
                        </div>
                        <!-- Form Truk -->
                        <div v-if="activeTab === 'truk'" class="form-grid">
                            <div class="f-group"><label>Plat Nomor</label><input v-model="form.plat_no" placeholder="B 1234 ABC" required></div>
                            <div class="f-group"><label>Tipe Kendaraan</label><input v-model="form.tipe_truk" placeholder="Colt Diesel" required></div>
                        </div>
                        <!-- Form Pangkalan -->
                        <div v-if="activeTab === 'pangkalan'" class="form-grid">
                            <div class="f-group"><label>Nama Pangkalan</label><input v-model="form.nama_pangkalan" required></div>
                            <div class="f-group"><label>No. Registrasi</label><input v-model="form.no_registrasi" required></div>
                            <div class="f-group"><label>Alamat</label><input v-model="form.alamat" required></div>
                        </div>
                        <!-- Form SPBE -->
                        <div v-if="activeTab === 'spbe'" class="form-grid">
                            <div class="f-group"><label>Nama SPBE</label><input v-model="form.nama_spbe" required></div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="showModal = false" class="btn-cancel">Batal</button>
                        <button type="submit" class="btn-save" :disabled="submitting">
                            {{ submitting ? 'Menyimpan...' : 'Simpan Data' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, computed, reactive } from 'vue';
import axios from 'axios';

const activeTab = ref('karyawan');
const searchPangkalan = ref('');
const loading = ref(true);
const showModal = ref(false);
const submitting = ref(false);

const data = ref({ karyawan: [], truk: [], pangkalan: [], spbe: [] });
const form = reactive({ id: null, nama: '', jabatan: 'Supir', no_hp: '', plat_no: '', tipe_truk: '', nama_pangkalan: '', no_registrasi: '', alamat: '', nama_spbe: '' });

const fetchData = async () => {
    loading.value = true;
    try {
        const res = await axios.get('/api/master-all');
        data.value = res.data;
    } finally { loading.value = false; }
};

const openModal = (tab) => {
    Object.keys(form).forEach(key => form[key] = (key === 'jabatan' ? 'Supir' : ''));
    form.id = null;
    showModal.value = true;
};

const editItem = (type, item) => {
    const pk = type == 'karyawan' ? 'id_karyawan' : (type == 'truk' ? 'id_truk' : (type == 'pangkalan' ? 'id_pangkalan' : 'id_spbe'));
    Object.keys(form).forEach(key => form[key] = item[key] || '');
    form.id = item[pk]; // Simpan ID untuk update
    showModal.value = true;
};

const confirmDelete = async (type, id) => {
    if (!confirm("Hapus data ini secara permanen?")) return;
    try {
        await axios.delete(`/api/master/${type}/${id}`);
        fetchData();
    } catch (e) { alert("Gagal menghapus data"); }
};

const saveData = async () => {
    submitting.value = true;
    try {
        await axios.post(`/api/master/${activeTab.value}/save`, form);
        showModal.value = false;
        fetchData();
    } catch (e) {
        alert(e.response?.data?.message || "Terjadi kesalahan");
    } finally { submitting.value = false; }
};

const filteredPangkalan = computed(() => {
    return (data.value.pangkalan || []).filter(p => p.nama_pangkalan.toLowerCase().includes(searchPangkalan.value.toLowerCase()));
});

onMounted(fetchData);
</script>

<style scoped>
.master-container { padding: 30px; background: #f0f7ff; min-height: 100vh; font-family: 'Inter', sans-serif; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.title-group h1 { font-size: 26px; font-weight: 800; color: #1e3a8a; margin: 0; }
.title-group p { color: #64748b; font-size: 14px; margin: 5px 0 0; }

.btn-add-main { 
    background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 12px; 
    font-weight: 700; display: flex; align-items: center; gap: 10px; cursor: pointer; transition: 0.2s;
}
.btn-add-main:hover { background: #1e40af; transform: translateY(-2px); box-shadow: 0 10px 15px -3px rgba(37, 99, 235, 0.3); }

/* TABS */
.tabs-wrapper { border-bottom: 2px solid #e2e8f0; margin-bottom: 20px; }
.tabs-nav { display: flex; gap: 5px; }
.tabs-nav button {
    padding: 12px 30px; border: none; background: none; color: #94a3b8;
    font-weight: 700; font-size: 13px; cursor: pointer; position: relative; transition: 0.2s;
}
.tabs-nav button.active { color: #2563eb; }
.tabs-nav button.active::after {
    content: ''; position: absolute; bottom: -2px; left: 0; right: 0; 
    height: 3px; background: #2563eb; border-radius: 10px 10px 0 0;
}

/* TABLE */
.card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 10px; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.modern-table { width: 100%; border-collapse: collapse; }
.modern-table th { text-align: left; padding: 15px 20px; background: #f8fafc; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; }
.modern-table td { padding: 18px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; color: #334155; }

.badge { padding: 4px 12px; border-radius: 6px; font-size: 11px; font-weight: 800; text-transform: uppercase; }
.badge.supir { background: #dcfce7; color: #16a34a; }
.badge.kernet { background: #fff7ed; color: #c2410c; }

.plat-no { background: #1e293b; color: white; padding: 4px 10px; border-radius: 6px; font-family: monospace; width: fit-content; font-weight: 700; }

/* ACTIONS */
.btn-icon { width: 32px; height: 32px; border-radius: 8px; border: none; cursor: pointer; transition: 0.2s; margin-left: 5px; }
.btn-icon.edit { background: #f0fdf4; color: #16a34a; }
.btn-icon.edit:hover { background: #16a34a; color: white; }
.btn-icon.delete { background: #fef2f2; color: #dc2626; }
.btn-icon.delete:hover { background: #dc2626; color: white; }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
.modal-box { background: white; width: 450px; border-radius: 24px; overflow: hidden; animation: slideUp 0.3s ease-out; box-shadow: 0 25px 50px -12px rgba(0, 0, 0, 0.25); }
.modal-header { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.modal-body { padding: 25px; }
.f-group { margin-bottom: 15px; }
.f-group label { display: block; font-size: 12px; font-weight: 800; color: #64748B; text-transform: uppercase; margin-bottom: 6px; }
.f-group input, .f-group select { width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid #E2E8F0; outline: none; transition: 0.2s; font-family: inherit; }
.f-group input:focus { border-color: #2563EB; box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1); }

.modal-footer { padding: 20px 25px; background: #f8fafc; display: flex; justify-content: flex-end; gap: 10px; }
.btn-save { background: #2563EB; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; }
.btn-cancel { background: white; color: #64748B; border: 1.5px solid #E2E8F0; padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; }

@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>