<template>
  <div class="prodes-page">
    <!-- Header -->
    <header class="page-header">
      <button class="btn-back" @click="volver">← Volver</button>
      <h1 class="mb-4">Caballos</h1>
    </header>

    <!-- Acciones -->
    <div class="acciones-header">
      <button class="btn mb-3" v-if="!showForm && !editId" @click="abrirCrear">
        Nuevo Caballo
      </button>
    </div>

    <!-- Buscador -->
    <div class="buscador">
      <input v-model="search" type="text" placeholder="Buscar caballo..." class="input" />
    </div>

    <!-- Modal Crear/Editar -->
    <div v-if="showForm" class="modal-backdrop">
      <div class="modal-content">
        <button class="cerrar-btn" @click="cancelarEdicionOCreacion">✕</button>
        <h2 class="mb-3">{{ editId ? 'Editar Caballo' : 'Nuevo Caballo' }}</h2>

        <form @submit.prevent="guardarCaballo" class="mb-3">
          <input v-model="form.nombre" placeholder="Nombre" required class="input mb-2" />
          <input v-model="form.descripcion" placeholder="Descripción" class="input mb-2" />

          <div class="mt-4 flex-center">
            <button type="submit" class="btn">{{ editId ? 'Actualizar' : 'Guardar' }}</button>
            <button type="button" class="btn-secondary ml-2" @click="cancelarEdicionOCreacion">Cancelar</button>
          </div>
        </form>
      </div>
    </div>

    <!-- Modal Historial -->
    <div v-if="showHistorial" class="modal-backdrop">
      <div class="modal-content">
        <button class="cerrar-btn" @click="cerrarHistorial">✕</button>
        <h2 class="mb-3">Historial de {{ caballoSeleccionado?.nombre }}</h2>

        <!-- Resumen -->
        <div v-if="resumenHistorial" class="mb-4">
          <p><strong>Total de carreras:</strong> {{ resumenHistorial.total_carreras }}</p>
          <p><strong>Victorias:</strong> {{ resumenHistorial.victorias }}</p>
          <p><strong>% Victorias:</strong> {{ resumenHistorial.porcentaje_victorias }}%</p>
        </div>

        <!-- Tabla de historial (filas por cada tiempo) -->
        <div class="tabla-responsive">
          <table class="tabla">
            <thead>
              <tr>
                <th>Carrera</th>
                <th>Fecha</th>
                <th>Posición</th>
                <th>Diferencia</th>
                <th>Distancia</th>
                <th>Tiempo</th>
                <th>Observación</th>
              </tr>
            </thead>
            <tbody>
              <tr v-for="(f, idx) in filasHistorial" :key="idx">
                <td>{{ f.carrera_nombre }}</td>
                <td>{{ f.fecha ?? '-' }}</td>
                <td>{{ f.posicion ?? '-' }}</td>
                <td>{{ f.diferencia ?? '-' }}</td>
                <td>{{ f.metros ?? '-' }}</td>
                <td>{{ f.tiempo ?? '-' }}</td>
                <td>{{ f.carrera_descripcion ?? '-' }}</td>
              </tr>
            </tbody>
          </table>
        </div>
      </div>
    </div>

    <!-- Tabla -->
    <div class="tabla-wrapper">
      <div class="tabla-responsive">
        <table class="tabla">
          <thead>
            <tr>
              <th @click="ordenarPor('nombre')">
                Nombre <span v-if="sortKey==='nombre'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th @click="ordenarPor('descripcion')">
                Descripción <span v-if="sortKey==='descripcion'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="caballo in caballosPaginados" :key="caballo.id">
              <td>{{ caballo.nombre }}</td>
              <td>{{ caballo.descripcion }}</td>
              <td class="acciones-td">
                <button @click="editarCaballo(caballo)" class="btn-secondary">Editar</button>
                <button @click="eliminarCaballo(caballo.id)" class="btn-danger">Eliminar</button>
                <button @click="verHistorial(caballo)" class="btn">Historial</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Paginación -->
    <div class="paginacion">
      <button :disabled="paginaActual===1" @click="paginaActual--" class="btn-secondary">Anterior</button>
      <button
        v-for="n in totalPaginas"
        :key="n"
        :class="['btn-secondary', {activo: paginaActual===n}]"
        @click="paginaActual = n"
      >
        {{ n }}
      </button>
      <button :disabled="paginaActual===totalPaginas" @click="paginaActual++" class="btn-secondary">Siguiente</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import './CaballosModal.css'

function volver() {
  window.location.href = "/admin";
}

const caballos = ref([])
const showForm = ref(false)
const editId = ref(null)
const search = ref('')
const sortKey = ref('')
const sortOrder = ref(1)
const paginaActual = ref(1)
const itemsPorPagina = 10

const form = ref({
  nombre: '',
  descripcion: ''
})

// Historial
const showHistorial = ref(false)
const historial = ref([])            // array de carreras con .tiempos[]
const resumenHistorial = ref(null)
const caballoSeleccionado = ref(null)

// Helpers
function abrirCrear() {
  resetForm()
  editId.value = null
  showForm.value = true
}
function cancelarEdicionOCreacion() {
  showForm.value = false
  resetForm()
  editId.value = null
}
function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content')
}

// Data
function cargarCaballos() {
  fetch('/caballos')
    .then(r => r.json())
    .then(data => caballos.value = data)
}

// Historial
function verHistorial(caballo) {
  caballoSeleccionado.value = caballo
  fetch(`/caballos/${caballo.id}/historial`)
    .then(r => r.json())
    .then(data => {
      historial.value = data.carreras || []   // viene con carrera_descripcion y tiempos[].diferencia
      resumenHistorial.value = data
      showHistorial.value = true
    })
}
function cerrarHistorial() {
  showHistorial.value = false
  historial.value = []
  resumenHistorial.value = null
}

// Aplana historial en filas por cada tiempo
const filasHistorial = computed(() => {
  const filas = []
  for (const c of historial.value) {
    if (c.tiempos && c.tiempos.length > 0) {
      for (const t of c.tiempos) {
        filas.push({
          carrera_nombre: c.carrera_nombre,
          carrera_descripcion: c.carrera_descripcion, // observación
          fecha: c.fecha,
          posicion: c.posicion,
          diferencia: t?.diferencia ?? null,
          metros: t?.metros ?? null,
          tiempo: t?.tiempo ?? null,
        })
      }
    } else {
      filas.push({
        carrera_nombre: c.carrera_nombre,
        carrera_descripcion: c.carrera_descripcion,
        fecha: c.fecha,
        posicion: c.posicion,
        diferencia: null,
        metros: null,
        tiempo: null,
      })
    }
  }
  return filas
})

// Computed con búsqueda + orden (tabla principal)
const caballosFiltrados = computed(() => {
  let result = caballos.value
  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(c =>
      c.nombre.toLowerCase().includes(q) ||
      (c.descripcion || '').toLowerCase().includes(q)
    )
  }
  if (sortKey.value) {
    result = [...result].sort((a, b) => {
      let valA = a[sortKey.value] ?? ''
      let valB = b[sortKey.value] ?? ''
      if (typeof valA === 'string') valA = valA.toLowerCase()
      if (typeof valB === 'string') valB = valB.toLowerCase()
      if (valA < valB) return -1 * sortOrder.value
      if (valA > valB) return 1 * sortOrder.value
      return 0
    })
  }
  return result
})

// Paginación
const totalPaginas = computed(() => Math.ceil(caballosFiltrados.value.length / itemsPorPagina))
const caballosPaginados = computed(() => {
  const start = (paginaActual.value - 1) * itemsPorPagina
  return caballosFiltrados.value.slice(start, start + itemsPorPagina)
})

function ordenarPor(columna) {
  if (sortKey.value === columna) {
    sortOrder.value *= -1
  } else {
    sortKey.value = columna
    sortOrder.value = 1
  }
}

// CRUD
function guardarCaballo() {
  const url = editId.value ? `/caballos/${editId.value}` : '/caballos'
  const method = editId.value ? 'PUT' : 'POST'
  fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    body: JSON.stringify(form.value),
  })
    .then(r => {
      if (!r.ok) throw new Error('Error en la petición')
      return r.json()
    })
    .then(() => {
      cargarCaballos()
      cancelarEdicionOCreacion()
    })
    .catch(e => alert('Error: ' + e.message))
}

function editarCaballo(caballo) {
  showForm.value = true
  editId.value = caballo.id
  form.value = { ...caballo }
}

function eliminarCaballo(id) {
  if (confirm('¿Eliminar caballo?')) {
    fetch(`/caballos/${id}`, {
      method: 'DELETE',
      headers: { 'X-CSRF-TOKEN': getCsrfToken() },
    }).then(() => cargarCaballos())
  }
}

// Utils
function resetForm() {
  form.value = { nombre: '', descripcion: '' }
}

onMounted(cargarCaballos)
</script>
