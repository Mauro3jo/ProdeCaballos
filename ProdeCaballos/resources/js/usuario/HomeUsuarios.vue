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
          @click="$emit('abrir-prode', prode.id)"
        >
          <div class="prode-nombre">{{ prode.nombre }}</div>
          <div class="prode-precio">Precio: ${{ prode.precio }}</div>
          <div class="prode-fecha">Finaliza: {{ formatFecha(prode.fechafin) }}</div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import './HomeUsuarios.css';

const prodes = ref([]);
const loading = ref(true);
const error = ref('');

const formatFecha = (fecha) => {
  if (!fecha) return '';
  const d = new Date(fecha);
  return d.toLocaleString();
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
