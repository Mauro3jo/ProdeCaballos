<template>
  <div class="prodes-page">
    <!-- Header -->
    <header class="page-header">
      <button class="btn-back" @click="volver">← Volver</button>
      <h1 class="mb-4">Carreras</h1>
    </header>

    <!-- Acciones header -->
    <div class="acciones-header">
      <button class="btn mb-3" v-if="!showForm && !editId" @click="abrirCrear">
        Nueva Carrera
      </button>
    </div>

    <!-- Buscador -->
    <div class="buscador">
      <input
        v-model="search"
        type="text"
        placeholder="Buscar carrera..."
        class="input"
      />
    </div>

    <!-- Modal Crear/Editar -->
    <div v-if="showForm" class="modal-backdrop">
      <div class="modal-content">
        <button class="cerrar-btn" @click="cancelarEdicionOCreacion">✕</button>
        <h2 class="mb-3">{{ editId ? 'Editar Carrera' : 'Nueva Carrera' }}</h2>

        <form @submit.prevent="guardarCarrera" class="mb-3">
          <input v-model="form.nombre" placeholder="Nombre" required class="input mb-2" />
          <input v-model="form.descripcion" placeholder="Descripción" class="input mb-2" />

          <!-- Select de Hípicos -->
          <select v-model="form.hipico" class="input mb-2" required>
            <option disabled value="">-- Seleccioná un hípico --</option>
            <option>Hipico Los Ralos</option>
            <option>Hipico camping de santa rosa</option>
            <option>Hipico Monteagudo</option>
            <option>Hipico el Monumental Atahona</option>
            <option>Hipico La Verde</option>
            <option>Hipico Las Tusquita</option>
            <option>Hipico Los Abuelos el Bobadal</option>
            <option>Hipico la Isla del rincón</option>
            <option>Hipico Macheta pozo hondo</option>
            <option>Hipico la certeza</option>
            <option>Hipodromo 27 de abril S.E</option>
            <option>Hipico san Carlos el mojón</option>
            <option>Hipico la pelucona el mojón</option>
            <option>Hipico el fragueño el mojón</option>
            <option>Hipico la victoria el Mojón</option>
            <option>Hipico el doradillo</option>
            <option>Hipico el quebrachal</option>
            <option>Hipico guardamonte</option>
          </select>

          <input v-model="form.fecha" type="datetime-local" class="input mb-2" />

          <label class="mb-1 font-semibold">Estado</label>
          <input v-model="form.estado" class="input mb-2" :disabled="!editId" />
          <small v-if="!editId" class="text-sm text-gray-500">
            Estado se fija como ACTIVA al crear
          </small>

          <!-- Caballos en CREAR -->
          <div v-if="!editId">
            <label class="mb-1 font-semibold">Seleccionar Caballos:</label>
            <div
              v-for="(caballoId, index) in form.caballos"
              :key="index"
              class="caballo-numero-row mb-2"
            >
              <select v-model="form.caballos[index]" class="input select-caballo" required>
                <option disabled value="">-- Selecciona caballo --</option>
                <option
                  v-for="caballo in caballosDisponibles(index)"
                  :key="caballo.id"
                  :value="caballo.id"
                >
                  {{ caballo.nombre }}
                </option>
              </select>
              <button
                type="button"
                @click="removerCaballo(index)"
                class="btn-danger btn-small ml-2"
              >
                X
              </button>
            </div>
            <button type="button" class="btn mb-3" @click="agregarCaballo()">Agregar Caballo</button>
          </div>

          <!-- Resultados en EDITAR -->
          <div v-if="editId" class="mt-4">
            <h3 class="mb-2 font-semibold">Resultados</h3>
            <div
              v-for="(res, index) in form.resultados"
              :key="index"
              class="resultado-row mb-3 p-3 border rounded"
            >
              <select v-model="res.caballo_id" class="input mb-2" required>
                <option disabled value="">-- Selecciona caballo --</option>
                <option
                  v-for="caballo in caballosDisponiblesResultados(index)"
                  :key="caballo.id"
                  :value="caballo.id"
                >
                  {{ caballo.nombre }}
                </option>
              </select>

              <input
                v-model.number="res.posicion"
                type="number"
                min="0"
                class="input mb-1"
                placeholder="Posición"
              />
              <small class="text-sm text-gray-500">Si ponés 0 → carrera cancelada</small>

              <div class="mt-2">
                <h4 class="font-medium mb-1">Tiempos parciales</h4>
                <div v-for="(t, i) in res.tiempos" :key="i" class="flex gap-2 mb-1">
                  <input v-model.number="t.metros" type="number" placeholder="Metros" class="input" />
                  <input v-model.number="t.tiempo" type="number" step="0.01" placeholder="Tiempo (s)" class="input" />
                  <button type="button" class="btn-danger btn-small" @click="res.tiempos.splice(i,1)">X</button>
                </div>
                <button
                  type="button"
                  class="btn-secondary btn-small mt-1"
                  @click="res.tiempos.push({metros:'',tiempo:''})"
                >
                  + Tiempo
                </button>
              </div>
            </div>
          </div>

          <div class="mt-4 flex-center">
            <button type="submit" class="btn">{{ editId ? 'Actualizar' : 'Guardar' }}</button>
            <button type="button" class="btn-secondary ml-2" @click="cancelarEdicionOCreacion">Cancelar</button>
          </div>
        </form>
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
              <th @click="ordenarPor('hipico')">
                Hípico <span v-if="sortKey==='hipico'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th @click="ordenarPor('fecha')">
                Fecha <span v-if="sortKey==='fecha'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th @click="ordenarPor('estado')">
                Estado <span v-if="sortKey==='estado'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th>Caballos</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="carrera in carrerasPaginadas" :key="carrera.id">
              <td>{{ carrera.nombre }}</td>
              <td>{{ carrera.descripcion }}</td>
              <td>{{ carrera.hipico }}</td>
              <td>{{ carrera.fecha }}</td>
              <td>{{ carrera.estado }}</td>
              <td>
                <ul>
                  <li v-for="caballo in carrera.caballos" :key="caballo.id">{{ caballo.nombre }}</li>
                </ul>
              </td>
              <td>
                <button @click="editarCarrera(carrera)" class="btn-secondary">Editar</button>
                <button @click="eliminarCarrera(carrera.id)" class="btn-danger">Eliminar</button>
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
import './GestionCarrerasModal.css'

function volver() {
  window.location.href = "/admin";
}

const carreras = ref([])
const todosCaballos = ref([])
const showForm = ref(false)
const editId = ref(null)
const search = ref('')
const sortKey = ref('')
const sortOrder = ref(1)
const paginaActual = ref(1)
const itemsPorPagina = 10

const form = ref({
  nombre: '',
  descripcion: '',
  hipico: '',
  fecha: '',
  estado: 'ACTIVA',
  caballos: [],
  resultados: []
})

// Helpers UI
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

// Data
function cargarCarreras() {
  fetch('/carreras')
    .then(r => r.json())
    .then(data => {
      carreras.value = data.carreras || data
      todosCaballos.value = data.caballos || []
    })
}

// Filtrado + orden
const carrerasFiltradas = computed(() => {
  let result = carreras.value
  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(c =>
      c.nombre.toLowerCase().includes(q) ||
      (c.descripcion || '').toLowerCase().includes(q) ||
      (c.hipico || '').toLowerCase().includes(q)
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
const totalPaginas = computed(() => Math.ceil(carrerasFiltradas.value.length / itemsPorPagina))
const carrerasPaginadas = computed(() => {
  const start = (paginaActual.value - 1) * itemsPorPagina
  return carrerasFiltradas.value.slice(start, start + itemsPorPagina)
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
function agregarCaballo() {
  form.value.caballos.push('')
}
function removerCaballo(index) {
  form.value.caballos.splice(index, 1)
}
function guardarCarrera() {
  if (!editId.value && form.value.caballos.length < 2) {
    alert('Debés asignar al menos 2 caballos')
    return
  }
  const payload = { ...form.value }
  let url = editId.value ? `/carreras/actualizar/${editId.value}` : '/carreras'
  fetch(url, {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: JSON.stringify(payload),
  })
    .then(r => {
      if (!r.ok) throw new Error('Error guardando la carrera')
      return r.json()
    })
    .then(() => {
      alert("✅ Carrera guardada correctamente")
      cargarCarreras()
      cancelarEdicionOCreacion()
    })
    .catch(e => alert(e.message))
}
function editarCarrera(carrera) {
  editId.value = carrera.id
  form.value.nombre = carrera.nombre
  form.value.descripcion = carrera.descripcion
  form.value.hipico = carrera.hipico
  form.value.fecha = carrera.fecha ? carrera.fecha.slice(0, 16) : ''
  form.value.estado = carrera.estado
  form.value.caballos = carrera.caballos.map(c => c.id)
  form.value.resultados = carrera.caballos.map(c => ({
    caballo_id: c.id,
    posicion: c.pivot?.posicion || '', // 👉 acá NO precargamos 0
    tiempos: []
  }))
  showForm.value = true
}
function eliminarCarrera(id) {
  if (confirm('¿Eliminar carrera?')) {
    fetch(`/carreras/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    }).then(() => cargarCarreras())
  }
}

// Utils
function resetForm() {
  form.value = {
    nombre: '',
    descripcion: '',
    hipico: '',
    fecha: '',
    estado: 'ACTIVA',
    caballos: [],
    resultados: []
  }
}

// Evita caballos duplicados en crear
function caballosDisponibles(indexActual) {
  const idsSeleccionados = form.value.caballos
    .map((id, idx) => (idx !== indexActual ? id : null))
    .filter(id => !!id)
  return todosCaballos.value.filter(c => !idsSeleccionados.includes(c.id))
}

// Evita caballos duplicados en editar/resultados
function caballosDisponiblesResultados(indexActual) {
  const idsSeleccionados = form.value.resultados
    .map((r, idx) => (idx !== indexActual ? r.caballo_id : null))
    .filter(id => !!id)
  return todosCaballos.value.filter(c => !idsSeleccionados.includes(c.id))
}

onMounted(cargarCarreras)
</script>
