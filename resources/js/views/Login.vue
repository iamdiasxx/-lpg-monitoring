<template>
    <div class="split-container">
        <!-- SISI KIRI: INFORMASI KONTAK & BRANDING -->
        <div class="info-side">
            <div class="overlay"></div>
            <div class="content">
                <div class="brand">
                    <div class="logo-circle">
                        <svg xmlns="http://www.w3.org/2000/svg" width="35" height="35" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M13 2 3 14h9l-1 8 10-12h-9l1-8z"/></svg>
                    </div>
                    <h2>LPG-MONITORING</h2>
                </div>

                <div class="contact-details">
                    <h3>Hubungi Kami</h3>
                    <p>Punya kendala dengan stok LPG atau akses akun? Silahkan hubungi pusat bantuan kami:</p>
                    
                    <div class="contact-item">
                        <div class="icon">📍</div>
                        <div class="text">Jl. Diponegoro No.209, Dusun Lidah, Gambiran, Kec. Gambiran, Kabupaten Banyuwangi</div>
                    </div>
                    
                    <div class="contact-item">
                        <div class="icon">📞</div>
                        <div class="text">+62 813 5822 9027 (WhatsApp)</div>
                    </div>

                    <div class="contact-item">
                        <div class="icon">✉️</div>
                        <div class="text">ptwinatapk01@gmail.com</div>
                    </div>

                    <div class="contact-item">
                        <div class="icon">⏰</div>
                        <div class="text">Senin - sabtu | 08:00 - 17:00</div>
                    </div>
                </div>

                <div class="footer-info">
                    <p>PT. Winata Pramana Komala</p>
                </div>
            </div>
        </div>

        <!-- SISI KANAN: FORM LOGIN -->
        <div class="login-side">
            <div class="login-box">
                <div class="mobile-logo">
                    <h2>LPG-MONITORING</h2>
                </div>
                
                <h2>Selamat Datang</h2>
                <p class="subtitle">Silahkan masukkan email dan kata sandi Anda</p>

                <div v-if="error" class="alert-error">{{ error }}</div>

                <div class="form-group">
                    <label>Email Dashboard</label>
                    <input v-model="form.email" type="email" placeholder="contoh: admin@lpg.com">
                </div>

                <div class="form-group">
                    <label>Kata Sandi</label>
                    <input v-model="form.password" type="password" placeholder="••••••••">
                </div>

                <button @click="handleLogin" :disabled="loading" class="btn-primary">
                    {{ loading ? 'Memproses...' : 'Masuk Sekarang' }}
                </button>

                <p class="help-text">Lupa kata sandi? Hubungi admin melalui kontak di sebelah kiri.</p>
            </div>
        </div>
    </div>
</template>

<script setup>
import { reactive, ref } from 'vue';
import axios from 'axios';
import { useRouter } from 'vue-router';

const router = useRouter();
const loading = ref(false);
const error = ref('');
const form = reactive({ email: '', password: '' });

const handleLogin = async () => {
    if (!form.email || !form.password) {
        error.value = 'Email dan password tidak boleh kosong';
        return;
    }
    loading.value = true;
    error.value = '';
    try {
        const res = await axios.post('/api/login', form);
        sessionStorage.setItem('user', JSON.stringify(res.data.user));
        const role = res.data.user.role;
        if (role === 'admin') router.push('/admin');
        else if (role === 'operator') router.push('/operator');
        else router.push('/customer');
    } catch (e) {
        error.value = 'Email atau password salah!';
    } finally {
        loading.value = false;
    }
};
</script>

<style scoped>
.split-container {
    display: flex;
    min-height: 100vh;
    font-family: 'Inter', sans-serif;
}

/* SISI KIRI */
.info-side {
    flex: 1.2;
    background: #1e40af; /* Biru Tua */
    background-image: url('https://images.unsplash.com/photo-1581091226825-a6a2a5aee158?auto=format&fit=crop&q=80'); /* Gambar Industrial */
    background-size: cover;
    background-position: center;
    position: relative;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    padding: 60px;
}

.overlay {
    position: absolute;
    top: 0; left: 0; right: 0; bottom: 0;
    background: linear-gradient(135deg, rgba(30, 64, 175, 0.95), rgba(30, 58, 138, 0.8));
}

.content {
    position: relative;
    z-index: 1;
    max-width: 500px;
}

.brand {
    display: flex;
    align-items: center;
    gap: 15px;
    margin-bottom: 50px;
}

.logo-circle {
    background: white;
    color: #1e40af;
    width: 60px;
    height: 60px;
    border-radius: 15px;
    display: flex;
    align-items: center;
    justify-content: center;
}

.brand h2 {
    font-size: 24px;
    font-weight: 800;
    letter-spacing: 1px;
}

.contact-details h3 {
    font-size: 28px;
    margin-bottom: 15px;
}

.contact-details p {
    opacity: 0.8;
    margin-bottom: 30px;
    line-height: 1.6;
}

.contact-item {
    display: flex;
    align-items: flex-start;
    gap: 15px;
    margin-bottom: 20px;
}

.contact-item .icon {
    font-size: 20px;
}

.contact-item .text {
    font-size: 15px;
    opacity: 0.9;
}

.footer-info {
    margin-top: 60px;
    font-size: 13px;
    opacity: 0.6;
}

/* SISI KANAN */
.login-side {
    flex: 1;
    background: #ffffff;
    display: flex;
    align-items: center;
    justify-content: center;
    padding: 40px;
}

.login-box {
    width: 100%;
    max-width: 400px;
}

.mobile-logo {
    display: none; /* Hanya muncul di HP */
}

.login-side h2 {
    font-size: 32px;
    font-weight: 700;
    color: #1e293b;
    margin-bottom: 10px;
}

.subtitle {
    color: #64748b;
    margin-bottom: 40px;
}

.form-group {
    margin-bottom: 20px;
}

.form-group label {
    display: block;
    font-size: 14px;
    font-weight: 600;
    color: #334155;
    margin-bottom: 8px;
}

.form-group input {
    width: 100%;
    padding: 14px 16px;
    border: 1.5px solid #e2e8f0;
    border-radius: 12px;
    font-size: 16px;
    transition: all 0.3s;
    box-sizing: border-box;
}

.form-group input:focus {
    outline: none;
    border-color: #2563eb;
    box-shadow: 0 0 0 4px rgba(37, 99, 235, 0.1);
}

.btn-primary {
    width: 100%;
    background: #2563eb;
    color: white;
    border: none;
    padding: 16px;
    border-radius: 12px;
    font-size: 16px;
    font-weight: 700;
    cursor: pointer;
    margin-top: 10px;
    transition: background 0.3s, transform 0.2s;
}

.btn-primary:hover {
    background: #1d4ed8;
}

.btn-primary:active {
    transform: scale(0.98);
}

.alert-error {
    background: #fff1f2;
    color: #e11d48;
    padding: 12px;
    border-radius: 8px;
    font-size: 14px;
    margin-bottom: 20px;
    border: 1px solid #ffe4e6;
}

.help-text {
    margin-top: 30px;
    text-align: center;
    font-size: 13px;
    color: #94a3b8;
    line-height: 1.5;
}

/* RESPONSIVE UNTUK HP */
@media (max-width: 900px) {
    .info-side {
        display: none; /* Sembunyikan sisi kiri di HP */
    }
    .mobile-logo {
        display: block;
        text-align: center;
        margin-bottom: 30px;
        color: #2563eb;
        font-weight: 800;
    }
    .login-side {
        padding: 20px;
    }
}
</style>