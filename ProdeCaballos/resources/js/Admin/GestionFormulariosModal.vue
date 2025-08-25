<template>
  <div class="prodes-page">
    <!-- Header -->
    <header class="page-header">
      <button class="btn-back" @click="volver">← Volver</button>
      <h1 class="mb-4">Prodes Caballos</h1>
    </header>

    <!-- Acciones header -->
    <div class="acciones-header">
      <button
        class="btn mb-3"
        v-if="!showForm && !editId"
        @click="abrirCrear"
      >
        Nuevo Prode
      </button>
    </div>

    <!-- Modal Crear/Editar -->
    <div v-if="showForm" class="modal-backdrop">
      <div class="modal-content">
        <!-- Botón cerrar -->
        <button class="cerrar-btn" @click="cancelarEdicionOCreacion">✕</button>

        <h2 class="mb-3">{{ editId ? 'Editar Prode' : 'Nuevo Prode' }}</h2>

        <form @submit.prevent="guardarProde" enctype="multipart/form-data">
          <div class="form-grid">
            <input v-model="form.nombre" placeholder="Nombre" required class="input mb-2" />

            <input v-model.number="form.precio" type="number" min="0" placeholder="Precio" required class="input mb-2" />

            <input v-model.number="form.premio" type="number" min="0" placeholder="Premio" required class="input mb-2" />

            <input v-model="form.fechafin" type="datetime-local" placeholder="Fecha Fin" required class="input mb-2" />

            <select v-model="form.tipo" required class="input mb-2">
              <option disabled value="">Tipo de Prode</option>
              <option value="libre">Prode Libre</option>
              <option value="puntos">Prode por Puntos</option>
            </select>

            <!-- Foto (opcional) -->
            <div class="mb-2">
              <label class="mb-1 font-semibold">Imagen (opcional, .jpg/.png)</label>
              <input type="file" accept="image/*" class="input" @change="onFileChange" />
              <div v-if="fotoPreview" class="preview-wrap">
                <img :src="fotoPreview" class="preview-img" alt="preview" />
              </div>
            </div>
          </div>

          <label class="mb-1 font-semibold mt-2">Configuración del Prode</label>
          <div class="config-grid">
            <input v-model.number="form.configuracion.cantidad_obligatorias" type="number" min="0" placeholder="Carreras obligatorias" class="input" required />
            <input v-model.number="form.configuracion.cantidad_opcionales" type="number" min="0" placeholder="Carreras opcionales" class="input" required />
            <input v-model.number="form.configuracion.cantidad_suplentes" type="number" min="0" placeholder="Carreras suplentes" class="input" required />
          </div>

          <label class="mb-1 font-semibold mt-2">Seleccionar Carreras</label>
          <div v-for="(item, index) in form.carreras" :key="index" class="mb-2 carrera-row">
            <select v-model="item.id" class="input carrera-select" required>
              <option disabled value="">-- Seleccione una carrera --</option>
              <option
                v-for="carrera in carrerasDisponibles(index)"
                :key="carrera.id"
                :value="carrera.id"
              >
                {{ carrera.nombre }} — {{ carrera.fecha }}
              </option>
            </select>

            <label class="ml-2">
              <input type="checkbox" v-model="item.obligatoria" />
              Obligatoria
            </label>

            <button
              type="button"
              @click="removerCarrera(index)"
              class="btn-danger btn-small ml-2"
            >
              X
            </button>
          </div>

          <button type="button" class="btn mb-3" @click="agregarCarrera()">Agregar Carrera</button>

          <div class="mt-4 flex-center">
            <button type="submit" class="btn">
              {{ editId ? 'Actualizar' : 'Guardar' }}
            </button>
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
              <th>Nombre</th>
              <th>Precio</th>
              <th>Premio</th>
              <th>Tipo</th>
              <th>Fecha Fin</th>
              <th>Config (Obl / Opc / Sup)</th>
              <th>Carreras</th>
              <th style="min-width: 190px;">Acciones</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="prode in prodes" :key="prode.id">
              <td>{{ prode.nombre }}</td>
              <td>{{ formateaMoneda(prode.precio) }}</td>
              <td>{{ formateaMoneda(prode.premio) }}</td>
              <td>
                <span v-if="prode.tipo === 'libre'">Libre</span>
                <span v-else-if="prode.tipo === 'puntos'">Por Puntos</span>
                <span v-else>-</span>
              </td>
              <td>{{ prode.fechafin }}</td>
              <td>
                {{ prode.configuraciones?.[0]?.cantidad_obligatorias || 0 }} /
                {{ prode.configuraciones?.[0]?.cantidad_opcionales || 0 }} /
                {{ prode.configuraciones?.[0]?.cantidad_suplentes || 0 }}
              </td>
              <td>
                <!-- Carreras comprimidas -->
                <div class="chips">
                  <span
                    class="chip"
                    v-for="c in carrerasCortas(prode.carreras)"
                    :key="`c-${prode.id}-${c.id}`"
                    :title="c.nombre"
                  >
                    {{ acortar(c.nombre, 18) }}
                    <small class="chip-tag">{{ c.pivot?.obligatoria ? 'Obl' : 'Opc' }}</small>
                  </span>
                  <span v-if="prode.carreras?.length > 6" class="chip chip--more">
                    +{{ prode.carreras.length - 6 }} más
                  </span>
                </div>
              </td>
              <td class="acciones-td">
                <button class="btn-secondary" @click="abrirEditar(prode)">Editar</button>
                <button class="btn-danger" @click="eliminarProde(prode.id)">Eliminar</button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import { useRouter } from 'vue-router'
import './ModalProdesCaballos.css'

const router = useRouter()

function volver() {
  window.location.href = "/admin";
}


const prodes = ref([])
const todasCarreras = ref([])
const showForm = ref(false)
const editId = ref(null)
const fotoFile = ref(null)
const fotoPreview = ref(null)

const form = ref({
  nombre: '',
  precio: 0,
  premio: 0,
  fechafin: '',
  tipo: '',
  reglas: '',
  configuracion: {
    cantidad_obligatorias: 0,
    cantidad_opcionales: 0,
    cantidad_suplentes: 0,
  },
  carreras: [],
})

// ------ Helpers UI ------
function abrirCrear() {
  resetForm()
  editId.value = null
  showForm.value = true
}
function abrirEditar(prode) {
  editarProde(prode)
  showForm.value = true
}
function cancelarEdicionOCreacion() {
  showForm.value = false
  resetForm()
  editId.value = null
  fotoPreview.value = null
  fotoFile.value = null
}

// ------ Data ------
function cargarProdes() {
  fetch('/prode-caballos', {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  })
    .then(r => r.json())
    .then(data => {
      prodes.value = data.prodes || data
    })
}

function cargarCarreras() {
  fetch('/carreras', {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  })
    .then(r => r.json())
    .then(data => {
      todasCarreras.value = data.carreras || data
    })
}

function carrerasDisponibles(indexActual) {
  const idsSeleccionados = form.value.carreras
    .map((c, idx) => (idx !== indexActual ? c.id : null))
    .filter(id => !!id)
  return todasCarreras.value.filter(c => !idsSeleccionados.includes(c.id))
}

function agregarCarrera() {
  form.value.carreras.push({ id: '', obligatoria: false })
}
function removerCarrera(index) {
  form.value.carreras.splice(index, 1)
}

function onFileChange(e) {
  const file = e.target.files?.[0]
  if (!file) {
    fotoFile.value = null
    fotoPreview.value = null
    return
  }
  fotoFile.value = file
  const reader = new FileReader()
  reader.onload = () => (fotoPreview.value = reader.result)
  reader.readAsDataURL(file)
}

// ------ CRUD ------
function guardarProde() {
  const url = editId.value ? `/prode-caballos/${editId.value}` : '/prode-caballos'
  const method = 'POST'

  const formData = new FormData()
  formData.append('nombre', form.value.nombre)
  formData.append('precio', form.value.precio)
  formData.append('premio', form.value.premio)
  formData.append('fechafin', form.value.fechafin)
  formData.append('tipo', form.value.tipo)

  if (fotoFile.value) formData.append('foto', fotoFile.value)

  formData.append('configuracion[cantidad_obligatorias]', form.value.configuracion.cantidad_obligatorias)
  formData.append('configuracion[cantidad_opcionales]', form.value.configuracion.cantidad_opcionales)
  formData.append('configuracion[cantidad_suplentes]', form.value.configuracion.cantidad_suplentes)

  form.value.carreras.forEach((c, i) => {
    formData.append(`carreras[${i}][id]`, c.id)
    formData.append(`carreras[${i}][obligatoria]`, c.obligatoria ? 1 : 0)
  })

  if (editId.value) formData.append('_method', 'PUT')

  fetch(url, {
    method,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: formData,
  })
    .then(r => {
      if (!r.ok) throw new Error('Error guardando prode')
      return r.json()
    })
    .then(() => {
      cargarProdes()
      cancelarEdicionOCreacion()
    })
    .catch(e => alert(e.message))
}

function editarProde(prode) {
  editId.value = prode.id
  form.value.nombre = prode.nombre
  form.value.precio = prode.precio
  form.value.premio = prode.premio
  form.value.fechafin = prode.fechafin ? prode.fechafin.slice(0, 16) : ''
  form.value.tipo = prode.tipo || ''

  const config = prode.configuraciones?.[0] || {
    cantidad_obligatorias: 0,
    cantidad_opcionales: 0,
    cantidad_suplentes: 0,
  }

  form.value.configuracion = {
    cantidad_obligatorias: config.cantidad_obligatorias,
    cantidad_opcionales: config.cantidad_opcionales,
    cantidad_suplentes: config.cantidad_suplentes,
  }

  form.value.carreras =
    prode.carreras?.map(c => ({
      id: c.id,
      obligatoria: c.pivot?.obligatoria || false,
    })) || []
}

function eliminarProde(id) {
  if (!confirm('¿Eliminar prode?')) return
  fetch(`/prode-caballos/${id}`, {
    method: 'DELETE',
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  }).then(() => cargarProdes())
}

// ------ utils ------
function resetForm() {
  form.value = {
    nombre: '',
    precio: 0,
    premio: 0,
    fechafin: '',
    tipo: '',
    reglas: '',
    configuracion: {
      cantidad_obligatorias: 0,
      cantidad_opcionales: 0,
      cantidad_suplentes: 0,
    },
    carreras: [],
  }
  fotoFile.value = null
  fotoPreview.value = null
}
function formateaMoneda(n) {
  const num = Number(n || 0)
  return '$' + num.toLocaleString('es-AR')
}
function acortar(txt = '', max = 18) {
  return txt.length > max ? txt.slice(0, max - 1) + '…' : txt
}
function carrerasCortas(lista = []) {
  return (lista || []).slice(0, 6)
}

onMounted(() => {
  cargarProdes()
  cargarCarreras()
})
</script>
