<template>
  <div class="prodes-backdrop">
    <div class="prodes-content">
      <!-- BLOQUE DE REGLAS ARRIBA -->
      <div class="prode-reglas-box">
        <h3 class="prode-reglas-title">¿Qué es el PRODE DEL TURF?</h3>
        <p class="prode-reglas-text">
          El Prode del turf es un juego de apuestas donde tienes que elegir 10 caballos qué pueden GANAR el fin de semana y tienes que elegir 3 caballos suplentes para reemplazar los caballos que no pudieran correr. De esta forma podés armar un PRODE para ganar.
        </p>
        <h3 class="prode-reglas-title">Objetivo del Juego</h3>
        <p class="prode-reglas-text">
          El objetivo es acertar en 10 carreras que elijas. <br />
          * El valor del Prode es de $10.000 <br />
          * Si aciertas en 10 carreras, ganás el Prode que equivale a $1.000.000. <br />
          * Si NO aciertas a las 10 carreras, pero tenés más aciertos que otros jugadores, ganás $300.000. <br />
          * Si hay 2 o más ganadores, el premio se reparte de manera proporcional. <br />
          * Si hay 10 aciertos, el "sale o sale" de $300.000 no se entrega.
        </p>
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
          <!-- Imagen según tipo de prode -->
          <img
            :src="getProdeImg(prode.tipo)"
            alt="Logo del prode"
            class="prode-img"
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
    return `${imagesPath}/ProdeXPuntos.jpg`;
  }
  // Por defecto (libre o undefined)
  return `${imagesPath}/ProdeLibre.jpg`;
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
