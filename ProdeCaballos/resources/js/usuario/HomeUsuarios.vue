<template>
  <div class="prodes-backdrop">
    <div class="prodes-content">
      <!-- BLOQUE DE REGLAS ARRIBA -->
      <div class="prode-reglas-box">
        <h3 class="prode-reglas-title">¿Qué es el PRODE DEL TURF?</h3>
        <p class="prode-reglas-text">
          El Prode del turf es un juego de apuestas donde tienes que elegir 10 caballos qué pueden GANAR el fin de semana y tienes que elegir 3 caballos suplentes para reemplazar los caballos que no pudieran correr. De esta forma podés armar un PRODE para ganar.
        </p>
        <h3 class="prode-reglas-title">Cómo Jugar</h3>
        <ol class="prode-reglas-list">
          <li><span class="prode-reglas-num">1.</span> Elegir el ganador de 10 carreras del fin de semana disponibles en el formulario.</li>
          <li><span class="prode-reglas-num">2.</span> Elegir 3 caballos suplentes para reemplazar algún caballo que no CORRE debido a lesiones, golpes, mal tiempo u otros inconvenientes.</li>
          <li><span class="prode-reglas-num">3.</span> Si algún caballo seleccionado NO CORRE, se reemplaza por el suplente 1. Si hay dos, se reemplazan por suplente 1 y 2, y así sucesivamente.</li>
          <li><span class="prode-reglas-num">4.</span> No hay restricciones sobre los ganadores: podés elegir cualquier caballo que participe.</li>
          <li><span class="prode-reglas-num">5.</span> Una vez seleccionados tus 10 ganadores y 3 suplentes, completá el formulario con tus datos y enviá tu PRODE.</li>
          <li><span class="prode-reglas-num">6.</span> No hay un límite de PRODES que puedas hacer: jugá y combiná cuantas veces quieras.</li>
          <li><span class="prode-reglas-num">7.</span> Realizá el depósito correspondiente al CBU indicado.</li>
          <li><span class="prode-reglas-num">8.</span> Se te enviará el PRODE que jugaste.</li>
          <li><span class="prode-reglas-num">9.</span> El sábado por la mañana se enviará un archivo Excel con todos los PRODES vendidos.</li>
          <li><span class="prode-reglas-num">10.</span> Esperá que se disputen las carreras.</li>
          <li><span class="prode-reglas-num">11.</span> El pago a los ganadores se realizará al día siguiente de la última carrera del formulario.</li>
        </ol>
      </div>
      <!-- FIN BLOQUE DE REGLAS -->

      <h1 class="prodes-title">Prodes disponibles</h1>
      <div v-if="loading" class="prodes-loading">Cargando prodes...</div>
      <div v-else-if="error" class="prodes-error">{{ error }}</div>
      <div v-else class="prodes-grid">
        <div
          v-for="prode in prodesVigentes"
          :key="prode.id"
          class="prode-card"
          @click="abrirProde(prode.id)"
        >
          <!-- Imagen arriba, ancho completo -->
          <img
            :src="getProdeImg(prode.tipo)"
            alt="Logo del prode"
            class="prode-img-full"
          />
          <div class="prode-card-content">
            <div class="prode-nombre">{{ prode.nombre }}</div>
            <div class="prode-precio">
              Premio: ${{ Math.round(prode.premio).toLocaleString('es-AR') }}
            </div>
            <div class="prode-precio">
              Precio: ${{ Math.round(prode.precio).toLocaleString('es-AR') }}
            </div>
            <div class="prode-fecha">
              Finaliza: {{ formatFecha(prode.fechafin) }}
            </div>
          </div>
        </div>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, computed, onMounted } from 'vue';
import { useRouter } from 'vue-router';
import './HomeUsuarios.css';

const imagesPath = (import.meta.env.VITE_IMAGES_PUBLIC_PATH || '').replace(/\/$/, '');

function getProdeImg(tipo) {
  if (tipo === 'puntos') {
    return `${imagesPath}/ProdeXPuntos.svg`;
  }
  // Por defecto (libre o undefined)
  return `${imagesPath}/ProdeLibre.svg`;
}

function ahoraARG() {
  return new Date(new Date().toLocaleString("en-US", { timeZone: "America/Argentina/Buenos_Aires" }));
}

const prodes = ref([]);
const loading = ref(true);
const error = ref('');

const router = useRouter();

const prodesVigentes = computed(() => {
  const ahora = ahoraARG();
  return prodes.value.filter(p => {
    if (!p.fechafin) return true;
    const fin = new Date(p.fechafin);
    return fin > ahora;
  });
});

const formatFecha = (fecha) => {
  if (!fecha) return '';
  const d = new Date(fecha);
  return d.toLocaleString("es-AR", { timeZone: "America/Argentina/Buenos_Aires" });
};

function abrirProde(id) {
  router.push(`/prode/${id}`);
}

onMounted(async () => {
  try {
    loading.value = true;
    error.value = '';
    const res = await fetch('/formularios');
    if (!res.ok) throw new Error('No se pudo cargar la lista de prodes.');
    prodes.value = await res.json();
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
});
</script>
