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

      <!-- Scroll horizontal -->
      <div class="tabla-responsive">
        <table v-if="!loading && !error && formularios.length" class="tabla-formularios">
          <thead>
            <tr>
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
              <th>Acción</th>
            </tr>
          </thead>
          <tbody>
            <tr v-for="form in formularios" :key="form.id">
              <td>{{ form.nombre }}</td>
              <td>{{ form.alias }}</td>
              <td>{{ form.alias_admin || '-' }}</td>
              <td>{{ form.celular }}</td>
              <td>{{ form.forma_pago }}</td>
              <td>{{ form.preciopagado ? ('$' + parseInt(form.preciopagado).toLocaleString('es-AR')) : '-' }}</td>
              <td>{{ form.created_at }}</td>
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
              <td>
                <button
                  class="btn-wsp"
                  @click="enviarWhatsapp(form)"
                  title="Enviar comprobante por WhatsApp"
                >
                  WhatsApp
                </button>
              </td>
            </tr>
          </tbody>
        </table>
      </div>

      <div
        v-if="!loading && !error && formularios.length === 0"
        class="form-error"
      >
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
        'X-CSRF-TOKEN': document.querySelector(
          'meta[name="csrf-token"]'
        ).getAttribute('content'),
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

watch(
  () => props.prodeId,
  (id) => {
    if (id) cargarFormularios(id);
  },
  { immediate: true }
);

function cerrar() {
  emit('close');
}

// Dado un form y un pronóstico suplente, devuelve su nro según carrera_id asc
function getNumeroSuplente(form, pronostico) {
  if (!pronostico.es_suplente) return null;
  const suplentesOrdenados = form.pronosticos
    .filter(p => p.es_suplente)
    .sort((a, b) => a.carrera_id - b.carrera_id);
  return suplentesOrdenados.findIndex(p => p.carrera_id === pronostico.carrera_id) + 1;
}

// Exportar Excel con una hoja por cada precio pagado distinto
function exportarExcel() {
  // 1. Agrupar por precio pagado
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
        Fecha: f.created_at,
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

    // Si el precio es "Sin dato" la hoja se llama así, sino se pone el monto
    const nombreHoja = precio === 'Sin dato'
      ? 'Sin Dato'
      : `Pagado $${parseInt(precio).toLocaleString('es-AR')}`;

    const ws = XLSX.utils.json_to_sheet(datos);
    XLSX.utils.book_append_sheet(wb, ws, nombreHoja);
  });

  XLSX.writeFile(wb, `formularios_prode_${props.prodeId}.xlsx`);
}

// PDF y WhatsApp: solo carreras con pronóstico, suplente numerado
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
  doc.text(`Fecha: ${form.created_at}`, margenX, linea); linea += 12;

  doc.text('Pronósticos:', margenX, linea); linea += 7;

  carreras.value.forEach(c => {
    const p = form.pronosticos.find(pr => pr.carrera_id === c.id);
    if (p) {
      let texto = p.caballo_nombre || '';
      if (p.es_suplente) {
        const nro = getNumeroSuplente(form, p);
        texto += ` (Suplente ${nro})`;
      }
      doc.text(`- ${c.nombre}: ${texto}`, margenX + 2, linea);
      linea += 7;
    }
  });

  doc.save(`comprobante_prode_${props.prodeId}_${form.nombre}.pdf`);

  // WhatsApp: igual, solo carreras con pronóstico
  let mensaje = `Comprobante Prode: ${props.prodeNombre}\n`;
  mensaje += `Nombre: ${form.nombre}\nAlias: ${form.alias}\nAlias Admin: ${form.alias_admin || '-'}\nCelular: ${form.celular}\nForma de Pago: ${form.forma_pago}\nPrecio Pagado: ${form.preciopagado ? ('$' + parseInt(form.preciopagado).toLocaleString('es-AR')) : '-'}\nFecha: ${form.created_at}\n\nPronósticos:\n`;
  carreras.value.forEach(c => {
    const p = form.pronosticos.find(pr => pr.carrera_id === c.id);
    if (p) {
      let texto = p.caballo_nombre || '';
      if (p.es_suplente) {
        const nro = getNumeroSuplente(form, p);
        texto += ` (Suplente ${nro})`;
      }
      mensaje += `- ${c.nombre}: ${texto}\n`;
    }
  });

  const cel = form.celular.replace(/\D/g, '');
  window.open(`https://wa.me/54${cel}?text=${encodeURIComponent(mensaje)}`, '_blank');
}
</script>

<style scoped>
/* Scroll horizontal para la tabla */
.tabla-responsive {
  overflow-x: auto;
  margin-bottom: 2em;
}
.tabla-formularios {
  min-width: 900px;
}
</style>
