<template>
  <div class="prodes-page">
    <!-- Header -->
    <header class="page-header">
      <button class="btn-back" @click="volver">← Volver</button>
      <h1 class="mb-4">Formularios de Usuarios - {{ prodeNombre }}</h1>
    </header>

    <!-- Buscador -->
    <div class="buscador">
      <input v-model="search" type="text" placeholder="Buscar formulario..." class="input" />
    </div>

    <div v-if="loading" class="form-loading">Cargando formularios...</div>
    <div v-else-if="error" class="form-error">{{ error }}</div>

    <button v-if="!loading && !error && formularios.length" @click="exportarExcel" class="btn">
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
              <th v-for="carrera in carreras" :key="carrera.id">{{ carrera.nombre }}</th>
              <th>Resultado Total</th>
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
              <td v-for="carrera in carreras" :key="carrera.id"
                  :style="getEstiloResultado(form, carrera.id)">
                {{
                  (() => {
                    const p = form.pronosticos.find(pr => pr.carrera_id === carrera.id);
                    if (!p) return '';
                    return `${p.caballo_nombre || ''} ${p.posicion ? '(Pos ' + p.posicion + ')' : ''}`;
                  })()
                }}
              </td>
              <td>{{ calcularResultado(form) }}</td>
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
import ExcelJS from 'exceljs'
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

// Calcula victorias según resultado
function calcularResultado(form) {
  return form.pronosticos.filter(p => p.resultado === 'ganador').length
}

// Pintar verde/rojo en la tabla
function getEstiloResultado(form, carreraId) {
  const p = form.pronosticos.find(pr => pr.carrera_id === carreraId)
  if (!p) return {}
  if (p.resultado === 'ganador') return { color: 'green', fontWeight: '600' }
  if (p.resultado === 'perdedor') return { color: 'red', fontWeight: '600' }
  return {}
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

// Exportar Excel con colores
async function exportarExcel() {
  const workbook = new ExcelJS.Workbook()
  const sheet = workbook.addWorksheet("Formularios")

  const headers = [
    "Nombre", "Alias", "Alias Admin", "Celular",
    "Forma de Pago", "Precio Pagado", "Fecha",
    ...carreras.value.map(c => c.nombre),
    "Resultado Total"
  ]
  sheet.addRow(headers)

  // 🔥 ordenar primero por cantidad de ganadores, luego alfabético
  const ordenados = [...formulariosFiltrados.value].sort((a, b) => {
    const victoriasA = a.pronosticos.filter(p => p.resultado === 'ganador').length
    const victoriasB = b.pronosticos.filter(p => p.resultado === 'ganador').length
    if (victoriasA !== victoriasB) return victoriasB - victoriasA
    return a.nombre.localeCompare(b.nombre)
  })

  ordenados.forEach(f => {
    let victorias = 0
    const rowData = [
      f.nombre,
      f.alias,
      f.alias_admin || '-',
      f.celular,
      f.forma_pago,
      f.preciopagado ? ('$' + parseInt(f.preciopagado).toLocaleString('es-AR')) : '-',
      formatearFecha(f.created_at)
    ]
    const row = sheet.addRow(rowData)

    carreras.value.forEach((carrera, idx) => {
      const p = f.pronosticos.find(pr => pr.carrera_id === carrera.id)
      const cell = row.getCell(8 + idx)
      if (p) {
        cell.value = `${p.caballo_nombre} ${p.posicion ? '(Pos ' + p.posicion + ')' : ''}`
        if (p.resultado === 'ganador') {
          cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FF00FF00" } }
          victorias++
        } else if (p.resultado === 'perdedor') {
          cell.fill = { type: "pattern", pattern: "solid", fgColor: { argb: "FFFF0000" } }
        }
      }
    })

    row.getCell(headers.length).value = victorias
  })

  const buf = await workbook.xlsx.writeBuffer()
  const blob = new Blob([buf])
  const link = document.createElement("a")
  link.href = URL.createObjectURL(blob)
  link.download = `formularios_prode_${prodeId}.xlsx`
  link.click()
}

// PDF + WhatsApp igual que antes
function enviarWhatsapp(form) {
  const doc = new jsPDF()
  let linea = 20
  doc.setFontSize(16)
  doc.text('Comprobante de Prode Caballos', 105, linea, { align: 'center' })
  doc.setFontSize(12)
  const margenX = 15
  linea += 13
  doc.text(`Prode: ${prodeNombre}`, margenX, linea); linea += 8
  doc.text(`Nombre: ${form.nombre}`, margenX, linea); linea += 8
  doc.text(`Alias: ${form.alias}`, margenX, linea); linea += 8
  doc.text(`Alias Admin: ${form.alias_admin || '-'}`, margenX, linea); linea += 8
  doc.text(`Celular: ${form.celular}`, margenX, linea); linea += 8
  doc.text(`Forma de Pago: ${form.forma_pago}`, margenX, linea); linea += 8
  doc.text(`Precio Pagado: ${form.preciopagado ? ('$' + parseInt(form.preciopagado).toLocaleString('es-AR')) : '-'}`, margenX, linea); linea += 8
  doc.text(`Fecha: ${formatearFecha(form.created_at)}`, margenX, linea); linea += 12

  doc.text('Pronósticos:', margenX, linea); linea += 7

  const pronosOrdenados = form.pronosticos.filter(p => !p.es_suplente).sort((a, b) => a.carrera_id - b.carrera_id)
  const suplentesOrdenados = form.pronosticos.filter(p => p.es_suplente).sort((a, b) => a.carrera_id - b.carrera_id)

  ;[...pronosOrdenados, ...suplentesOrdenados].forEach((p, idx) => {
    let texto = `${idx + 1}: ${p.caballo_nombre || ''}`
    if (p.es_suplente) {
      const nro = suplentesOrdenados.findIndex(s => s.carrera_id === p.carrera_id) + 1
      texto += ` (Suplente ${nro})`
    }
    doc.text(texto, margenX + 2, linea)
    linea += 7
  })

  doc.save(`comprobante_prode_${prodeId}_${form.nombre}.pdf`)

  let mensaje = `Comprobante Prode: ${prodeNombre}\n`
  mensaje += `Nombre: ${form.nombre}\nAlias: ${form.alias}\nAlias Admin: ${form.alias_admin || '-'}\nCelular: ${form.celular}\nForma de Pago: ${form.forma_pago}\nPrecio Pagado: ${form.preciopagado ? ('$' + parseInt(form.preciopagado).toLocaleString('es-AR')) : '-'}\nFecha: ${formatearFecha(form.created_at)}\n\nPronósticos:\n`

  ;[...pronosOrdenados, ...suplentesOrdenados].forEach((p, idx) => {
    let texto = `${idx + 1}: ${p.caballo_nombre || ''}`
    if (p.es_suplente) {
      const nro = suplentesOrdenados.findIndex(s => s.carrera_id === p.carrera_id) + 1
      texto += ` (Suplente ${nro})`
    }
    mensaje += `${texto}\n`
  })

  const cel = form.celular.replace(/\D/g, '')
  window.open(`https://wa.me/54${cel}?text=${encodeURIComponent(mensaje)}`, '_blank')
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

