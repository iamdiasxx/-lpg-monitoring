<template>
    <div class="user-management-container">
        <!-- HEADER -->
        <div class="page-header">
            <div class="title-group">
                <h1>Manajemen Akses Pengguna</h1>
                <p>Kelola hak akses untuk Admin, Operator, dan Pangkalan.</p>
            </div>
            <button class="btn-primary" @click="openAddModal">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M16 21v-2a4 4 0 0 0-4-4H5a4 4 0 0 0-4 4v2"></path><circle cx="8.5" cy="7" r="4"></circle><line x1="20" y1="8" x2="20" y2="14"></line><line x1="17" y1="11" x2="23" y2="11"></line></svg>
                Tambah Pengguna Baru
            </button>
        </div>

        <!-- TABLE CARD -->
        <div class="card main-table-card">
            <div class="table-filter">
                <input type="text" v-model="search" placeholder="Cari berdasarkan nama atau email..." class="search-input">
            </div>

            <div class="table-wrapper">
                <table class="modern-table">
                    <thead>
                        <tr>
                            <th>Nama Pengguna</th>
                            <th>Email</th>
                            <th>Role / Hak Akses</th>
                            <th class="text-right">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="u in filteredUsers" :key="u.id">
                            <td>
                                <div class="user-cell">
                                    <div class="user-avatar">{{ u.name.charAt(0) }}</div>
                                    <span class="user-name">{{ u.name }}</span>
                                </div>
                            </td>
                            <td class="text-secondary">{{ u.email }}</td>
                            <td>
                                <span class="role-badge" :class="u.role">{{ u.role }}</span>
                            </td>
                            <td class="text-right">
                                <button class="action-btn edit" @click="editUser(u)" title="Edit User">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M11 4H4a2 2 0 0 0-2 2v14a2 2 0 0 0 2 2h14a2 2 0 0 0 2-2v-7"></path><path d="M18.5 2.5a2.121 2.121 0 1 1 3 3L12 15l-4 1 1-4 9.5-9.5z"></path></svg>
                                </button>
                                <button class="action-btn delete" @click="deleteUser(u.id)" title="Hapus User">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><polyline points="3 6 5 6 21 6"></polyline><path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2"></path></svg>
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>
        </div>

        <!-- MODAL FORM -->
        <div v-if="showModal" class="modal-overlay" @click.self="showModal = false">
            <div class="modal-content">
                <div class="modal-header">
                    <h3>{{ form.id ? 'Edit Data Pengguna' : 'Daftarkan Pengguna Baru' }}</h3>
                    <button @click="showModal = false" class="close-btn">&times;</button>
                </div>
                <form @submit.prevent="submitForm">
                    <div class="modal-body">
                        <div class="form-group">
                            <label>Nama Lengkap</label>
                            <input v-model="form.name" type="text" required placeholder="Masukkan nama...">
                        </div>
                        <div class="form-group">
                            <label>Alamat Email</label>
                            <input v-model="form.email" type="email" required placeholder="email@contoh.com">
                        </div>
                        <div class="form-group">
                            <label>Role / Jabatan</label>
                            <select v-model="form.role" required>
                                <option value="admin">Manager (Admin)</option>
                                <option value="operator">Petugas Gudang (Operator)</option>
                                <option value="customer">Pangkalan (Customer)</option>
                            </select>
                        </div>
                        <div class="form-group">
                            <label>{{ form.id ? 'Ganti Password (Kosongkan jika tidak diubah)' : 'Password Akun' }}</label>
                            <input v-model="form.password" type="password" :required="!form.id" placeholder="••••••••">
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" @click="showModal = false" class="btn-secondary">Batal</button>
                        <button type="submit" :disabled="loading" class="btn-primary-lg">
                            {{ loading ? 'Memproses...' : 'Simpan Data Pengguna' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive, onMounted, computed } from 'vue';
import axios from 'axios';

const users = ref([]);
const search = ref('');
const showModal = ref(false);
const loading = ref(false);
const form = reactive({ id: null, name: '', email: '', role: 'customer', password: '' });

const fetchData = async () => {
    try {
        const res = await axios.get('/api/users');
        users.value = res.data;
    } catch (e) { console.error(e); }
};

const openAddModal = () => {
    form.id = null;
    form.name = ''; form.email = ''; form.role = 'customer'; form.password = '';
    showModal.value = true;
};

const editUser = (user) => {
    form.id = user.id;
    form.name = user.name;
    form.email = user.email;
    form.role = user.role;
    form.password = ''; // Kosongkan password saat edit
    showModal.value = true;
};

const deleteUser = async (id) => {
    if (!confirm("Hapus user ini? Mereka tidak akan bisa login lagi.")) return;
    try {
        await axios.delete(`/api/users/${id}`);
        fetchData();
    } catch (e) { alert(e.response.data.message); }
};

const submitForm = async () => {
    loading.value = true;
    try {
        await axios.post('/api/users/save', form);
        showModal.value = false;
        fetchData();
        alert("Data berhasil disimpan!");
    } catch (e) {
        alert("Terjadi kesalahan. Email mungkin sudah digunakan.");
    } finally { loading.value = false; }
};

const filteredUsers = computed(() => {
    return users.value.filter(u => 
        u.name.toLowerCase().includes(search.value.toLowerCase()) ||
        u.email.toLowerCase().includes(search.value.toLowerCase())
    );
});

onMounted(fetchData);
</script>

<style scoped>
.user-management-container { padding: 30px; background: #f0f7ff; min-height: 100vh; font-family: 'Inter', sans-serif; }

/* HEADER */
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.title-group h1 { font-size: 26px; font-weight: 800; color: #1e3a8a; margin: 0; }
.title-group p { color: #64748b; font-size: 14px; margin-top: 5px; }

.btn-primary { 
    background: #2563eb; color: white; border: none; padding: 12px 24px; 
    border-radius: 12px; font-weight: 700; display: flex; align-items: center; gap: 10px; cursor: pointer;
}

/* TABLE CARD */
.main-table-card { background: white; border-radius: 20px; padding: 25px; border: 1px solid #e2e8f0; box-shadow: 0 4px 6px -1px rgba(0,0,0,0.05); }
.table-filter { margin-bottom: 20px; }
.search-input { width: 300px; padding: 12px 18px; border-radius: 10px; border: 1.5px solid #e2e8f0; outline: none; transition: 0.2s; }
.search-input:focus { border-color: #2563eb; }

.modern-table { width: 100%; border-collapse: collapse; }
.modern-table th { text-align: left; padding: 15px; color: #64748b; font-size: 11px; text-transform: uppercase; letter-spacing: 1px; border-bottom: 2px solid #f1f5f9; }
.modern-table td { padding: 15px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

.user-cell { display: flex; align-items: center; gap: 12px; }
.user-avatar { width: 35px; height: 35px; background: #e0efff; color: #2563eb; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-weight: 800; font-size: 14px; }
.user-name { font-weight: 700; color: #1e293b; }

.role-badge { padding: 4px 12px; border-radius: 50px; font-size: 10px; font-weight: 800; text-transform: uppercase; }
.role-badge.admin { background: #eff6ff; color: #2563eb; }
.role-badge.operator { background: #fff7ed; color: #f97316; }
.role-badge.customer { background: #f0fdf4; color: #10b981; }

.action-btn { width: 35px; height: 35px; border-radius: 10px; border: none; cursor: pointer; margin-left: 8px; transition: 0.2s; }
.action-btn.edit { background: #f0fdf4; color: #16a34a; }
.action-btn.delete { background: #fef2f2; color: #dc2626; }
.action-btn:hover { transform: scale(1.1); }

/* MODAL */
.modal-overlay { position: fixed; inset: 0; background: rgba(15, 23, 42, 0.6); display: flex; align-items: center; justify-content: center; z-index: 1000; backdrop-filter: blur(4px); }
.modal-content { background: white; width: 100%; max-width: 450px; border-radius: 24px; overflow: hidden; animation: slideUp 0.3s ease; }
.modal-header { padding: 20px 25px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; align-items: center; }
.close-btn { background: none; border: none; font-size: 24px; cursor: pointer; color: #94a3b8; }
.modal-body { padding: 25px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-size: 12px; font-weight: 700; color: #64748b; margin-bottom: 6px; }
.form-group input, .form-group select { width: 100%; padding: 12px; border-radius: 12px; border: 1.5px solid #e2e8f0; outline: none; }

.modal-footer { padding: 20px 25px; background: #f8fafc; display: flex; justify-content: flex-end; gap: 10px; }
.btn-primary-lg { background: #2563eb; color: white; border: none; padding: 12px 24px; border-radius: 12px; font-weight: 700; cursor: pointer; flex: 1; }
.btn-secondary { background: white; border: 1.5px solid #e2e8f0; padding: 12px 24px; border-radius: 12px; font-weight: 700; color: #64748b; cursor: pointer; }

@keyframes slideUp { from { transform: translateY(20px); opacity: 0; } to { transform: translateY(0); opacity: 1; } }
</style>