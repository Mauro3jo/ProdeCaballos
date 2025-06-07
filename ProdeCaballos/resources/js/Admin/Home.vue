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
        <img
          v-if="prode.foto_url"
          :src="prode.foto_url"
          alt="Imagen del prode"
          class="prode-img"
          @error="onImgError($event, prode.foto)"
        />
        <div class="prode-card-content">
          <div class="prode-nombre">{{ prode.nombre }}</div>
          <div class="prode-precio">
            Precio: ${{ Number(prode.precio).toLocaleString('es-AR', { minimumFractionDigits: 2 }) }}
          </div>
          <div class="prode-fecha">Finaliza: {{ formatFecha(prode.fechafin) }}</div>
        </div>
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
import ModalFormulariosUsuarios from './ModalFormulariosUsuarios.vue';
import './Home.css';

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
  const d = new Date(fecha);
  return d.toLocaleString('es-AR', {
    day: 'numeric', month: 'numeric', year: 'numeric',
    hour: '2-digit', minute: '2-digit', second: '2-digit'
  });
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
    let data = await res.json();
    prodes.value = data.prodes ?? data;

    // -------- LOG para debug ----------
    console.log("Prodes cargados:");
    prodes.value.forEach((prode, i) => {
      console.log(`Prode #${i}:`, prode);
      console.log(`   foto_url:`, prode.foto_url);
      console.log(`   foto:`, prode.foto);
    });
    // -----------------------------------

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

// Para debug de error en imagen: podrías mostrar una imagen de fallback o loggear
const onImgError = (event, foto) => {
  console.error('No se pudo cargar la imagen:', foto, event.target.src);
  event.target.style.display = 'none'; // oculta la imagen rota
};

onMounted(() => {
  cargarProdes();
});
</script>
