<template>
    <div class="personel-container">
        <div class="page-header">
            <div class="title-block">
                <h1>Personel Distribusi</h1>
                <p>Kelola data supir dan kru armada pengiriman LPG.</p>
            </div>
            <button @click="showModal = true" class="btn-add">
                <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                Tambah Personel
            </button>
        </div>

        <!-- TABEL DATA -->
        <div class="card table-card">
            <table>
                <thead>
                    <tr>
                        <th>Nama Lengkap</th>
                        <th>Jabatan</th>
                        <th>No. WhatsApp</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-if="personels.length === 0">
                        <td colspan="4" class="text-center">Belum ada data personel.</td>
                    </tr>
                    <tr v-for="p in personels" :key="p.id_karyawan">
                        <td class="font-bold">{{ p.nama }}</td>
                        <td>
                            <span class="badge-role" :class="p.jabatan.toLowerCase()">{{ p.jabatan }}</span>
                        </td>
                        <td>{{ p.no_hp }}</td>
                        <td>
                            <button class="btn-edit">Edit</button>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- MODAL TAMBAH PERSONEL -->
        <div v-if="showModal" class="modal-overlay">
            <div class="modal-box">
                <div class="modal-header">
                    <h3>Tambah Personel Baru</h3>
                    <button @click="showModal = false" class="btn-close">&times;</button>
                </div>
                <div class="modal-body">
                    <div class="form-group">
                        <label>Nama Lengkap</label>
                        <input v-model="form.nama" type="text" placeholder="Masukkan nama...">
                    </div>
                    <div class="form-group">
                        <label>Jabatan</label>
                        <select v-model="form.jabatan">
                            <option value="Supir">Supir</option>
                            <option value="Kernet">Kernet</option>
                            <option value="Gudang">Petugas Gudang</option>
                        </select>
                    </div>
                    <div class="form-group">
                        <label>No. WhatsApp</label>
                        <input v-model="form.no_hp" type="text" placeholder="08xxxx">
                    </div>
                </div>
                <div class="modal-footer">
                    <button @click="submitPersonel" :disabled="loading" class="btn-save">
                        {{ loading ? 'Menyimpan...' : 'Simpan Personel' }}
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const personels = ref([]);
const showModal = ref(false);
const loading = ref(false);

const form = reactive({
    nama: '',
    jabatan: 'Supir',
    no_hp: ''
});

const fetchData = async () => {
    const res = await axios.get('/api/personel');
    personels.value = res.data;
};

const submitPersonel = async () => {
    if(!form.nama || !form.no_hp) return alert("Lengkapi form!");
    loading.value = true;
    try {
        await axios.post('/api/personel-store', form);
        alert("Berhasil disimpan!");
        showModal.value = false;
        fetchData(); // Refresh list
        // Reset Form
        form.nama = ''; form.no_hp = '';
    } catch (e) {
        alert("Gagal simpan.");
    } finally {
        loading.value = false;
    }
};

onMounted(fetchData);
</script>

<style scoped>
.personel-container { padding: 20px; }
.page-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 30px; }
.title-block h1 { font-size: 24px; font-weight: 800; color: #1e3a8a; margin: 0; }
.title-block p { color: #64748b; font-size: 14px; }

.btn-add { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 10px; cursor: pointer; display: flex; align-items: center; gap: 8px; font-weight: 700; }
.card { background: white; border-radius: 15px; border: 1px solid #e2e8f0; overflow: hidden; }

table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 15px 20px; background: #f8fafc; color: #64748b; font-size: 12px; text-transform: uppercase; }
td { padding: 15px 20px; border-bottom: 1px solid #f1f5f9; font-size: 14px; }

.badge-role { padding: 4px 10px; border-radius: 6px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
.badge-role.supir { background: #dcfce7; color: #15803d; }
.badge-role.kernet { background: #fef3c7; color: #d97706; }
.badge-role.gudang { background: #e0efff; color: #2563eb; }

/* Modal */
.modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
.modal-box { background: white; width: 400px; border-radius: 15px; overflow: hidden; }
.modal-header { padding: 20px; border-bottom: 1px solid #f1f5f9; display: flex; justify-content: space-between; }
.modal-body { padding: 20px; }
.form-group { margin-bottom: 15px; }
.form-group label { display: block; font-size: 13px; font-weight: 700; margin-bottom: 5px; color: #475569; }
.form-group input, select { width: 100%; padding: 10px; border: 1px solid #e2e8f0; border-radius: 8px; box-sizing: border-box; }
.btn-save { width: 100%; padding: 12px; background: #2563eb; color: white; border: none; border-radius: 10px; font-weight: 700; cursor: pointer; }
</style>