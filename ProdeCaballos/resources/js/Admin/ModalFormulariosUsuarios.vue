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

      <table v-if="!loading && !error && formularios.length" class="tabla-formularios">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Alias</th>
            <th>Alias Admin</th>
            <th>Celular</th>
            <th>Forma Pago</th>
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
            <td>{{ form.created_at }}</td>
            <td v-for="carrera in carreras" :key="carrera.id">
              {{
                (form.pronosticos.find(p => p.carrera_id === carrera.id) || {}).caballo_nombre || ''
              }}
              <span v-if="(form.pronosticos.find(p => p.carrera_id === carrera.id) || {}).es_suplente">(Suplente)</span>
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
import autoTable from 'jspdf-autotable'; // ← Import correcto
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

// Exportar Excel con las mismas columnas que la tabla
function exportarExcel() {
  const datos = formularios.value.map((f) => {
    const fila = {
      Nombre: f.nombre,
      Alias: f.alias,
      'Alias Admin': f.alias_admin || '-',
      Celular: f.celular,
      'Forma de Pago': f.forma_pago,
      Fecha: f.created_at,
    };
    carreras.value.forEach(carrera => {
      const p = f.pronosticos.find(pr => pr.carrera_id === carrera.id);
      fila[carrera.nombre] = p
        ? `${p.caballo_nombre || ''}${p.es_suplente ? ' (Suplente)' : ''}`
        : '';
    });
    return fila;
  });

  const ws = XLSX.utils.json_to_sheet(datos);
  const wb = XLSX.utils.book_new();
  XLSX.utils.book_append_sheet(wb, ws, 'Formularios');
  XLSX.writeFile(wb, `formularios_prode_${props.prodeId}.xlsx`);
}

// Generar PDF comprobante de un formulario y abrir WhatsApp Web con link (no soporta adjuntar archivo, pero sí pre-cargar mensaje)
function enviarWhatsapp(form) {
  const doc = new jsPDF();

  // Encabezado
  doc.setFontSize(16);
  doc.text('Comprobante de Prode Caballos', 105, 15, { align: 'center' });
  doc.setFontSize(12);

  // Info principal
  doc.text(`Prode: ${props.prodeNombre}`, 15, 30);
  doc.text(`Nombre: ${form.nombre}`, 15, 38);
  doc.text(`Alias: ${form.alias}`, 15, 46);
  doc.text(`Alias Admin: ${form.alias_admin || '-'}`, 15, 54);
  doc.text(`Celular: ${form.celular}`, 15, 62);
  doc.text(`Forma de Pago: ${form.forma_pago}`, 15, 70);
  doc.text(`Fecha: ${form.created_at}`, 15, 78);

  // Tabla carreras y pronósticos (usar autoTable como función, no método)
  const columns = [
    ...carreras.value.map(c => c.nombre)
  ];
  const row = [
    carreras.value.map(c => {
      const p = form.pronosticos.find(pr => pr.carrera_id === c.id);
      return p
        ? `${p.caballo_nombre || ''}${p.es_suplente ? ' (Suplente)' : ''}`
        : '';
    })
  ];
  autoTable(doc, {
    startY: 86,
    head: [columns],
    body: row,
    theme: 'grid',
    headStyles: { fillColor: [37,99,235] }
  });

  doc.save(`comprobante_prode_${props.prodeId}_${form.nombre}.pdf`);

  // Prepara mensaje de texto para WhatsApp (resumen en texto plano)
  let mensaje = `Comprobante Prode: ${props.prodeNombre}\n`;
  mensaje += `Nombre: ${form.nombre}\nAlias: ${form.alias}\nAlias Admin: ${form.alias_admin || '-'}\nCelular: ${form.celular}\nForma de Pago: ${form.forma_pago}\nFecha: ${form.created_at}\n\nPronósticos:\n`;
  carreras.value.forEach(c => {
    const p = form.pronosticos.find(pr => pr.carrera_id === c.id);
    mensaje += `- ${c.nombre}: ${p ? `${p.caballo_nombre || ''}${p.es_suplente ? ' (Suplente)' : ''}` : ''}\n`;
  });

  const cel = form.celular.replace(/\D/g, ''); // Solo números
  window.open(`https://wa.me/54${cel}?text=${encodeURIComponent(mensaje)}`, '_blank');
}
</script>
