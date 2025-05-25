<template>
  <div class="container mx-auto py-8">
    <h1 class="text-2xl font-bold mb-4">Prodes disponibles</h1>
    <div class="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
      <div v-for="prode in prodes" :key="prode.id" class="bg-white p-4 rounded shadow cursor-pointer hover:bg-gray-100" @click="selectProde(prode)">
        <h2 class="font-semibold text-xl">{{ prode.nombre }}</h2>
        <div>Precio: ${{ prode.precio }}</div>
        <div>Finaliza: {{ prode.fechafin }}</div>
        <div class="text-sm text-gray-500">Carreras: {{ prode.carreras.length }}</div>
      </div>
    </div>
    <ProdeDetalle v-if="prodeSeleccionado" :prode="prodeSeleccionado" @cerrar="prodeSeleccionado=null"/>
  </div>
</template>

<script setup>
import { ref, onMounted } from 'vue'
import ProdeDetalle from './ProdeDetalle.vue'

const prodes = ref([])
const prodeSeleccionado = ref(null)

const selectProde = (prode) => {
  prodeSeleccionado.value = prode
}

onMounted(async () => {
  const res = await fetch('/prodes')
  prodes.value = await res.json()
})
</script>
