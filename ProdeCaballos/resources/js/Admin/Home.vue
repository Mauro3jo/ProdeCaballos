<template>
  <div class="admin-home">
    <h1>Bienvenido, {{ userName }}</h1>
    <p>Este es tu panel de administración.</p>

    <div class="actions">
      <button class="btn" @click="showCarreras = true">Gestionar Carreras</button>
      <button class="btn" @click="showCaballos = true">Gestionar Caballos</button>
      <button class="btn" @click="showFormularios = true">Gestionar Formularios</button>
    </div>

    <h2 style="margin-top: 40px;">Prodes Disponibles</h2>
    <div v-if="loadingProdes" class="loading">Cargando prodes...</div>
    <div v-else-if="errorProdes" class="error">{{ errorProdes }}</div>
    <div v-else class="prodes-grid">
      <div
        v-for="prode in prodes"
        :key="prode.id"
        class="prode-card"
        @click="abrirModalFormularios(prode.id, prode.nombre)"
        style="cursor:pointer"
        title="Ver formularios"
      >
        <div class="prode-nombre">{{ prode.nombre }}</div>
        <div class="prode-precio">Precio: ${{ prode.precio }}</div>
        <div class="prode-fecha">Finaliza: {{ formatFecha(prode.fechafin) }}</div>
      </div>
    </div>

    <!-- Modales -->
    <GestionCarrerasModal v-if="showCarreras" @close="showCarreras = false" />
    <GestionCaballosModal v-if="showCaballos" @close="showCaballos = false" />
    <GestionFormulariosModal v-if="showFormularios" @close="showFormularios = false" />

    <ModalFormulariosUsuarios
      v-if="showModalFormulariosUsuarios"
      :prodeId="prodeIdSeleccionado"
      :prodeNombre="prodeNombreSeleccionado"
      @close="showModalFormulariosUsuarios = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';
import GestionCarrerasModal from './GestionCarrerasModal.vue';
import GestionCaballosModal from './GestionCaballosModal.vue';
import GestionFormulariosModal from './GestionFormulariosModal.vue';
import ModalFormulariosUsuarios from './ModalFormulariosUsuarios.vue'; // Modal nuevo

const userName = ref('Administrador');
const showCarreras = ref(false);
const showCaballos = ref(false);
const showFormularios = ref(false);

const prodes = ref([]);
const loadingProdes = ref(false);
const errorProdes = ref('');

const showModalFormulariosUsuarios = ref(false);
const prodeIdSeleccionado = ref(null);
const prodeNombreSeleccionado = ref('');

const formatFecha = (fecha) => {
  if (!fecha) return '';
  return new Date(fecha).toLocaleString();
};

const cargarProdes = async () => {
  loadingProdes.value = true;
  errorProdes.value = '';
  try {
    const res = await fetch('/admin/prodes/listar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    });
    if (!res.ok) throw new Error('No se pudo cargar los prodes');
    prodes.value = await res.json();
  } catch (e) {
    errorProdes.value = e.message;
  } finally {
    loadingProdes.value = false;
  }
};

const abrirModalFormularios = (id, nombre) => {
  prodeIdSeleccionado.value = id;
  prodeNombreSeleccionado.value = nombre;
  showModalFormulariosUsuarios.value = true;
};

onMounted(() => {
  cargarProdes();
});
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

.prodes-grid {
  margin-top: 20px;
  display: grid;
  grid-template-columns: repeat(auto-fill,minmax(220px,1fr));
  gap: 20px;
}

.prode-card {
  background: white;
  border-radius: 1em;
  padding: 1.5em;
  box-shadow: 0 3px 15px rgba(0,0,0,0.1);
  user-select:none;
  transition: transform 0.15s ease;
}
.prode-card:hover {
  transform: translateY(-4px);
  box-shadow: 0 8px 20px rgba(0,0,0,0.15);
}

.prode-nombre {
  font-weight: 700;
  font-size: 1.2em;
  margin-bottom: 0.3em;
}
.prode-precio,
.prode-fecha {
  font-weight: 500;
  color: #555;
}
.loading, .error {
  font-size: 1.2em;
  margin-top: 1.5em;
  color: #b00020;
  text-align: center;
}
</style>
