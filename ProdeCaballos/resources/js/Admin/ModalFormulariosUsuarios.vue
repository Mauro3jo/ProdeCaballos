<template>
  <div class="prodes-page">
    <!-- Header -->
    <header class="page-header">
      <button class="btn-back" @click="volver">← Volver</button>
      <h1 class="mb-4">Formularios de Usuarios - {{ prodeNombre }}</h1>
    </header>

    <!-- Buscador -->
    <div class="buscador">
      <input
        v-model="search"
        type="text"
        placeholder="Buscar formulario..."
        class="input"
      />
    </div>

    <div v-if="loading" class="form-loading">Cargando formularios...</div>
    <div v-else-if="error" class="form-error">{{ error }}</div>

    <button
      v-if="!loading && !error && formularios.length"
      @click="exportarExcel"
      class="btn"
    >
      Descargar Excel
    </button>

    <!-- Tabla -->
    <div class="tabla-wrapper">
      <div class="tabla-responsive">
        <table v-if="!loading && !error && formularios.length" class="tabla">
          <thead>
            <tr>
              <th>#</th>
              <th>Acciones</th>
              <th @click="ordenarPor('nombre')">
                Nombre <span v-if="sortKey==='nombre'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th @click="ordenarPor('alias')">
                Alias <span v-if="sortKey==='alias'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th>Alias Admin</th>
              <th>Celular</th>
              <th @click="ordenarPor('forma_pago')">
                Forma Pago <span v-if="sortKey==='forma_pago'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th @click="ordenarPor('preciopagado')">
                Precio Pagado <span v-if="sortKey==='preciopagado'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th @click="ordenarPor('created_at')">
                Fecha <span v-if="sortKey==='created_at'">{{ sortOrder===1 ? '▲' : '▼' }}</span>
              </th>
              <th v-for="carrera in carreras" :key="carrera.id">
                {{ carrera.nombre }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(form, index) in formulariosPaginados" :key="form.id">
              <td>{{ index + 1 }}</td>
              <td class="acciones-td">
                <button class="btn-cel" @click="enviarWhatsapp(form)">📲</button>
                <button class="btn-danger" @click="borrarFormulario(form.id)">❌</button>
              </td>
              <td>{{ form.nombre }}</td>
              <td>{{ form.alias }}</td>
              <td>{{ form.alias_admin || '-' }}</td>
              <td>{{ form.celular }}</td>
              <td>{{ form.forma_pago }}</td>
              <td>{{ form.preciopagado ? ('$' + parseInt(form.preciopagado).toLocaleString('es-AR')) : '-' }}</td>
              <td>{{ formatearFecha(form.created_at) }}</td>
              <td v-for="carrera in carreras" :key="carrera.id">
                {{
                  (() => {
                    const p = form.pronosticos.find(pr => pr.carrera_id === carrera.id);
                    if (!p) return '';
                    if (p.es_suplente) {
                      const nro = getNumeroSuplente(form, p);
                      return `${p.caballo_nombre || ''} (Suplente ${nro})`;
                    }
                    return p.caballo_nombre || '';
                  })()
                }}
              </td>
            </tr>
          </tbody>
        </table>
      </div>
    </div>

    <!-- Paginación -->
    <div class="paginacion" v-if="totalPaginas > 1">
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
import * as XLSX from 'xlsx'
import jsPDF from 'jspdf'
import './ModalFormulariosUsuarios.css'

function volver() {
  window.location.href = "/admin"
}

const prodeId = window.prodeId
const prodeNombre = window.prodeNombre || ''

const formularios = ref([])
const carreras = ref([])
const loading = ref(false)
const error = ref('')
const search = ref('')
const sortKey = ref('')
const sortOrder = ref(1)
const paginaActual = ref(1)
const itemsPorPagina = 10

function formatearFecha(fechaISO) {
  return new Date(fechaISO).toLocaleDateString('es-AR')
}

function getNumeroSuplente(form, pronostico) {
  if (!pronostico.es_suplente) return null
  const suplentesOrdenados = form.pronosticos.filter(p => p.es_suplente).sort((a, b) => a.carrera_id - b.carrera_id)
  return suplentesOrdenados.findIndex(p => p.carrera_id === pronostico.carrera_id) + 1
}

const cargarFormularios = async () => {
  loading.value = true
  try {
    const res = await fetch('/admin/formularios/listar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({ prode_caballo_id: prodeId }),
    })
    const data = await res.json()
    formularios.value = data.formularios
    carreras.value = data.carreras
  } catch (e) {
    error.value = e.message
  } finally {
    loading.value = false
  }
}

// Búsqueda + orden
const formulariosFiltrados = computed(() => {
  let result = formularios.value
  if (search.value) {
    const q = search.value.toLowerCase()
    result = result.filter(f =>
      f.nombre.toLowerCase().includes(q) ||
      (f.alias || '').toLowerCase().includes(q) ||
      (f.forma_pago || '').toLowerCase().includes(q)
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
const totalPaginas = computed(() => Math.ceil(formulariosFiltrados.value.length / itemsPorPagina))
const formulariosPaginados = computed(() => {
  const start = (paginaActual.value - 1) * itemsPorPagina
  return formulariosFiltrados.value.slice(start, start + itemsPorPagina)
})

function ordenarPor(columna) {
  if (sortKey.value === columna) {
    sortOrder.value *= -1
  } else {
    sortKey.value = columna
    sortOrder.value = 1
  }
}

// Exportar Excel
function exportarExcel() {
  const wb = XLSX.utils.book_new()
  formulariosFiltrados.value.forEach(f => {
    const fila = {
      Nombre: f.nombre,
      Alias: f.alias,
      'Alias Admin': f.alias_admin || '-',
      Celular: f.celular,
      'Forma de Pago': f.forma_pago,
      'Precio Pagado': f.preciopagado ? ('$' + parseInt(f.preciopagado).toLocaleString('es-AR')) : '-',
      Fecha: formatearFecha(f.created_at),
    }
    carreras.value.forEach(carrera => {
      const p = f.pronosticos.find(pr => pr.carrera_id === carrera.id)
      fila[carrera.nombre] = p
        ? (p.es_suplente ? `${p.caballo_nombre} (Suplente ${getNumeroSuplente(f, p)})` : p.caballo_nombre)
        : ''
    })
    const ws = XLSX.utils.json_to_sheet([fila])
    XLSX.utils.book_append_sheet(wb, ws, f.nombre)
  })
  XLSX.writeFile(wb, `formularios_prode_${prodeId}.xlsx`)
}

function enviarWhatsapp(form) {
  const doc = new jsPDF()
  doc.setFontSize(16)
  doc.text('Comprobante de Prode Caballos', 105, 20, { align: 'center' })
  doc.setFontSize(12)
  doc.text(`Prode: ${prodeNombre}`, 15, 35)
  doc.text(`Nombre: ${form.nombre}`, 15, 43)
  doc.text(`Alias: ${form.alias}`, 15, 51)
  doc.save(`comprobante_prode_${prodeId}_${form.nombre}.pdf`)
  const cel = form.celular.replace(/\D/g, '')
  window.open(`https://wa.me/54${cel}?text=Comprobante%20del%20prode%20${encodeURIComponent(prodeNombre)}`, '_blank')
}

async function borrarFormulario(id) {
  if (!confirm('¿Seguro que querés borrar este formulario y sus apuestas?')) return
  await fetch('/admin/formularios/borrar', {
    method: 'POST',
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: JSON.stringify({ formulario_id: id }),
  })
  cargarFormularios()
}

onMounted(cargarFormularios)
</script>
