import { createRouter, createWebHistory } from 'vue-router';
import HomeUsuarios from '../resources/js/usuario/HomeUsuarios.vue';
import FormularioProde from '../resources/js/usuario/FormularioProde.vue';

const routes = [
  { path: '/', name: 'Home', component: HomeUsuarios },
  { path: '/prode/:id', name: 'FormularioProde', component: FormularioProde, props: true },
];

const router = createRouter({
  history: createWebHistory(),
  routes,
});

export default router;
