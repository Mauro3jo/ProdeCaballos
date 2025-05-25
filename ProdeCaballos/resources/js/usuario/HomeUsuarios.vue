<template>
  <div class="prodes-backdrop">
    <div class="prodes-content">
      <h1 class="prodes-title">Prodes disponibles</h1>
      <div v-if="loading" class="prodes-loading">Cargando prodes...</div>
      <div v-else-if="error" class="prodes-error">{{ error }}</div>
      <div v-else class="prodes-grid">
        <div
          v-for="prode in prodes"
          :key="prode.id"
          class="prode-card"
          @click="abrirModal(prode.id)"
        >
          <div class="prode-nombre">{{ prode.nombre }}</div>
          <div class="prode-precio">Precio: ${{ prode.precio }}</div> 
          <div class="prode-fecha">Finaliza: {{ formatFecha(prode.fechafin) }}</div>
        </div>
      </div>

      <!-- Modal FormularioProde -->
      <FormularioProde 
        v-if="modalVisible" 
        :id="modalProdeId" 
        @cerrar="modalVisible = false" 
      />
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import FormularioProde from './FormularioProde.vue';

const prodes = ref([]);
const loading = ref(true);
const error = ref('');

const modalVisible = ref(false);
const modalProdeId = ref(null);

const formatFecha = (fecha) => {
  if (!fecha) return '';
  const d = new Date(fecha);
  return d.toLocaleString();
};

const abrirModal = (id) => {
  modalProdeId.value = id;
  modalVisible.value = true;
};

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

<style scoped>
@import url('https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap');

.prodes-backdrop {
  min-height: 100vh;
  width: 100vw;
  background: #f5f6fa;
  display: flex;
  align-items: center;
  justify-content: center;
  font-family: 'Roboto', sans-serif;
}
.prodes-content {
  background: #fff;
  padding: 2em 2.5em;
  border-radius: 1em;
  box-shadow: 0 2px 32px 0 rgba(0,0,0,0.07), 0 0.5px 1.5px 0 rgba(0,0,0,0.03);
  min-width: 720px;
  max-width: 95vw;
}
.prodes-title {
  font-size: 2.2em;
  font-weight: bold;
  margin-bottom: 1.4em;
  text-align: center;
  color: #1a2237;
  letter-spacing: 0.04em;
}
.prodes-loading {
  text-align: center;
  font-size: 1.2em;
  padding: 2em 0;
}
.prodes-error {
  color: #ef4444;
  text-align: center;
  padding: 1em 0;
  font-size: 1.1em;
}
.prodes-grid {
  display: grid;
  grid-template-columns: repeat(auto-fit, minmax(220px, 1fr));
  gap: 1.6em;
  justify-items: center;
}
.prode-card {
  background: #f6f8fa;
  border-radius: 1em;
  box-shadow: 0 1px 7px 0 rgba(0,0,0,0.07), 0 0.5px 1.5px 0 rgba(0,0,0,0.02);
  border: 1px solid #e5e7eb;
  padding: 2em 1em;
  min-width: 220px;
  max-width: 250px;
  display: flex;
  flex-direction: column;
  align-items: center;
  cursor: pointer;
  transition: box-shadow 0.18s, transform 0.18s;
}
.prode-card:hover {
  box-shadow: 0 4px 20px 2px rgba(43,115,238,0.10), 0 2px 6px 0 rgba(30,64,175,0.03);
  transform: translateY(-5px) scale(1.045);
  background: #ecf3fe;
}
.prode-nombre {
  font-size: 1.13em;
  font-weight: bold;
  color: #222;
  margin-bottom: 0.8em;
  text-align: center;
}
.prode-precio {
  color: #2b6cb0;
  font-weight: 500;
  font-size: 1.06em;
  margin-bottom: 0.4em;
}
.prode-fecha {
  color: #555;
  font-size: 0.98em;
  text-align: center;
}
@media (max-width: 900px) {
  .prodes-content {
    min-width: unset;
    padding: 1.2em;
  }
  .prodes-title {
    font-size: 1.3em;
  }
}
@media (max-width: 540px) {
  .prodes-content {
    border-radius: 0.3em;
    padding: 0.5em 0.2em;
  }
  .prodes-grid {
    gap: 0.7em;
  }
  .prode-card {
    min-width: 98vw;
    max-width: 99vw;
    padding: 1.2em 0.5em;
    border-radius: 0.6em;
  }
}
</style>
