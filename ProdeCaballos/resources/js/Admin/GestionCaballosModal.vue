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

<style scoped src="./CaballosModal.css"></style>
