<template>
  <div class="prodes-page">
    <!-- Header -->
    <header class="page-header">
      <h1 class="mb-4">Caballos</h1>
    </header>

    <!-- Buscador -->
    <div class="buscador">
      <input v-model="search" type="text" placeholder="Buscar caballo..." class="input" />
    </div>

    <!-- Modal Historial -->
    <div v-if="showHistorial" class="modal-backdrop">
      <div class="modal-content">
        <button class="cerrar-btn" @click="cerrarHistorial">✕</button>
        <h2 class="mb-3 flex items-center gap-2">
          <span style="font-size:1.6rem;">🕓</span>
        </h2>

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

    <!-- Tabla principal -->
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
                <button @click="verHistorial(caballo)" class="btn">🕓</button>
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
        v-for="n in paginasVisibles"
        :key="n"
        :disabled="n==='...'"
        :class="['btn-secondary', {activo: paginaActual===n}]"
        @click="cambiarPagina(n)"
      >
        {{ n }}
      </button>

      <button :disabled="paginaActual===totalPaginas" @click="paginaActual++" class="btn-secondary">Siguiente</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted, computed } from 'vue'
import './HomeEstadisticas.css'

const caballos = ref([])
const search = ref('')
const sortKey = ref('')
const sortOrder = ref(1)
const paginaActual = ref(1)
const itemsPorPagina = 10

// Historial
const showHistorial = ref(false)
const historial = ref([])           // viene como array de carreras, cada una con .tiempos[]
const resumenHistorial = ref(null)
const caballoSeleccionado = ref(null)

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
      historial.value = data.carreras || []
      resumenHistorial.value = data
      showHistorial.value = true
    })
}
function cerrarHistorial() {
  showHistorial.value = false
  historial.value = []
  resumenHistorial.value = null
}

// Flatten para mostrar una fila por cada tiempo
const filasHistorial = computed(() => {
  // Estructura esperada de cada item en historial:
  // { carrera_nombre, carrera_descripcion, fecha, posicion, tiempos: [{metros, tiempo, diferencia}] }
  const filas = []
  for (const c of historial.value) {
    if (c.tiempos && c.tiempos.length > 0) {
      for (const t of c.tiempos) {
        filas.push({
          carrera_nombre: c.carrera_nombre,
          carrera_descripcion: c.carrera_descripcion,
          fecha: c.fecha,
          posicion: c.posicion,
          diferencia: t?.diferencia ?? null,
          metros: t?.metros ?? null,
          tiempo: t?.tiempo ?? null,
        })
      }
    } else {
      // Sin tiempos: una fila “vacía” para esa carrera
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

// Paginación limitada (tabla principal)
const totalPaginas = computed(() => Math.ceil(caballosFiltrados.value.length / itemsPorPagina))

function cambiarPagina(n) {
  if (n !== '...') {
    paginaActual.value = n
  }
}

const paginasVisibles = computed(() => {
  const total = totalPaginas.value
  let pages = []

  if (total <= 5) {
    for (let i = 1; i <= total; i++) pages.push(i)
  } else {
    pages = [1, 2, 3, '...', total]
  }
  return pages
})

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

onMounted(cargarCaballos)
</script>
