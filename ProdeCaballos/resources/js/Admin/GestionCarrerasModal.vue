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
        <input v-model="form.hipico" placeholder="Hípico" class="input mb-2" />
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
              {{ caballo.nombre }}<span v-if="caballo.descripcion"> ({{ caballo.descripcion }})</span>
            </option>
          </select>

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
            <th>Hípico</th>
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
            <td>{{ carrera.hipico }}</td>
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
    hipico: form.value.hipico,
    fecha: form.value.fecha,
    estado: form.value.estado,
  };

  if (editId.value) {
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
  form.value.hipico = carrera.hipico;
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
    hipico: '',
    fecha: '',
    estado: 'ACTIVA',
    caballos: [],
    numeros: [],
  };
}

onMounted(cargarCarreras);
</script>
