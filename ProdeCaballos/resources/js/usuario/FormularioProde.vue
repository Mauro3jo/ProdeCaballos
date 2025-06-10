<template>
  <div class="formulario-prode-page">
    <button class="volver-btn" @click="volver" aria-label="Volver">← Volver</button>
    <div class="formulario-prode-content">
      <!-- Logo fijo arriba -->
      <div class="prode-form-img-wrap">
        <img :src="logoUrl" alt="Logo del prode" class="prode-form-img" />
      </div>

      <!-- Reglas debajo del logo -->
      <div class="prode-reglas-box">
        <h3 class="prode-reglas-title">Reglas del Prode</h3>
        <ol class="prode-reglas-list">
          <li v-for="(regla, idx) in reglas" :key="idx">
            <span class="prode-reglas-num">{{ idx + 1 }}.</span>
            <span>{{ regla }}</span>
          </li>
        </ol>
      </div>

      <form v-if="!prodeVencido" @submit.prevent="enviarFormulario" class="form-main" novalidate>
        <!-- Carreras obligatorias agrupadas por hipico -->
        <div v-if="carrerasObligatoriasAgrupadas.length" class="form-carreras">
          <h3 class="form-section-title">Carreras obligatorias</h3>
          <template v-for="grupo in carrerasObligatoriasAgrupadas" :key="grupo.hipico || 'sin-hipico-oblig'">
            <h3 class="form-section-title" style="margin-bottom: .45em;" v-if="grupo.hipico">
              {{ grupo.hipico }}
            </h3>
            <h3 class="form-section-title" style="margin-bottom: .45em;" v-else>
              (Sin hipódromo)
            </h3>
            <div v-for="(carrera, idx) in grupo.carreras" :key="carrera.id" class="carrera-group">
              <div class="label-carrera">
                {{ idx + 1 }}. {{ carrera.nombre }}
                <span class="tag-obligatoria">Obligatoria</span>
              </div>
              <div class="caballos-checkboxes-vertical">
                <label v-for="caballo in carrera.caballos" :key="caballo.id" class="caballo-checkbox">
                  <input
                    type="checkbox"
                    :checked="pronosticosObligatorias[carrera.id] === caballo.id"
                    @change="onCheckObligatoria(carrera.id, caballo.id)"
                  />
                  {{ caballo.nombre }}
                </label>
              </div>
            </div>
          </template>
        </div>

        <!-- Carreras opcionales agrupadas por hipico -->
        <div v-if="carrerasOpcionalesAgrupadas.length" class="form-carreras">
          <h3 class="form-section-title">
            Carreras disponibles (elegí {{ cantidadOpcionalesRequeridas }} de {{ carrerasOpcionales.length }})
          </h3>
          <template v-for="grupo in carrerasOpcionalesAgrupadas" :key="grupo.hipico || 'sin-hipico-opc'">
            <h3 class="form-section-title" style="margin-bottom: .45em;" v-if="grupo.hipico">
              {{ grupo.hipico }}
            </h3>
            <h3 class="form-section-title" style="margin-bottom: .45em;" v-else>
              (Sin hipódromo)
            </h3>
            <div v-for="(carrera, idx) in grupo.carreras" :key="carrera.id" class="carrera-group">
              <div class="label-carrera">
                {{ idx + 1 }}. {{ carrera.nombre }}
                <span class="tag-opcional">Opcional</span>
              </div>
              <div class="caballos-checkboxes-vertical">
                <label v-for="caballo in carrera.caballos" :key="caballo.id" class="caballo-checkbox">
                  <input
                    type="checkbox"
                    :checked="pronosticosOpcionales[carrera.id] === caballo.id"
                    @change="onCheckOpcional(carrera.id, caballo.id)"
                    :disabled="isOpcionalDisabled(carrera.id, caballo.id)"
                  />
                  {{ caballo.nombre }}
                </label>
              </div>
            </div>
          </template>
        </div>

        <!-- Suplentes -->
        <div v-if="cantidadSuplentesRequeridos > 0" class="form-carreras">
          <h3 class="form-section-title">Suplentes</h3>
          <div v-for="(suplente, idx) in suplentes" :key="idx" class="carrera-group suplente-group">
            <label class="label-carrera-select">Carrera suplente {{ idx + 1 }}:</label>
            <select v-model="suplentes[idx].carreraId" class="select-carrera" required>
              <option value="">Seleccionar carrera</option>
              <option v-for="carrera in carrerasOpcionalesNoUsadasEnSuplentes(idx)" :key="carrera.id" :value="carrera.id">
                {{ carrera.nombre }}
              </option>
            </select>
            <div v-if="suplente.carreraId" class="caballos-checkboxes-vertical">
              <label v-for="caballo in getCaballosDeCarrera(suplente.carreraId)" :key="caballo.id" class="caballo-checkbox">
                <input
                  type="checkbox"
                  :checked="suplente.caballoId === caballo.id"
                  @change="onCheckSuplente(idx, caballo.id)"
                />
                {{ caballo.nombre }}
              </label>
            </div>
          </div>
        </div>

        <!-- DATOS PERSONALES -->
        <div class="form-group">
          <input v-model="form.nombre" placeholder="Nombre y apellido" required class="input" />
        </div>
        <div class="form-group">
          <input v-model="form.alias" placeholder="Alias / CBU" required class="input" />
        </div>
        <div class="form-group">
          <input v-model="form.celular" placeholder="Celular" required class="input" />
        </div>
        <div class="form-group">
          <select v-model="form.forma_pago" required class="select-carrera">
            <option value="">Forma de pago</option>
            <option value="Efectivo">Efectivo</option>
            <option value="Transferencia">Transferencia</option>
          </select>
        </div>
        <div class="form-group" v-if="form.forma_pago === 'Transferencia'">
          <label class="mb-2"><b>Alias admin para la transferencia</b></label>
          <div>
            <label>
              <input type="radio" v-model="form.alias_admin" value="lafijacuadrera2025" />
              lafijacuadrera2025
            </label>
            <label style="margin-left: 1em;">
              <input type="radio" v-model="form.alias_admin" value="Studvecinaslindas" />
              Studvecinaslindas
            </label>
          </div>
        </div>
        <div v-if="serverError" class="form-error">{{ serverError }}</div>
        <button type="submit" class="btn-form" :disabled="!validarFormulario()">
          Enviar pronóstico
        </button>
      </form>
      <div v-else class="form-error">
        Este prode ya finalizó. No podés enviar pronóstico.
        <button class="btn-form mt-3" @click="volver">Volver al home</button>
      </div>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch, computed } from "vue";
import { useRouter } from 'vue-router';
import './FormularioProde.css';

const router = useRouter();

const logoUrl = import.meta.env.VITE_IMAGES_PUBLIC_PATH
  ? `${import.meta.env.VITE_IMAGES_PUBLIC_PATH.replace(/\/$/, '')}/Logo.jpg`
  : '/Logo.jpg';

const reglasFijas = [
  "La sentencia de cada carrera esta dada por el fallo oficial del hipico",
  "Los clásicos que hicieron PUESTA en el prode pierden solo suman un punto",
  "Los prodes que no están abonados no participan del prode",
  "Se enviará al grupo un Excel con todos los prodes vendidos",
  "Los clásicos que no se corren por razones particulares, accidentes o lluvia tienen como reemplazo un clásico suplente 1 y si este no se corre tiene un clásico suplente 2 y así sucesivamente.",
  "Las carreras suplentes entran en juego solo si las carreras jugadas en el prode no se corren",
  "Todos los clásicos son a las puertas.."
];

const props = defineProps({ id: { type: Number, required: true } });
const emit = defineEmits(["cerrar", "guardado"]);

const prode = ref(null);
const loading = ref(true);
const error = ref("");
const serverError = ref("");

const carrerasObligatorias = computed(() => prode.value?.carreras?.filter(c => c.obligatoria) || []);
const carrerasOpcionales = computed(() => prode.value?.carreras?.filter(c => !c.obligatoria) || []);
const cantidadOpcionalesRequeridas = computed(() => prode.value?.configuracion?.cantidad_opcionales || 0);
const cantidadSuplentesRequeridos = computed(() => prode.value?.configuracion?.cantidad_suplentes || 0);

const pronosticosObligatorias = reactive({});
const pronosticosOpcionales = reactive({});
const suplentes = reactive([]);

const prodeVencido = computed(() => {
  if (!prode.value?.fechafin) return false;
  const ahora = ahoraARG();
  return new Date(prode.value.fechafin) <= ahora;
});

// Agrupadores por hipico (nombre hipódromo)
function agruparPorHipico(carreras) {
  const grupos = {};
  carreras.forEach(c => {
    const key = c.hipico && c.hipico.trim() ? c.hipico : '';
    if (!grupos[key]) grupos[key] = [];
    grupos[key].push(c);
  });
  // Devuelve array [{ hipico, carreras }]
  return Object.entries(grupos)
    .sort(([a], [b]) => (a || '').localeCompare(b || '')) // ordena por nombre hipico asc
    .map(([hipico, carreras]) => ({ hipico, carreras }));
}

// Computed agrupadas
const carrerasObligatoriasAgrupadas = computed(() => agruparPorHipico(carrerasObligatorias.value));
const carrerasOpcionalesAgrupadas = computed(() => agruparPorHipico(carrerasOpcionales.value));

// Helpers originales
function ahoraARG() {
  return new Date(new Date().toLocaleString("en-US", { timeZone: "America/Argentina/Buenos_Aires" }));
}
function getCaballosDeCarrera(carreraId) {
  const carrera = prode.value?.carreras?.find(c => c.id === carreraId);
  return carrera?.caballos || [];
}
function onCheckObligatoria(carreraId, caballoId) {
  pronosticosObligatorias[carreraId] =
    pronosticosObligatorias[carreraId] === caballoId ? "" : caballoId;
}
function onCheckOpcional(carreraId, caballoId) {
  pronosticosOpcionales[carreraId] =
    pronosticosOpcionales[carreraId] === caballoId ? "" : caballoId;
}
function isOpcionalDisabled(carreraId, caballoId) {
  const cantidadElegidas = Object.values(pronosticosOpcionales).filter(v => v).length;
  return (
    !pronosticosOpcionales[carreraId] &&
    cantidadElegidas >= cantidadOpcionalesRequeridas.value
  );
}
function onCheckSuplente(idx, caballoId) {
  suplentes[idx].caballoId =
    suplentes[idx].caballoId === caballoId ? "" : caballoId;
}
function carrerasOpcionalesNoUsadasEnSuplentes(idx) {
  const usadasEnOpcionales = Object.keys(pronosticosOpcionales).filter(cid => pronosticosOpcionales[cid]);
  const usadasEnSuplentes = suplentes.map((s, i) => i !== idx ? s.carreraId : null).filter(Boolean);
  return carrerasOpcionales.value.filter(
    c => !usadasEnOpcionales.includes(String(c.id)) && !usadasEnSuplentes.includes(c.id)
  );
}

function parseReglasConNumeros(texto) {
  if (!texto) return [];
  texto = texto.replace(/^Reglas\s*/i, "");
  let reglas = texto.split(/\d+\.\s*/).filter(x => x.trim() !== "");
  if (reglas.length === 1 && texto.indexOf("1.") !== 0) {
    reglas = texto.split(/\n+/).filter(x => x.trim() !== "");
  }
  return reglas;
}

const reglas = computed(() => {
  if (prode.value?.reglas) {
    return parseReglasConNumeros(prode.value.reglas);
  }
  return reglasFijas;
});

const form = reactive({
  nombre: "",
  alias: "",
  alias_admin: "",
  celular: "",
  forma_pago: "",
});

const cargarProde = async (id) => {
  loading.value = true;
  error.value = "";
  try {
    const res = await fetch("/formularios/detalle", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
      },
      body: JSON.stringify({ id }),
    });
    if (!res.ok) throw new Error("No se pudo cargar el detalle del prode");
    const data = await res.json();
    prode.value = data;

    Object.keys(pronosticosObligatorias).forEach(key => delete pronosticosObligatorias[key]);
    carrerasObligatorias.value.forEach(c => pronosticosObligatorias[c.id] = "");

    Object.keys(pronosticosOpcionales).forEach(key => delete pronosticosOpcionales[key]);
    carrerasOpcionales.value.forEach(c => pronosticosOpcionales[c.id] = "");

    suplentes.length = 0;
    for (let i = 0; i < cantidadSuplentesRequeridos.value; i++) {
      suplentes.push({ carreraId: "", caballoId: "" });
    }

    form.nombre = "";
    form.alias = "";
    form.alias_admin = "";
    form.celular = "";
    form.forma_pago = "";
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
};

watch(
  () => props.id,
  (newId) => { if (newId) cargarProde(newId); },
  { immediate: true }
);

function volver() {
  router.push('/');
}

function validarFormulario() {
  if (prodeVencido.value) return false;
  if (carrerasObligatorias.value.some(c => !pronosticosObligatorias[c.id])) return false;
  const elegidas = Object.keys(pronosticosOpcionales).filter(cid => pronosticosOpcionales[cid]);
  if (elegidas.length !== cantidadOpcionalesRequeridas.value) return false;
  const suplentesIds = suplentes.map(s => s.carreraId);
  if (
    suplentes.length !== cantidadSuplentesRequeridos.value ||
    suplentes.some(s => !s.carreraId || !s.caballoId) ||
    new Set(suplentesIds).size !== suplentesIds.length
  ) return false;
  if (!form.nombre || !form.alias || !form.celular || !form.forma_pago) return false;
  if (!["Efectivo", "Transferencia"].includes(form.forma_pago)) return false;
  if (form.forma_pago === "Transferencia" && !form.alias_admin) return false;
  return true;
}

async function enviarFormulario() {
  serverError.value = "";
  if (prodeVencido.value) {
    serverError.value = "Este prode ya finalizó. No podés enviar pronóstico.";
    return;
  }
  if (!validarFormulario()) {
    serverError.value = "Completá todos los campos y pronósticos correctamente.";
    return;
  }
  const pronosticos = [
    ...carrerasObligatorias.value.map(c => ({
      carrera_id: c.id,
      caballo_id: pronosticosObligatorias[c.id],
      es_suplente: false,
    })),
    ...carrerasOpcionales.value
      .filter(c => pronosticosOpcionales[c.id])
      .map(c => ({
        carrera_id: c.id,
        caballo_id: pronosticosOpcionales[c.id],
        es_suplente: false,
      })),
    ...suplentes.map(s => ({
      carrera_id: s.carreraId,
      caballo_id: s.caballoId,
      es_suplente: true,
    })),
  ];
  const payload = {
    prode_caballo_id: prode.value.id,
    nombre: form.nombre,
    alias: form.alias,
    alias_admin: form.forma_pago === "Transferencia" ? form.alias_admin : null,
    celular: form.celular,
    forma_pago: form.forma_pago,
    pronosticos,
  };

  try {
    const res = await fetch("/api/guardar-formulario", {
      method: "POST",
      headers: {
        "Content-Type": "application/json",
        "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
      },
      body: JSON.stringify(payload),
    });
    const result = await res.json();
    if (!res.ok) throw new Error(result.error || "Error al guardar el formulario");
    alert("¡Pronóstico enviado correctamente!");
    await cargarProde(props.id);
    emit("guardado");
  } catch (e) {
    serverError.value = e.message;
  }
}
</script>
