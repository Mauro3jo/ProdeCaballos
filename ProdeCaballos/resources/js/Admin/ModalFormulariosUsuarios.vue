<template>
  <div class="modal-backdrop" @click.self="cerrar">
    <div class="modal-content">
      <button class="cerrar-btn" @click="cerrar" aria-label="Cerrar modal">×</button>
      <h2>Formularios - {{ prodeNombre }}</h2>

      <div v-if="loading" class="form-loading">Cargando formularios...</div>
      <div v-else-if="error" class="form-error">{{ error }}</div>

      <button
        v-if="!loading && !error && formularios.length"
        @click="exportarExcel"
        class="btn-export"
      >
        Descargar Excel
      </button>

      <div class="tabla-responsive">
        <table v-if="!loading && !error && formularios.length" class="tabla-formularios">
          <thead>
            <tr>
              <th>#</th>
              <th>Acción</th>
              <th>Nombre</th>
              <th>Alias</th>
              <th>Alias Admin</th>
              <th>Celular</th>
              <th>Forma Pago</th>
              <th>Precio Pagado</th>
              <th>Fecha</th>
              <th v-for="carrera in carreras" :key="carrera.id">
                {{ carrera.nombre }}
              </th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="(form, index) in formularios" :key="form.id">
              <td>{{ index + 1 }}</td>
              <td class="acciones-td">
                <!-- Botón WhatsApp (ícono) -->
                <button class="btn-cel" @click="enviarWhatsapp(form)" title="Enviar comprobante por WhatsApp">
                  <svg xmlns="http://www.w3.org/2000/svg" width="20" height="20"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <path d="M22 16.92V21a2 2 0 0 1-2.18 2A19.72 19.72 0 0 1 3 5.18
                      2 2 0 0 1 5 3h4.09a2 2 0 0 1 2 1.72 13 13 0 0 0 .57 2.56
                      2 2 0 0 1-.45 2.11L9.91 11a16 16 0 0 0 5.07 5.07l1.6-1.6
                      a2 2 0 0 1 2.11-.45 13 13 0 0 0 2.56.57 2 2 0 0 1 1.72 2z"/>
                  </svg>
                </button>
                <!-- Botón Borrar formulario (ícono X en círculo) -->
                <button class="btn-x" @click="borrarFormulario(form.id)" title="Borrar formulario y todas las apuestas">
                  <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18"
                      fill="none" viewBox="0 0 24 24" stroke="currentColor" stroke-width="2">
                    <circle cx="12" cy="12" r="11" stroke="#e02424" stroke-width="2" fill="white"/>
                    <line x1="16" y1="8" x2="8" y2="16" stroke="#e02424" stroke-width="2"/>
                    <line x1="8" y1="8" x2="16" y2="16" stroke="#e02424" stroke-width="2"/>
                  </svg>
                </button>
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

      <div v-if="!loading && !error && formularios.length === 0" class="form-error">
        No hay formularios para este prode.
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, watch } from 'vue';
import * as XLSX from 'xlsx';
import jsPDF from 'jspdf';
import './ModalFormulariosUsuarios.css';

const props = defineProps({
  prodeId: Number,
  prodeNombre: String,
});
const emit = defineEmits(['close']);

const formularios = ref([]);
const carreras = ref([]);
const loading = ref(false);
const error = ref('');

function formatearFecha(fechaISO) {
  const fecha = new Date(fechaISO);
  const dia = String(fecha.getDate()).padStart(2, '0');
  const mes = String(fecha.getMonth() + 1).padStart(2, '0');
  const anio = fecha.getFullYear();
  return `${dia}/${mes}/${anio}`;
}

const cargarFormularios = async (id) => {
  loading.value = true;
  error.value = '';
  formularios.value = [];
  carreras.value = [];
  try {
    const res = await fetch('/admin/formularios/listar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({ prode_caballo_id: id }),
    });
    if (!res.ok) throw new Error('Error al cargar los formularios');
    const data = await res.json();
    formularios.value = data.formularios;
    carreras.value = data.carreras;
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
};

watch(() => props.prodeId, (id) => {
  if (id) cargarFormularios(id);
}, { immediate: true });

function cerrar() {
  emit('close');
}

function getNumeroSuplente(form, pronostico) {
  if (!pronostico.es_suplente) return null;
  const suplentesOrdenados = form.pronosticos.filter(p => p.es_suplente).sort((a, b) => a.carrera_id - b.carrera_id);
  return suplentesOrdenados.findIndex(p => p.carrera_id === pronostico.carrera_id) + 1;
}

function exportarExcel() {
  const grupos = {};
  formularios.value.forEach(f => {
    const key = f.preciopagado || 'Sin dato';
    if (!grupos[key]) grupos[key] = [];
    grupos[key].push(f);
  });

  const wb = XLSX.utils.book_new();

  Object.entries(grupos).forEach(([precio, grupo]) => {
    const datos = grupo.map((f) => {
      const fila = {
        Nombre: f.nombre,
        Alias: f.alias,
        'Alias Admin': f.alias_admin || '-',
        Celular: f.celular,
        'Forma de Pago': f.forma_pago,
        'Precio Pagado': f.preciopagado ? ('$' + parseInt(f.preciopagado).toLocaleString('es-AR')) : '-',
        Fecha: formatearFecha(f.created_at),
      };
      carreras.value.forEach(carrera => {
        const p = f.pronosticos.find(pr => pr.carrera_id === carrera.id);
        if (p) {
          if (p.es_suplente) {
            const nro = getNumeroSuplente(f, p);
            fila[carrera.nombre] = `${p.caballo_nombre || ''} (Suplente ${nro})`;
          } else {
            fila[carrera.nombre] = p.caballo_nombre || '';
          }
        } else {
          fila[carrera.nombre] = '';
        }
      });
      return fila;
    });

    const nombreHoja = precio === 'Sin dato' ? 'Sin Dato' : `Pagado $${parseInt(precio).toLocaleString('es-AR')}`;
    const ws = XLSX.utils.json_to_sheet(datos);
    XLSX.utils.book_append_sheet(wb, ws, nombreHoja);
  });

  XLSX.writeFile(wb, `formularios_prode_${props.prodeId}.xlsx`);
}

function enviarWhatsapp(form) {
  const doc = new jsPDF();
  let linea = 20;
  doc.setFontSize(16);
  doc.text('Comprobante de Prode Caballos', 105, linea, { align: 'center' });
  doc.setFontSize(12);
  const margenX = 15;
  linea += 13;
  doc.text(`Prode: ${props.prodeNombre}`, margenX, linea); linea += 8;
  doc.text(`Nombre: ${form.nombre}`, margenX, linea); linea += 8;
  doc.text(`Alias: ${form.alias}`, margenX, linea); linea += 8;
  doc.text(`Alias Admin: ${form.alias_admin || '-'}`, margenX, linea); linea += 8;
  doc.text(`Celular: ${form.celular}`, margenX, linea); linea += 8;
  doc.text(`Forma de Pago: ${form.forma_pago}`, margenX, linea); linea += 8;
  doc.text(`Precio Pagado: ${form.preciopagado ? ('$' + parseInt(form.preciopagado).toLocaleString('es-AR')) : '-'}`, margenX, linea); linea += 8;
  doc.text(`Fecha: ${formatearFecha(form.created_at)}`, margenX, linea); linea += 12;

  doc.text('Pronósticos:', margenX, linea); linea += 7;

  const pronosOrdenados = form.pronosticos.filter(p => !p.es_suplente).sort((a, b) => a.carrera_id - b.carrera_id);
  const suplentesOrdenados = form.pronosticos.filter(p => p.es_suplente).sort((a, b) => a.carrera_id - b.carrera_id);

  [...pronosOrdenados, ...suplentesOrdenados].forEach((p, idx) => {
    let texto = `${idx + 1}: ${p.caballo_nombre || ''}`;
    if (p.es_suplente) {
      const nro = getNumeroSuplente(form, p);
      texto += ` (Suplente ${nro})`;
    }
    doc.text(texto, margenX + 2, linea);
    linea += 7;
  });

  doc.save(`comprobante_prode_${props.prodeId}_${form.nombre}.pdf`);

  let mensaje = `Comprobante Prode: ${props.prodeNombre}\n`;
  mensaje += `Nombre: ${form.nombre}\nAlias: ${form.alias}\nAlias Admin: ${form.alias_admin || '-'}\nCelular: ${form.celular}\nForma de Pago: ${form.forma_pago}\nPrecio Pagado: ${form.preciopagado ? ('$' + parseInt(form.preciopagado).toLocaleString('es-AR')) : '-'}\nFecha: ${formatearFecha(form.created_at)}\n\nPronósticos:\n`;

  [...pronosOrdenados, ...suplentesOrdenados].forEach((p, idx) => {
    let texto = `${idx + 1}: ${p.caballo_nombre || ''}`;
    if (p.es_suplente) {
      const nro = getNumeroSuplente(form, p);
      texto += ` (Suplente ${nro})`;
    }
    mensaje += `${texto}\n`;
  });

  const cel = form.celular.replace(/\D/g, '');
  window.open(`https://wa.me/54${cel}?text=${encodeURIComponent(mensaje)}`, '_blank');
}

// BORRAR FORMULARIO Y TODOS SUS PRONOSTICOS
async function borrarFormulario(formularioId) {
  if (!confirm('¿Seguro que querés borrar todo este formulario y sus apuestas?')) return;
  let res, data;
  try {
    res = await fetch('/admin/formularios/borrar', {
      method: 'POST',
      headers: {
        'Content-Type': 'application/json',
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
      body: JSON.stringify({ formulario_id: formularioId }),
    });
    data = await res.json();
  } catch (e) {
    alert('Error interno del servidor. Revisá el backend.');
    return;
  }
  if (data && data.success) {
    cargarFormularios(props.prodeId);
  } else {
    alert((data && data.error) || 'Error al borrar');
  }
}
</script>

<style scoped>
.tabla-responsive {
  overflow-x: auto;
  margin-bottom: 2em;
}
.tabla-formularios {
  min-width: 900px;
}
.acciones-td {
  display: flex;
  gap: 12px;
  align-items: center;
  justify-content: center;
}
.btn-cel, .btn-x {
  background: none;
  border: none;
  padding: 2px 6px;
  margin: 0;
  cursor: pointer;
  display: inline-flex;
  align-items: center;
  border-radius: 50%;
  transition: background 0.18s, transform 0.16s;
}
.btn-cel:hover {
  background: #e4ffe9;
  box-shadow: 0 0 0 2px #b5f0c2;
  transform: scale(1.13);
}
.btn-x:hover {
  background: #ffeaea;
  box-shadow: 0 0 0 2px #f5b5b5;
  transform: scale(1.13);
}
</style>
