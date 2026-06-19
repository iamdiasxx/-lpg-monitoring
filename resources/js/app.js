import './bootstrap';
import { createApp } from 'vue';
import App from './App.vue';
import router from './router'; // Import router yang tadi dibuat

import { registerSW } from 'virtual:pwa-register';
registerSW();

createApp(App)
    .use(router) // Gunakan router
    .mount('#app');