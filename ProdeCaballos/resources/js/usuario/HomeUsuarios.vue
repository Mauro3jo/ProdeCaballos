<template>
  <div class="prodes-backdrop">
    <div class="prodes-content">
      <h1 class="prodes-title">Prodes disponibles</h1>
      <div v-if="loading" class="prodes-loading">Cargando prodes...</div>
      <div v-else-if="error" class="prodes-error">{{ error }}</div>
      <div v-else class="prodes-grid">
        <div
          v-for="prode in prodesVigentes"
          :key="prode.id"
          class="prode-card"
          @click="abrirProde(prode.id)"
        >
          <!-- Imagen fija igual que en admin -->
          <img :src="logoUrl" alt="Logo del prode" class="prode-img" />
          <div class="prode-card-content">
            <div class="prode-nombre">{{ prode.nombre }}</div>
            <div class="prode-precio">
              Precio: ${{ Math.round(prode.precio).toLocaleString('es-AR') }}
            </div>
            <div class="prode-fecha">Finaliza: {{ formatFecha(prode.fechafin) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import './HomeUsuarios.css';

// --- Manejo de logo fijo desde public (idéntico al admin) ---
const logoUrl = import.meta.env.VITE_IMAGES_PUBLIC_PATH
  ? `${import.meta.env.VITE_IMAGES_PUBLIC_PATH.replace(/\/$/, '')}/Logo.jpg`
  : '/Logo.jpg';
// ------------------------------------------------------------

function ahoraARG() {
  return new Date(new Date().toLocaleString("en-US", { timeZone: "America/Argentina/Buenos_Aires" }));
}

const prodes = ref([]);
const loading = ref(true);
const error = ref('');

const router = useRouter();

const prodesVigentes = computed(() => {
  const ahora = ahoraARG();
  return prodes.value.filter(p => {
    if (!p.fechafin) return true;
    const fin = new Date(p.fechafin);
    return fin > ahora;
  });
});

const formatFecha = (fecha) => {
  if (!fecha) return '';
  const d = new Date(fecha);
  return d.toLocaleString("es-AR", { timeZone: "America/Argentina/Buenos_Aires" });
};

function abrirProde(id) {
  router.push(`/prode/${id}`);
}

onMounted(async () => {
  try {
    loading.value = true;
    error.value = '';
    const res = await fetch('/formularios');
    if (!res.ok) throw new Error('No se pudo cargar la lista de prodes.');
    prodes.value = await res.json();
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
});
</script>
