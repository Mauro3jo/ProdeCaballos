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
          <!-- Imagen del prode -->
          <img
            v-if="prode.foto_url"
            :src="prode.foto_url"
            alt="Imagen del prode"
            class="prode-img"
          />
          <div class="prode-card-content">
            <div class="prode-nombre">{{ prode.nombre }}</div>
            <div class="prode-precio">Precio: ${{ prode.precio }}</div>
            <div class="prode-fecha">Finaliza: {{ formatFecha(prode.fechafin) }}</div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router'; // Importa el router
import './HomeUsuarios.css';

// Obtener hora Argentina en ISO para comparar (siempre con zona horaria)
function ahoraARG() {
  return new Date(new Date().toLocaleString("en-US", { timeZone: "America/Argentina/Buenos_Aires" }));
}

const prodes = ref([]);
const loading = ref(true);
const error = ref('');

const router = useRouter(); // Instancia del router

const prodesVigentes = computed(() => {
  const ahora = ahoraARG();
  // solo los que NO vencieron
  return prodes.value.filter(p => {
    if (!p.fechafin) return true;
    // Parsear la fecha como UTC o local y comparar con hora Argentina
    const fin = new Date(p.fechafin);
    return fin > ahora;
  });
});

const formatFecha = (fecha) => {
  if (!fecha) return '';
  const d = new Date(fecha);
  // Mostrar en formato argentino, hora local Buenos Aires
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
