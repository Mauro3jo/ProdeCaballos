<template>
  <div class="modal-backdrop">
    <!-- Botón cerrar arriba a la derecha fuera del modal -->
    <button class="cerrar-btn" @click="$emit('close')" aria-label="Cerrar modal">×</button>

    <div class="modal-content">
      <h2 class="mb-4">Carreras</h2>
      <button class="btn mb-2" @click="showForm = !showForm">
        {{ showForm ? 'Cancelar' : (editId ? 'Editar Carrera' : 'Nueva Carrera') }}
      </button>

      <form v-if="showForm" @submit.prevent="guardarCarrera" class="mb-3">
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
          <option>Hipico el fragueño  el mojón</option>
          <option>Hipico la victoria el Mojón</option>
          <option>Hipico el doradillo</option>
          <option>Hipico el quebrachal</option>
          <option>Hipico guardamonte</option>
        </select>

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

        <div
          v-for="(caballoId, index) in form.caballos"
          :key="index"
          class="caballo-numero-row mb-2"
        >
          <select v-model="form.caballos[index]" class="input select-caballo" required>
            <option disabled value="">-- Selecciona caballo --</option>
            <option v-for="caballo in todosCaballos" :key="caballo.id" :value="caballo.id">
              {{ caballo.nombre }}
            </option>
          </select>
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
            <th>Hípico</th>
            <th>Fecha</th>
            <th>Estado</th>
            <th>Caballos</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="carrera in carreras" :key="carrera.id">
            <td>{{ carrera.nombre }}</td>
            <td>{{ carrera.descripcion }}</td>
            <td>{{ carrera.hipico }}</td>
            <td>{{ carrera.fecha }}</td>
            <td>{{ carrera.estado }}</td>
            <td>
              <ul>
                <li v-for="caballo in carrera.caballos" :key="caballo.id">
                  {{ caballo.nombre }}
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
import './GestionCarrerasModal.css';

const carreras = ref([]);
const todosCaballos = ref([]);
const showForm = ref(false);
const editId = ref(null);

const form = ref({
  nombre: '',
  descripcion: '',
  hipico: '',
  fecha: '',
  estado: 'ACTIVA',
  caballos: [],
});

function cargarCarreras() {
  fetch('/carreras')
    .then(r => r.json())
    .then(data => {
      carreras.value = data.carreras || data;
      todosCaballos.value = data.caballos || [];
    });
}

function agregarCaballo() {
  form.value.caballos.push('');
}

function removerCaballo(index) {
  form.value.caballos.splice(index, 1);
}

function guardarCarrera() {
  if (form.value.caballos.length < 2) {
    alert('Debés asignar al menos 2 caballos');
    return;
  }

  const payload = {
    nombre: form.value.nombre,
    descripcion: form.value.descripcion,
    hipico: form.value.hipico,
    fecha: form.value.fecha,
    estado: form.value.estado,
    caballos: form.value.caballos,
  };

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
    .then(r => {
      if (!r.ok) throw new Error('Error guardando la carrera');
      return r.json();
    })
    .then(() => {
      cargarCarreras();
      resetForm();
      showForm.value = false;
      editId.value = null;
    })
    .catch(e => alert(e.message));
}

function editarCarrera(carrera) {
  editId.value = carrera.id;
  form.value.nombre = carrera.nombre;
  form.value.descripcion = carrera.descripcion;
  form.value.hipico = carrera.hipico;
  form.value.fecha = carrera.fecha ? carrera.fecha.slice(0, 16) : '';
  form.value.estado = carrera.estado;
  form.value.caballos = carrera.caballos.map(c => c.id);
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
    hipico: '',
    fecha: '',
    estado: 'ACTIVA',
    caballos: [],
  };
}

onMounted(cargarCarreras);
</script>
