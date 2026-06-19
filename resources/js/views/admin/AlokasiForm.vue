<template>
    <div class="alokasi-container">
        <!-- STEP 1: Rencana Pengambilan SPBE -->
        <div class="card header-section bg-blue-50">
            <h3 class="section-title">Step 1: Rencana Pengambilan SPBE</h3>
            <div class="row">
                <div class="form-group">
                    <label>Tanggal Distribusi</label>
                    <input type="date" v-model="form.tanggal" class="input-control">
                </div>
                <div class="form-group">
                    <label>Pilih Sumber SPBE</label>
                    <select v-model="form.id_spbe" class="input-control">
                        <option value="">-- Pilih Sumber --</option>
                        <option value="titipan" style="font-weight: bold; color: #2563eb;">🔄 AMBIL DARI STOK TITIPAN GUDANG</option>
                        <option v-for="s in resources.spbe" :key="s.id_spbe" :value="s.id_spbe">{{ s.nama_spbe }}</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Total Pengambilan (Tabung)</label>
                    <input type="number" v-model.number="form.total_rencana" 
                        class="input-control font-bold" 
                        :readonly="form.id_spbe === 'titipan'">
                        
                    <!-- Teks bantuan dinamis -->
                    <small v-if="form.id_spbe !== 'titipan'" class="text-info">
                        Estimasi: {{ jumlahTruk }} Truk (Kelipatan 560)
                    </small>
                    <small v-else class="text-blue">
                        Mendistribusikan {{ form.total_rencana }} tabung dari saldo titipan.
                    </small>
                </div>
            </div>
        </div>

        <!-- STEP 2: Pembagian ke Pangkalan -->
        <div class="card table-section">
            <div class="flex-between">
                <h3 class="section-title">Step 2: Alokasi ke Pangkalan</h3>
                <input type="text" v-model="searchQuery" placeholder="Cari pangkalan..." class="search-mini">
            </div>
            
            <!-- Alert Selisih -->
            <div v-if="selisih !== 0" class="alert-warning">
                ⚠️ Selisih: <strong>{{ selisih }}</strong> tabung belum dialokasikan. 
                (Rencana: {{ form.total_rencana }} | Terisi: {{ totalTabung }})
            </div>
            <div v-else-if="totalTabung > 0" class="alert-success">
                ✅ Kuota Pas! Siap diterbitkan.
            </div>

            <table>
                <thead>
                    <tr>
                        <th>No</th>
                        <th>Nama Pangkalan</th>
                        <th>Registrasi</th>
                        <th width="200">Jumlah Alokasi (Isi)</th>
                    </tr>
                </thead>
                <tbody>
                    <tr v-for="(p, index) in filteredPangkalan" :key="p.id_pangkalan" 
                        :class="{ 'row-muted': form.id_spbe === 'titipan' && p.stok_titipan === 0 }">
                        <td>{{ index + 1 }}</td>
                        <td>
                            <div class="font-bold">{{ p.nama_pangkalan }}</div>
                            <div v-if="p.stok_titipan > 0" class="badge-titipan">
                                📦 Titipan: {{ p.stok_titipan }} Unit
                            </div>
                        </td>
                        <td>
                            <input type="number" v-model.number="p.jumlah_input" 
                                class="input-qty" 
                                @input="validateInput(p)"
                                :disabled="form.id_spbe === 'titipan' && p.stok_titipan === 0">
                                <!-- Input mati jika tidak punya titipan saat mode titipan dipilih -->
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        <!-- Sticky Footer -->
        <div class="action-bar">
            <div class="summary">
                <div class="summary-item">
                    <span class="label">Rencana SPBE</span>
                    <span class="value">{{ form.total_rencana }}</span>
                </div>
                <div class="summary-item">
                    <span class="label">Telah Dialokasikan</span>
                    <span class="value text-orange">{{ totalTabung }}</span>
                </div>
            </div>
            <button @click="submitAlokasi" :disabled="selisih !== 0 || loading" class="btn-lock">
                Terbitkan Alokasi
            </button>
        </div>
    </div>
</template>

<script setup>
import { ref, onMounted, reactive, computed } from 'vue';
import axios from 'axios';
import { watch } from 'vue';

const loading = ref(false);
const searchQuery = ref('');
const resources = reactive({ spbe: [], pangkalan: [] });
const form = reactive({
    tanggal: new Date().toISOString().substr(0, 10),
    id_spbe: '',
    total_rencana: 0
});
const existingIds = ref([]);

// Fungsi cek pangkalan yang sudah dialokasi pada tanggal terpilih
const checkExisting = async () => {
    if (!form.tanggal) return;
    try {
        const res = await axios.get(`/api/alokasi-existing?tanggal=${form.tanggal}`);
        existingIds.value = res.data;
        
        // Reset inputan pangkalan yang sekarang sedang diisi jika ternyata dia terkunci di tanggal baru
        resources.pangkalan.forEach(p => {
            if (isAllocated(p.id_pangkalan)) {
                p.jumlah_input = 0;
            }
        });
    } catch (e) {
        console.error("Gagal cek data alokasi");
    }
};

// Helper untuk mengecek status di template
const isAllocated = (id) => {
    return existingIds.value.includes(id);
};

// Pantau perubahan tanggal, jika berubah langsung cek database
watch(() => form.id_spbe, (newVal) => {
    if (newVal === 'titipan') {
        // Otomatis isi total rencana dengan jumlah titipan yang ada
        form.total_rencana = totalTitipanTersedia.value;
        
        // Reset input pangkalan yang tidak punya titipan
        resources.pangkalan.forEach(p => {
            if (p.stok_titipan === 0) p.jumlah_input = 0;
        });
    } else {
        form.total_rencana = 0; // Reset jika pilih SPBE biasa
    }
});

const user = JSON.parse(sessionStorage.getItem('user'));

const jumlahTruk = computed(() => Math.ceil(form.total_rencana / 560));
const totalTabung = computed(() => resources.pangkalan.reduce((sum, p) => sum + (p.jumlah_input || 0), 0));
const selisih = computed(() => form.total_rencana - totalTabung.value);

const calculateTrucks = () => {
    if (form.total_rencana % 560 !== 0) {
        // Opsional: Beri peringatan jika bukan kelipatan 560
    }
};

const fetchResources = async () => {
    const res = await axios.get('/api/alokasi-resources');
    resources.spbe = res.data.spbe;
    resources.pangkalan = res.data.pangkalan.map(p => ({ ...p, jumlah_input: 0 }));
    await checkExisting(); // Cek untuk tanggal default (hari ini)
};

const filteredPangkalan = computed(() => {
    return resources.pangkalan.filter(p => 
        p.nama_pangkalan.toLowerCase().includes(searchQuery.value.toLowerCase())
    );
});

const totalPangkalanTerpilih = computed(() => {
    return resources.pangkalan.filter(p => p.jumlah_input > 0).length;
});

const submitAlokasi = async () => {
    // 1. Validasi dropdown sumber
    if (!form.id_spbe) return alert('Pilih sumber distribusi dulu (SPBE atau Titipan)!');

    // 2. Validasi Angka Kelipatan 560 (Hanya jika bukan titipan)
    if (form.id_spbe !== 'titipan') {
        if (form.total_rencana <= 0 || form.total_rencana % 560 !== 0) {
            return alert('Untuk pengambilan SPBE, total harus kelipatan 560 (1 DO)!');
        }
    } else {
        if (form.total_rencana <= 0) {
            return alert('Jumlah titipan tidak boleh kosong!');
        }
    }

    // 3. Validasi Selisih
    if (selisih.value !== 0) {
        return alert(`Ada selisih ${selisih.value} tabung. Pastikan input pangkalan pas dengan total rencana!`);
    }

    loading.value = true;
    try {
        const payload = {
            tanggal: form.tanggal,
            id_spbe: form.id_spbe,
            total_rencana: form.total_rencana,
            user_id: user.id, // Pastikan variabel 'user' sudah ada dari sessionStorage
            alokasi_data: resources.pangkalan
                .filter(p => p.jumlah_input > 0)
                .map(p => ({
                    id_pangkalan: p.id_pangkalan,
                    jumlah: p.jumlah_input
                }))
        };

        const response = await axios.post('/api/alokasi-store', payload);
        
        if (response.data.success) {
            alert('Berhasil! Alokasi telah diterbitkan.');
            location.reload();
        }
    } catch (error) {
        // MENAMPILKAN PESAN ASLI DARI SERVER
        const errorMsg = error.response?.data?.message || 'Terjadi kesalahan sistem.';
        alert('Gagal: ' + errorMsg);
        console.error(error);
    } finally {
        loading.value = false;
    }
};

const totalTitipanTersedia = computed(() => {
    return resources.pangkalan.reduce((sum, p) => sum + (p.stok_titipan || 0), 0);
})

const validateInput = (p) => {
    if (form.id_spbe === 'titipan') {
        // Gunakan Number() agar pasti membandingkan angka
        const inputVal = Number(p.jumlah_input);
        const maxVal = Number(p.stok_titipan);

        if (inputVal > maxVal) {
            alert(`Input melebihi titipan! Maksimal untuk ${p.nama_pangkalan} adalah ${maxVal}`);
            p.jumlah_input = maxVal; // Paksa balik ke angka maksimal
        }
    }
};


onMounted(fetchResources);
</script>

<style scoped>
.bg-blue-50 { background: #f0f7ff; border: 2px dashed #2563eb; }
.alert-warning { background: #fff7ed; color: #c2410c; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid #f97316; }
.alert-success { background: #f0fdf4; color: #15803d; padding: 15px; border-radius: 8px; margin-bottom: 15px; border-left: 5px solid #22c55e; }
.text-info { color: #2563eb; font-weight: bold; }
.font-bold { font-weight: bold; }

.alokasi-container { padding-bottom: 100px; }
.card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 2px 4px rgba(0,0,0,0.05); margin-bottom: 20px; }
.row { display: flex; gap: 20px; flex-wrap: wrap; }
.form-group { flex: 1; min-width: 200px; }
.form-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; }
.input-control { width: 100%; padding: 10px; border: 1px solid #d1e9ff; border-radius: 8px; box-sizing: border-box; }

table { width: 100%; border-collapse: collapse; }
th { text-align: left; padding: 15px; background: #f8fafc; color: #1e3a8a; font-size: 14px; }
td { padding: 15px; border-bottom: 1px solid #f1f5f9; vertical-align: middle; }
.p-name { font-weight: 700; color: #1e293b; }
.p-address { font-size: 12px; color: #64748b; }

.input-number-wrapper { display: flex; align-items: center; gap: 10px; }
.input-qty { width: 80px; padding: 8px; border: 2px solid #e2e8f0; border-radius: 6px; text-align: center; font-weight: bold; }

.action-bar {
    position: fixed; bottom: 0; left: 260px; right: 0;
    background: white; padding: 20px 40px;
    display: flex; justify-content: space-between; align-items: center;
    box-shadow: 0 -10px 15px rgba(0,0,0,0.05);
    border-top: 1px solid #d1e9ff;
    z-index: 100;
}
.summary { display: flex; gap: 40px; }
.summary-item { display: flex; flex-direction: column; }
.summary-item .label { font-size: 12px; color: #64748b; }
.summary-item .value { font-size: 20px; font-weight: 800; color: #2563eb; }

.btn-lock {
    background: #2563eb; color: white; border: none;
    padding: 15px 30px; border-radius: 10px; font-weight: 700;
    cursor: pointer; transition: 0.3s;
}
.btn-lock:hover { background: #1e40af; transform: translateY(-2px); }

.row-disabled {
    background-color: #f8fafc;
    opacity: 0.7;
}

.badge-locked {
    display: inline-block;
    margin-left: 10px;
    font-size: 10px;
    background: #fee2e2;
    color: #dc2626;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: bold;
    text-transform: uppercase;
}

.input-qty:disabled {
    background-color: #e2e8f0;
    cursor: not-allowed;
    border-color: #cbd5e1;
}

.row-muted {
    opacity: 0.4;
    background-color: #f1f5f9;
}

.badge-titipan {
    font-size: 11px;
    color: #2563eb;
    background: #eff6ff;
    padding: 2px 8px;
    border-radius: 4px;
    font-weight: 700;
    margin-top: 5px;
    display: inline-block;
}

.text-blue {
    color: #2563eb;
    font-weight: 600;
    font-size: 12px;
}
</style>