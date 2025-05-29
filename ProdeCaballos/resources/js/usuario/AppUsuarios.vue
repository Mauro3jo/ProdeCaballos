<template>
  <div>
    <HomeUsuarios v-if="vista === 'lista'" @abrir-prode="abrirProde" />
    <FormularioProde
      v-else-if="vista === 'formulario'"
      :id="idSeleccionado"
      @cerrar="volverALista"
    />
  </div>
</template>

<script setup>
import { ref } from 'vue';
import HomeUsuarios from './HomeUsuarios.vue';
import FormularioProde from './FormularioProde.vue';

const vista = ref('lista');
const idSeleccionado = ref(null);

function abrirProde(id) {
  idSeleccionado.value = id;
  vista.value = 'formulario';
}
function volverALista() {
  vista.value = 'lista';
}
</script>
