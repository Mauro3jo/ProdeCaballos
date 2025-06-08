import { createApp } from 'vue';
import AppUsuarios from './usuario/AppUsuarios.vue';
import router from '../../src/router';

createApp(AppUsuarios).use(router).mount('#app');
