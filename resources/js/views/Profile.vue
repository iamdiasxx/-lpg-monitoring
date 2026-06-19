<template>
    <div class="profile-container">
        <div class="page-header">
            <h1>Pengaturan Akun</h1>
            <p>Kelola informasi profil dan keamanan kata sandi Anda.</p>
        </div>

        <div class="profile-grid">
            <!-- INFO USER -->
            <div class="card info-card">
                <div class="user-avatar-big">{{ user.name.charAt(0) }}</div>
                <h2 class="u-name">{{ user.name }}</h2>
                <span class="u-role">{{ user.role.toUpperCase() }}</span>
                <hr>
                <div class="u-meta">
                    <label>Email Terdaftar</label>
                    <p>{{ user.email }}</p>
                </div>
            </div>

            <!-- FORM GANTI PASSWORD -->
            <div class="card security-card">
                <h3>🔒 Keamanan Kata Sandi</h3>
                <form @submit.prevent="handleUpdatePassword">
                    <div class="f-group">
                        <label>Password Saat Ini</label>
                        <input type="password" v-model="form.current_password" required placeholder="••••••••">
                    </div>
                    <div class="f-group">
                        <label>Password Baru</label>
                        <input type="password" v-model="form.new_password" required placeholder="Minimal 6 karakter">
                    </div>
                    <div class="f-group">
                        <label>Konfirmasi Password Baru</label>
                        <input type="password" v-model="form.new_password_confirmation" required placeholder="Ulangi password baru">
                    </div>
                    <button type="submit" :disabled="loading" class="btn-save">
                        {{ loading ? 'Menyimpan...' : 'Perbarui Kata Sandi' }}
                    </button>
                </form>
            </div>
        </div>
    </div>
</template>

<script setup>
import { ref, reactive } from 'vue';
import axios from 'axios';

const user = JSON.parse(sessionStorage.getItem('user'));
const loading = ref(false);
const form = reactive({
    user_id: user.id,
    current_password: '',
    new_password: '',
    new_password_confirmation: ''
});

const handleUpdatePassword = async () => {
    loading.value = true;
    try {
        const res = await axios.post('/api/user/update-password', form);
        alert(res.data.message);
        // Reset form
        form.current_password = '';
        form.new_password = '';
        form.new_password_confirmation = '';
    } catch (e) {
        alert(e.response?.data?.message || "Gagal memperbarui password.");
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.profile-container { padding: 30px; background: #f8fafc; min-height: 100vh; }
.profile-grid { display: grid; grid-template-columns: 350px 1fr; gap: 30px; margin-top: 20px; }

.card { background: white; border-radius: 20px; border: 1px solid #e2e8f0; padding: 30px; }

.info-card { text-align: center; }
.user-avatar-big { width: 80px; height: 80px; background: #2563eb; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; font-weight: 900; margin: 0 auto 20px; }
.u-name { font-size: 20px; font-weight: 800; color: #1e3a8a; margin: 0; }
.u-role { font-size: 11px; font-weight: 800; color: #64748b; background: #f1f5f9; padding: 4px 12px; border-radius: 50px; margin-top: 10px; display: inline-block; }

.u-meta { text-align: left; margin-top: 20px; }
.u-meta label { font-size: 11px; font-weight: 700; color: #94a3b8; text-transform: uppercase; }
.u-meta p { font-weight: 600; color: #334155; margin-top: 4px; }

.f-group { margin-bottom: 20px; }
.f-group label { display: block; font-size: 13px; font-weight: 700; color: #475569; margin-bottom: 8px; }
.f-group input { width: 100%; padding: 12px; border-radius: 10px; border: 1.5px solid #d1e9ff; outline: none; }
.btn-save { width: 100%; padding: 15px; background: #1e293b; color: white; border: none; border-radius: 12px; font-weight: 800; cursor: pointer; transition: 0.3s; }
.btn-save:hover { background: #2563eb; }
</style>