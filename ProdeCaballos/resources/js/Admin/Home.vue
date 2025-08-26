<template>
  <div class="admin-home">
    <h1>Bienvenido, {{ userName }}</h1>
    <p>Este es tu panel de administración.</p>

    <div class="actions">
      <!-- ✅ ahora redirige a las vistas -->
      <button class="btn" @click="irAGestionCaballos">Cargar Caballo</button>
      <button class="btn" @click="irAGestionCarreras">Cargar Carrera</button>
      <button class="btn" @click="irAGestionFormularios">Crear Formulario</button>
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
          :src="getProdeImg(prode.tipo)"
          alt="Logo del prode"
          class="prode-img"
        />
        <div class="prode-card-content">
          <div class="prode-nombre">{{ prode.nombre }}</div>
          <div class="prode-premio">
            Premio: ${{ Math.round(prode.premio).toLocaleString('es-AR') }}
          </div>
          <div class="prode-precio">
            Precio: ${{ Math.round(prode.precio).toLocaleString('es-AR') }}
          </div>
          <div class="prode-fecha">
            Finaliza: {{ formatFecha(prode.fechafin) }}
          </div>
        </div>
      </div>
    </div>

    <!-- ❌ eliminamos modales de caballos y carreras -->
    <!-- <GestionCarrerasModal v-if="showCarreras" @close="showCarreras = false" /> -->
    <!-- <GestionCaballosModal v-if="showCaballos" @close="showCaballos = false" /> -->

    <!-- ✅ Modal de usuarios sí se mantiene -->
    <ModalFormulariosUsuarios
      v-if="showModalFormulariosUsuarios"
      :prodeId="prodeIdSeleccionado"
      :prodeNombre="prodeNombreSeleccionado"
      @close="showModalFormulariosUsuarios = false"
    />
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ModalFormulariosUsuarios from './ModalFormulariosUsuarios.vue'
import './Home.css'

const imagesPath = (import.meta.env.VITE_IMAGES_PUBLIC_PATH || '').replace(/\/$/, '')

function getProdeImg(tipo) {
  return tipo === 'puntos'
    ? `${imagesPath}/ProdeXPuntos.jpg`
    : `${imagesPath}/ProdeLibre.jpg`
}

const userName = ref('Administrador')
const prodes = ref([])
const loadingProdes = ref(false)
const errorProdes = ref('')

const showModalFormulariosUsuarios = ref(false)
const prodeIdSeleccionado = ref(null)
const prodeNombreSeleccionado = ref('')

const formatFecha = (fecha) => {
  if (!fecha) return ''
  const d = new Date(fecha)
  return d.toLocaleString('es-AR', {
    day: 'numeric',
    month: 'numeric',
    year: 'numeric',
    hour: '2-digit',
    minute: '2-digit',
    second: '2-digit'
  })
}

const cargarProdes = async () => {
  loadingProdes.value = true
  errorProdes.value = ''
  try {
    const res = await fetch('/admin/prodes/listar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    })
    if (!res.ok) throw new Error('No se pudo cargar los prodes')
    let data = await res.json()
    prodes.value = data.prodes ?? data
  } catch (e) {
    errorProdes.value = e.message
  } finally {
    loadingProdes.value = false
  }
}

const abrirModalFormularios = (id, nombre) => {
  prodeIdSeleccionado.value = id
  prodeNombreSeleccionado.value = nombre
  showModalFormulariosUsuarios.value = true
}

// ✅ ahora redirige a vistas completas
function irAGestionFormularios() {
  window.location.href = '/GestionFormulariosModal'
}
function irAGestionCarreras() {
  window.location.href = '/GestionCarrerasModal'
}
function irAGestionCaballos() {
  window.location.href = '/GestionCaballosModal'
}

onMounted(() => {
  cargarProdes()
})
</script>
