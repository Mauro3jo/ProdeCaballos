<template>
  <div class="modal-backdrop">
    <div class="modal-content">
      <h2 class="mb-4">Caballos</h2>
      <button class="btn mb-2" @click="showForm = !showForm">{{ showForm ? 'Cancelar' : 'Nuevo Caballo' }}</button>

      <form v-if="showForm" @submit.prevent="guardarCaballo" class="mb-3">
        <input v-model="form.nombre" placeholder="Nombre" required class="input mb-2" />
        <input v-model="form.descripcion" placeholder="Descripción" class="input mb-2" />
        <button type="submit" class="btn">{{ editId ? 'Actualizar' : 'Guardar' }}</button>
      </form>

      <table class="tabla">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="caballo in caballos" :key="caballo.id">
            <td>{{ caballo.nombre }}</td>
            <td>{{ caballo.descripcion }}</td>
            <td>
              <button @click="editarCaballo(caballo)" class="btn-secondary">Editar</button>
              <button @click="eliminarCaballo(caballo.id)" class="btn-danger">Eliminar</button>
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

const caballos = ref([]);
const showForm = ref(false);
const form = ref({ nombre: '', descripcion: '' });
const editId = ref(null);

function getCsrfToken() {
  return document.querySelector('meta[name="csrf-token"]').getAttribute('content');
}

function cargarCaballos() {
  fetch('/caballos')
    .then((r) => r.json())
    .then((data) => (caballos.value = data));
}

function guardarCaballo() {
  const url = editId.value ? `/caballos/${editId.value}` : '/caballos';
  const method = editId.value ? 'PUT' : 'POST';
  fetch(url, {
    method,
    headers: {
      'Content-Type': 'application/json',
      'X-CSRF-TOKEN': getCsrfToken(),
    },
    body: JSON.stringify(form.value),
  })
    .then((response) => {
      if (!response.ok) throw new Error('Error en la petición');
      return response.json();
    })
    .then(() => {
      cargarCaballos();
      form.value = { nombre: '', descripcion: '' };
      showForm.value = false;
      editId.value = null;
    })
    .catch((error) => {
      alert('Error al guardar: ' + error.message);
    });
}

function editarCaballo(caballo) {
  showForm.value = true;
  editId.value = caballo.id;
  form.value = { ...caballo };
}

function eliminarCaballo(id) {
  if (confirm('¿Eliminar caballo?')) {
    fetch(`/caballos/${id}`, {
      method: 'DELETE',
      headers: {
        'X-CSRF-TOKEN': getCsrfToken(),
      },
    }).then(() => cargarCaballos());
  }
}

onMounted(cargarCaballos);
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
  min-width: 500px;
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
.tabla {
  width: 100%;
  margin-top: 1em;
  border-collapse: collapse;
}
.tabla th,
.tabla td {
  border: 1px solid #e5e7eb;
  padding: 8px;
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
</style>
