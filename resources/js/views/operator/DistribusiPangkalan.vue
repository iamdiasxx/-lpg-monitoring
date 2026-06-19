<template>
    <div class="distribusi-page">
        <h2 class="font-bold text-xl mb-6">Pengiriman ke Pangkalan</h2>

        <div v-if="shipments.length === 0" class="empty-state">
            <p>Tidak ada muatan yang siap dikirim. Pastikan pengambilan SPBE sudah selesai.</p>
        </div>

        <div v-for="s in shipments" :key="s.id" class="shipment-card">
            <div class="card-info">
                <span class="batch-id">BATCH #{{ s.id }}</span>
                <h3>Siap Distribusi: {{ s.total_rencana_isi }} Tabung</h3>
                <p>Sumber: {{ s.nama_spbe }}</p>
            </div>
            
            <div class="form-dispatch">
                <div class="input-grid">
                    <select v-model="s.selectedTruk" class="input-select">
                        <option value="">-- Pilih Truk --</option>
                        <option v-for="t in resources.truk" :key="t.id_truk" :value="t.id_truk">{{ t.plat_no }}</option>
                    </select>
                    <select v-model="s.selectedSupir" class="input-select">
                        <option value="">-- Pilih Supir --</option>
                        <option v-for="k in resources.supir" :key="k.id_karyawan" :value="k.id_karyawan">{{ k.nama }}</option>
                    </select>
                </div>
                <button @click="dispatch(s)" class="btn-go">Konfirmasi Keberangkatan Truk</button>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive } from 'vue';
import axios from 'axios';

const shipments = ref([]);
const resources = reactive({ truk: [], supir: [] });

const fetchData = async () => {
    // Ambil alokasi yang statusnya sudah 'SPBE' (Artinya tabung sudah ada di gudang)
    const res = await axios.get('/api/admin-monitoring');
    shipments.value = res.data.filter(i => i.status === 'SPBE');

    const resFleet = await axios.get('/api/fleet-resources');
    resources.truk = resFleet.data.truk;
    resources.supir = resFleet.data.supir;
};

const dispatch = async (s) => {
    if(!s.selectedTruk || !s.selectedSupir) return alert("Pilih armada dan supir!");
    
    try {
        await axios.post('/api/start-distribution', {
            header_id: s.id,
            id_truk: s.selectedTruk,
            id_supir: s.selectedSupir
        });
        alert("Status: Distribusi. Manager & Pangkalan telah diberi notifikasi.");
        fetchData();
    } catch (e) {
        alert(e.response.data.message);
    }
};

onMounted(fetchData);
</script>

<style scoped>
.shipment-card { background: white; padding: 25px; border-radius: 15px; border: 1px solid #e2e8f0; margin-bottom: 20px; display: flex; justify-content: space-between; align-items: center; }
.batch-id { font-size: 10px; font-weight: 800; color: #2563eb; background: #eff6ff; padding: 2px 8px; border-radius: 4px; }
.input-grid { display: grid; grid-template-columns: 1fr 1fr; gap: 10px; margin-bottom: 10px; }
.input-select { padding: 10px; border: 1px solid #d1e9ff; border-radius: 8px; }
.btn-go { background: #10b981; color: white; border: none; padding: 12px; border-radius: 8px; font-weight: 700; cursor: pointer; width: 100%; }
</style>