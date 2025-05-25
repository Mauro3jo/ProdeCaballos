<template>
  <div class="modal-backdrop">
    <div class="modal-content">
      <h2 class="mb-4">Prodes Caballos</h2>

      <button class="btn mb-3" @click="showForm = !showForm">
        {{ showForm ? 'Cancelar' : (editId ? 'Editar Prode' : 'Nuevo Prode') }}
      </button>

      <form v-if="showForm" @submit.prevent="guardarProde" class="mb-4">
        <input v-model="form.nombre" placeholder="Nombre" required class="input mb-2" />
        <input v-model.number="form.precio" type="number" min="0" placeholder="Precio" required class="input mb-2" />
        <input v-model="form.fechafin" type="datetime-local" placeholder="Fecha Fin" required class="input mb-2" />

        <label class="mb-1 font-semibold">Configuración del Prode</label>
        <input
          v-model.number="form.configuracion.cantidad_obligatorias"
          type="number"
          min="0"
          placeholder="Carreras obligatorias"
          class="input mb-2"
          required
        />
        <input
          v-model.number="form.configuracion.cantidad_opcionales"
          type="number"
          min="0"
          placeholder="Carreras opcionales"
          class="input mb-2"
          required
        />
        <input
          v-model.number="form.configuracion.cantidad_suplentes"
          type="number"
          min="0"
          placeholder="Carreras suplentes"
          class="input mb-2"
          required
        />

        <label class="mb-1 font-semibold">Seleccionar Carreras</label>

        <div v-for="(item, index) in form.carreras" :key="index" class="mb-2 carrera-row">
          <select v-model="item.id" class="input carrera-select" required>
            <option disabled value="">-- Seleccione una carrera --</option>
            <option
              v-for="carrera in todasCarreras"
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

          <button type="button" @click="removerCarrera(index)" class="btn-danger btn-small ml-2">
            X
          </button>
        </div>

        <button type="button" class="btn mb-3" @click="agregarCarrera()">Agregar Carrera</button>

        <button type="submit" class="btn">{{ editId ? 'Actualizar' : 'Guardar' }}</button>
      </form>

      <table class="tabla">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Precio</th>
            <th>Fecha Fin</th>
            <th>Configuración (Obl / Opc / Sup)</th>
            <th>Carreras</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="prode in prodes" :key="prode.id">
            <td>{{ prode.nombre }}</td>
            <td>{{ prode.precio }}</td>
            <td>{{ prode.fechafin }}</td>
            <td>
              {{ prode.configuraciones?.length ? prode.configuraciones[0].cantidad_obligatorias : 0 }} /
              {{ prode.configuraciones?.length ? prode.configuraciones[0].cantidad_opcionales : 0 }} /
              {{ prode.configuraciones?.length ? prode.configuraciones[0].cantidad_suplentes : 0 }}
            </td>
            <td>
              <ul>
                <li v-for="carrera in prode.carreras" :key="carrera.id">
                  {{ carrera.nombre }} <span v-if="carrera.pivot">({{ carrera.pivot.obligatoria ? 'Obligatoria' : 'Opcional' }})</span>
                </li>
              </ul>
            </td>
            <td>
              <button @click="editarProde(prode)" class="btn-secondary">Editar</button>
              <button @click="eliminarProde(prode.id)" class="btn-danger">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>

      <button class="btn-secondary mt-4" @click="$emit('close')">Cerrar</button>
    </div>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue';

const prodes = ref([]);
const todasCarreras = ref([]);
const showForm = ref(false);
const editId = ref(null);

const form = ref({
  nombre: '',
  precio: 0,
  fechafin: '',
  configuracion: {
    cantidad_obligatorias: 0,
    cantidad_opcionales: 0,
    cantidad_suplentes: 0,
  },
  carreras: [],
});

function cargarProdes() {
  fetch('/prode-caballos', {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  })
    .then(r => r.json())
    .then(data => {
      prodes.value = data.prodes || data;
    });
}

function cargarCarreras() {
  fetch('/carreras', {
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
  })
    .then(r => r.json())
    .then(data => {
      todasCarreras.value = data.carreras || data;
    });
}

function agregarCarrera() {
  form.value.carreras.push({ id: '', obligatoria: false });
}

function removerCarrera(index) {
  form.value.carreras.splice(index, 1);
}

function guardarProde() {
  const url = editId.value ? `/prode-caballos/${editId.value}` : '/prode-caballos';
  const method = editId.value ? 'PUT' : 'POST';

  fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: JSON.stringify(form.value),
  })
    .then(r => {
      if (!r.ok) throw new Error('Error guardando prode');
      return r.json();
    })
    .then(() => {
      cargarProdes();
      resetForm();
      showForm.value = false;
      editId.value = null;
    })
    .catch(e => alert(e.message));
}

function editarProde(prode) {
  editId.value = prode.id;
  form.value.nombre = prode.nombre;
  form.value.precio = prode.precio;
  form.value.fechafin = prode.fechafin ? prode.fechafin.slice(0, 16) : '';

  // Tomar la primera configuración (si existe) para mostrar en el form
  const config = prode.configuraciones && prode.configuraciones.length > 0
    ? prode.configuraciones[0]
    : { cantidad_obligatorias: 0, cantidad_opcionales: 0, cantidad_suplentes: 0 };

  form.value.configuracion = {
    cantidad_obligatorias: config.cantidad_obligatorias,
    cantidad_opcionales: config.cantidad_opcionales,
    cantidad_suplentes: config.cantidad_suplentes,
  };

  form.value.carreras = prode.carreras?.map(c => ({
    id: c.id,
    obligatoria: c.pivot?.obligatoria || false,
  })) || [];

  showForm.value = true;
}

function eliminarProde(id) {
  if (confirm('¿Eliminar prode?')) {
    fetch(`/prode-caballos/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    }).then(() => cargarProdes());
  }
}

function resetForm() {
  form.value = {
    nombre: '',
    precio: 0,
    fechafin: '',
    configuracion: {
      cantidad_obligatorias: 0,
      cantidad_opcionales: 0,
      cantidad_suplentes: 0,
    },
    carreras: [],
  };
}

onMounted(() => {
  cargarProdes();
  cargarCarreras();
});
</script>

<style scoped>
@import url('https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap');
.modal-backdrop {
  position: fixed;
  top: 0;
  left: 0;
  right: 0;
  bottom: 0;
  background: rgba(0, 0, 0, 0.2);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 100;
}
.modal-content {
  background: white;
  padding: 2em;
  border-radius: 1em;
  min-width: 700px;
  max-height: 85vh;
  overflow-y: auto;
  font-family: 'Roboto', sans-serif;
}
.input {
  width: 100%;
  padding: 0.4em;
  border-radius: 0.5em;
  border: 1px solid #d1d5db;
  margin-bottom: 8px;
}
.carrera-row {
  display: flex;
  align-items: center;
  gap: 12px;
  margin-bottom: 8px;
}
.carrera-select {
  flex: 1;
}
.tabla {
  width: 100%;
  margin-top: 1em;
  border-collapse: collapse;
}
.tabla th,
.tabla td {
  border: 1px solid #e5e7eb;
  padding: 8px;
  vertical-align: top;
}
.btn,
.btn-secondary,
.btn-danger {
  border-radius: 0.5em;
  padding: 0.3em 1em;
  font-size: 1em;
  cursor: pointer;
  border: none;
}
.btn {
  background: #3b82f6;
  color: #fff;
}
.btn-secondary {
  background: #fbbf24;
  color: #222;
}
.btn-danger {
  background: #ef4444;
  color: #fff;
}
.mt-4 {
  margin-top: 1.5em;
}
.mb-2 {
  margin-bottom: 0.7em;
}
.mb-3 {
  margin-bottom: 1.2em;
}
.mb-4 {
  margin-bottom: 1.5em;
}
.btn-small {
  padding: 0.2em 0.5em;
  font-size: 0.8em;
  line-height: 1;
}
.ml-2 {
  margin-left: 0.5em;
}
</style>
