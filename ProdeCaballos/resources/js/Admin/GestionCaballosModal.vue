<template>
  <div class="modal-backdrop">
    <!-- Botón cerrar en esquina superior derecha del backdrop -->
    <button class="cerrar-btn" @click="$emit('close')" aria-label="Cerrar modal">×</button>

    <div class="modal-content">
      <h2 class="mb-4">Caballos</h2>
      <button class="btn mb-2" @click="showForm = !showForm">{{ showForm ? 'Cancelar' : 'Nuevo Caballo' }}</button>

      <form v-if="showForm" @submit.prevent="guardarCaballo" class="mb-3">
        <input v-model="form.nombre" placeholder="Nombre" required class="input mb-2" />
        <input v-model="form.descripcion" placeholder="Descripción" class="input mb-2" />
        <button type="submit" class="btn">{{ editId ? 'Actualizar' : 'Guardar' }}</button>
      </form>

      <input
        type="text"
        v-model="busqueda"
        placeholder="Buscar por nombre..."
        class="input mb-3"
      />

      <table class="tabla" v-if="caballosFiltrados.length">
        <thead>
          <tr>
            <th>Nombre</th>
            <th>Descripción</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody>
          <tr v-for="caballo in caballosFiltrados" :key="caballo.id">
            <td>{{ caballo.nombre }}</td>
            <td>{{ caballo.descripcion }}</td>
            <td>
              <button @click="editarCaballo(caballo)" class="btn-secondary">Editar</button>
              <button @click="eliminarCaballo(caballo.id)" class="btn-danger">Eliminar</button>
            </td>
          </tr>
        </tbody>
      </table>

      <div v-else class="sin-resultados">No se encontraron caballos.</div>

      <button class="btn-secondary mt-4" @click="$emit('close')">Cerrar</button>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';

const caballos = ref([]);
const showForm = ref(false);
const form = ref({ nombre: '', descripcion: '' });
const editId = ref(null);
const busqueda = ref('');

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

const caballosFiltrados = computed(() => {
  const texto = busqueda.value.toLowerCase();
  return caballos.value.filter((c) =>
    c.nombre.toLowerCase().includes(texto)
  );
});

onMounted(cargarCaballos);
</script>

<style scoped src="./CaballosModal.css"></style>
