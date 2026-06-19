<template>
    <div class="default-page">
        <div class="card">
            <div class="icon">🛠️</div>
            <h1>Fitur Sedang Dikembangkan</h1>
            <p>Halaman <strong>{{ $route.path }}</strong> sedang dalam tahap integrasi sesuai Use Case Diagram.</p>
            <button @click="$router.back()">Kembali</button>
        </div>
    </div>
</template>

<style scoped>
.default-page { display: flex; justify-content: center; align-items: center; min-height: 60vh; text-align: center; }
.card { background: white; padding: 50px; border-radius: 20px; box-shadow: 0 10px 25px rgba(0,0,0,0.05); max-width: 400px; }
.icon { font-size: 50px; margin-bottom: 20px; }
h1 { font-size: 20px; color: #1e293b; margin-bottom: 10px; }
p { color: #64748b; font-size: 14px; margin-bottom: 25px; }
button { background: #2563eb; color: white; border: none; padding: 10px 20px; border-radius: 8px; cursor: pointer; }
</style>