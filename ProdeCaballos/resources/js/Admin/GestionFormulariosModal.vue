<template>
  <div class="modal-backdrop">
    <div class="modal-content">
      <h2 class="mb-4">Prodes Caballos</h2>

      <button class="btn mb-3" @click="showForm = !showForm">
        {{ showForm ? 'Cancelar' : (editId ? 'Editar Prode' : 'Nuevo Prode') }}
      </button>

      <form v-if="showForm" @submit.prevent="guardarProde" class="mb-4" enctype="multipart/form-data">
        <input v-model="form.nombre" placeholder="Nombre" required class="input mb-2" />
        <input v-model.number="form.precio" type="number" min="0" placeholder="Precio" required class="input mb-2" />
        <input v-model.number="form.premio" type="number" min="0" placeholder="Premio" required class="input mb-2" />
        <input v-model="form.fechafin" type="datetime-local" placeholder="Fecha Fin" required class="input mb-2" />

        <!-- Foto (opcional) -->
        <!--
        <label class="mb-1 font-semibold">Foto (opcional)</label>
        <input type="file" @change="onFotoChange" accept="image/*" class="input mb-2" />
        -->

        <!-- Reglas (opcional) -->
        <!--
        <label class="mb-1 font-semibold">Reglas del Prode (opcional)</label>
        <textarea v-model="form.reglas" placeholder="Reglas..." class="input mb-2"></textarea>
        -->

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
              v-for="carrera in carrerasDisponibles(index)"
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
            <th>Premio</th>
            <th>Fecha Fin</th>
            <!-- <th>Foto</th> -->
            <!-- <th>Reglas</th> -->
            <th>Configuración (Obl / Opc / Sup)</th>
            <th>Carreras</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="prode in prodes" :key="prode.id">
            <td>{{ prode.nombre }}</td>
            <td>{{ prode.precio }}</td>
            <td>{{ prode.premio }}</td>
            <td>{{ prode.fechafin }}</td>
            <!--
            <td>
              <img v-if="prode.foto_url" :src="prode.foto_url" alt="Foto" style="max-width:50px;max-height:50px;" />
              <span v-else>-</span>
            </td>
            <td>
              <span v-if="prode.reglas">{{ prode.reglas }}</span>
              <span v-else>-</span>
            </td>
            -->
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
import './ModalProdesCaballos.css';

const prodes = ref([]);
const todasCarreras = ref([]);
const showForm = ref(false);
const editId = ref(null);
const fotoFile = ref(null);

const form = ref({
  nombre: '',
  precio: 0,
  premio: 0,
  fechafin: '',
  reglas: '',
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

function carrerasDisponibles(indexActual) {
  // Ids seleccionados en otros selects
  const idsSeleccionados = form.value.carreras
    .map((c, idx) => idx !== indexActual ? c.id : null)
    .filter(id => !!id);
  // Filtrá las carreras para que no se repitan
  return todasCarreras.value.filter(c => !idsSeleccionados.includes(c.id));
}

function agregarCarrera() {
  form.value.carreras.push({ id: '', obligatoria: false });
}
function removerCarrera(index) {
  form.value.carreras.splice(index, 1);
}
function onFotoChange(e) {
  fotoFile.value = e.target.files[0] || null;
}
function guardarProde() {
  const url = editId.value ? `/prode-caballos/${editId.value}` : '/prode-caballos';
  const method = editId.value ? 'POST' : 'POST'; // Usa override _method para PUT

  const formData = new FormData();
  formData.append('nombre', form.value.nombre);
  formData.append('precio', form.value.precio);
  formData.append('premio', form.value.premio);
  formData.append('fechafin', form.value.fechafin);
  // formData.append('reglas', form.value.reglas || '');
  if (fotoFile.value) {
    formData.append('foto', fotoFile.value);
  }
  formData.append('configuracion[cantidad_obligatorias]', form.value.configuracion.cantidad_obligatorias);
  formData.append('configuracion[cantidad_opcionales]', form.value.configuracion.cantidad_opcionales);
  formData.append('configuracion[cantidad_suplentes]', form.value.configuracion.cantidad_suplentes);
  form.value.carreras.forEach((c, i) => {
    formData.append(`carreras[${i}][id]`, c.id);
    formData.append(`carreras[${i}][obligatoria]`, c.obligatoria ? 1 : 0);
  });
  if (editId.value) formData.append('_method', 'PUT');

  fetch(url, {
    method,
    headers: {
      'X-CSRF-TOKEN': document.querySelector('meta[name="csrf-token"]').getAttribute('content'),
    },
    body: formData,
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
      fotoFile.value = null;
    })
    .catch(e => alert(e.message));
}
function editarProde(prode) {
  editId.value = prode.id;
  form.value.nombre = prode.nombre;
  form.value.precio = prode.precio;
  form.value.premio = prode.premio;
  form.value.fechafin = prode.fechafin ? prode.fechafin.slice(0, 16) : '';
  // form.value.reglas = prode.reglas || '';

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
    premio: 0,
    fechafin: '',
    reglas: '',
    configuracion: {
      cantidad_obligatorias: 0,
      cantidad_opcionales: 0,
      cantidad_suplentes: 0,
    },
    carreras: [],
  };
  fotoFile.value = null;
}
onMounted(() => {
  cargarProdes();
  cargarCarreras();
});
</script>
