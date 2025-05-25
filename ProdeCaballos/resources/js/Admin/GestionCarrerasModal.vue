<template>
  <div class="modal-backdrop">
    <div class="modal-content">
      <h2 class="mb-4">Carreras</h2>
      <button class="btn mb-2" @click="showForm = !showForm">
        {{ showForm ? 'Cancelar' : (editId ? 'Editar Carrera' : 'Nueva Carrera') }}
      </button>

      <form v-if="showForm" @submit.prevent="guardarCarrera" class="mb-3">
        <input v-model="form.nombre" placeholder="Nombre" required class="input mb-2" />
        <input v-model="form.descripcion" placeholder="Descripción" class="input mb-2" />
        <input v-model="form.fecha" type="datetime-local" class="input mb-2" />

        <label class="mb-1 font-semibold">Estado</label>
        <input
          v-model="form.estado"
          class="input mb-2"
          :disabled="!editId"
          readonly="!editId"
        />
        <small v-if="!editId" class="text-sm text-gray-500">
          Estado se fija como ACTIVA al crear
        </small>

        <label class="mb-1 font-semibold">Seleccionar Caballos:</label>

        <div v-for="(caballoId, index) in form.caballos" :key="index" class="caballo-numero-row mb-2">
          <select v-model="form.caballos[index]" class="input select-caballo" required>
            <option disabled value="">-- Selecciona caballo --</option>
            <option v-for="caballo in todosCaballos" :key="caballo.id" :value="caballo.id">
              {{ caballo.nombre }}
            </option>
          </select>

          <!-- Mostrar input número sólo si estamos editando -->
          <input
            v-if="editId"
            type="number"
            min="1"
            v-model.number="form.numeros[index]"
            placeholder="Número"
            class="input numero-input"
            required
          />
          <button type="button" @click="removerCaballo(index)" class="btn-danger btn-small ml-2">X</button>
        </div>

        <button type="button" class="btn mb-3" @click="agregarCaballo()">Agregar Caballo</button>

        <button type="submit" class="btn">{{ editId ? 'Actualizar' : 'Guardar' }}</button>
      </form>

      <table class="tabla">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Caballos (Número)</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="carrera in carreras" :key="carrera.id">
            <td>{{ carrera.nombre }}</td>
            <td>{{ carrera.descripcion }}</td>
            <td>{{ carrera.fecha }}</td>
            <td>{{ carrera.estado }}</td>
            <td>
              <ul>
                <li v-for="caballo in carrera.caballos" :key="caballo.id">
                  {{ caballo.nombre }} ({{ caballo.pivot.numero }})
                </li>
              </ul>
            </td>
            <td>
              <button @click="editarCarrera(carrera)" class="btn-secondary">Editar</button>
              <button @click="eliminarCarrera(carrera.id)" class="btn-danger">Eliminar</button>
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

const carreras = ref([]);
const todosCaballos = ref([]);
const showForm = ref(false);
const editId = ref(null);

const form = ref({
  nombre: '',
  descripcion: '',
  fecha: '',
  estado: 'ACTIVA',
  caballos: [],
  numeros: [],
});

function cargarCarreras() {
  fetch('/carreras')
    .then((r) => r.json())
    .then((data) => {
      carreras.value = data.carreras || data;
      todosCaballos.value = data.caballos || [];
    });
}

function agregarCaballo() {
  form.value.caballos.push('');
  if (editId.value) {
    form.value.numeros.push(1);
  }
}

function removerCaballo(index) {
  form.value.caballos.splice(index, 1);
  if (editId.value) {
    form.value.numeros.splice(index, 1);
  }
}

function guardarCarrera() {
  if (form.value.caballos.length < 2) {
    alert('Debés asignar al menos 2 caballos');
    return;
  }

  let payload = {
    nombre: form.value.nombre,
    descripcion: form.value.descripcion,
    fecha: form.value.fecha,
    estado: form.value.estado,
  };

  if (editId.value) {
    // En edición enviamos caballos con números
    const caballosConNumero = {};
    for (let i = 0; i < form.value.caballos.length; i++) {
      const id = form.value.caballos[i];
      const numero = form.value.numeros[i];
      if (!id) {
        alert('Seleccioná todos los caballos');
        return;
      }
      caballosConNumero[id] = { numero };
    }
    payload.caballos = caballosConNumero;
  } else {
    // En creación enviamos solo ids
    payload.caballos = form.value.caballos;
  }

  const url = editId.value ? `/carreras/${editId.value}` : '/carreras';
  const method = editId.value ? 'PUT' : 'POST';

  fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: JSON.stringify(payload),
  })
    .then((r) => {
      if (!r.ok) throw new Error('Error guardando la carrera');
      return r.json();
    })
    .then(() => {
      cargarCarreras();
      resetForm();
      showForm.value = false;
      editId.value = null;
    })
    .catch((e) => alert(e.message));
}

function editarCarrera(carrera) {
  editId.value = carrera.id;
  form.value.nombre = carrera.nombre;
  form.value.descripcion = carrera.descripcion;
  form.value.fecha = carrera.fecha ? carrera.fecha.slice(0, 16) : '';
  form.value.estado = carrera.estado;

  form.value.caballos = [];
  form.value.numeros = [];
  carrera.caballos.forEach((c) => {
    form.value.caballos.push(c.id);
    form.value.numeros.push(c.pivot.numero || 1);
  });

  showForm.value = true;
}

function eliminarCarrera(id) {
  if (confirm('¿Eliminar carrera?')) {
    fetch(`/carreras/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
      },
    }).then(() => cargarCarreras());
  }
}

function resetForm() {
  form.value = {
    nombre: '',
    descripcion: '',
    fecha: '',
    estado: 'ACTIVA',
    caballos: [],
    numeros: [],
  };
}

onMounted(cargarCarreras);
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
.select-caballo {
  width: 70%;
  margin-right: 8px;
}
.numero-input {
  width: 20%;
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
.caballo-numero-row {
  display: flex;
  align-items: center;
  margin-bottom: 0.5em;
}
</style>
