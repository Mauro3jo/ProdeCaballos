<template>
  <div class="admin-home">
    <h1>Bienvenido, {{ userName }}</h1>
    <p>Este es tu panel de administración.</p>

    <div class="actions">
      <button class="btn" @click="showCarreras = true">Gestionar Carreras</button>
      <button class="btn" @click="showCaballos = true">Gestionar Caballos</button>
      <button class="btn" @click="showFormularios = true">Gestionar Formularios</button>
    </div>

    <!-- Modales: solo se montan cuando hace falta -->
    <GestionCarrerasModal v-if="showCarreras" @close="showCarreras = false" />
    <GestionCaballosModal v-if="showCaballos" @close="showCaballos = false" />
    <GestionFormulariosModal v-if="showFormularios" @close="showFormularios = false" />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import GestionCarrerasModal from './GestionCarrerasModal.vue';
import GestionCaballosModal from './GestionCaballosModal.vue';
import GestionFormulariosModal from './GestionFormulariosModal.vue'; // Importado

const userName = ref('Administrador');
const showCarreras = ref(false);
const showCaballos = ref(false);
const showFormularios = ref(false); // Estado para modal
</script>

<style scoped>
.admin-home {
  padding: 40px;
  text-align: center;
  font-size: 20px;
  background-color: #f8f8f8;
  min-height: 100vh;
  color: #222;
}

.actions {
  margin-top: 32px;
  display: flex;
  justify-content: center;
  gap: 24px;
}

.btn {
  background: #3b82f6;
  color: white;
  border-radius: 0.5em;
  padding: 0.7em 1.8em;
  font-size: 1.1em;
  transition: background 0.2s;
  cursor: pointer;
}

.btn:hover {
  background: #2563eb;
}
</style>
