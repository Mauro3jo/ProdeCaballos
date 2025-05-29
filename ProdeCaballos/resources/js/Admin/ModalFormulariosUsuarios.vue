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
            <th>DNI</th>
            <th>Celular</th>
            <th>Forma Pago</th>
            <th>Fecha</th>
            <th v-for="carrera in carreras" :key="carrera.id">
              {{ carrera.nombre }}
            </th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="form in formularios" :key="form.id">
            <td>{{ form.nombre }}</td>
            <td>{{ form.dni }}</td>
            <td>{{ form.celular }}</td>
            <td>{{ form.forma_pago }}</td>
            <td>{{ form.created_at }}</td>
            <td
              v-for="carrera in carreras"
              :key="carrera.id"
            >
              {{
                (form.pronosticos.find(p => p.carrera_id === carrera.id) || {}).caballo_nombre || ''
              }}
              <span v-if="(form.pronosticos.find(p => p.carrera_id === carrera.id) || {}).es_suplente">(Suplente)</span>
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

function exportarExcel() {
  const datos = formularios.value.map((f) => {
    const fila = {
      Nombre: f.nombre,
      DNI: f.dni,
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
</script>

<style scoped>
.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  justify-content: center;
  align-items: center;
  z-index: 10000;
}

.modal-content {
  background: white;
  padding: 1.5em 2em;
  max-width: 90vw;
  max-height: 90vh;
  overflow-y: auto;
  border-radius: 1em;
  box-shadow: 0 4px 12px rgba(0, 0, 0, 0.2);
  position: relative;
}

.cerrar-btn {
  position: absolute;
  top: 1em;
  right: 1em;
  background: transparent;
  border: none;
  font-size: 1.5em;
  cursor: pointer;
}

.form-loading,
.form-error {
  text-align: center;
  font-size: 1.1em;
  margin: 1em 0;
}

.tabla-formularios {
  width: 100%;
  border-collapse: collapse;
  margin-top: 1em;
}

.tabla-formularios th,
.tabla-formularios td {
  border: 1px solid #ccc;
  padding: 0.4em 0.6em;
  text-align: left;
}

.tabla-formularios th {
  background-color: #f0f0f0;
  font-weight: 700;
}

.btn-export {
  margin-top: 0.8em;
  background-color: #2563eb;
  color: white;
  border: none;
  padding: 0.6em 1.2em;
  border-radius: 0.4em;
  cursor: pointer;
  font-weight: 600;
}

.btn-export:hover {
  background-color: #1e40af;
}
</style>
