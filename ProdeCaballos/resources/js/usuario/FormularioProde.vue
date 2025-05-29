<template>
  <div class="formulario-prode-page">
    <button class="volver-btn" @click="volver" aria-label="Volver">← Volver</button>
    <div class="formulario-prode-content">
      <!-- FORMULARIO COMPLETO -->
      <form @submit.prevent="enviarFormulario" class="form-main" novalidate>
        <!-- Carreras obligatorias -->
        <div v-if="carrerasObligatorias.length" class="form-carreras">
          <h3 class="form-section-title">Carreras obligatorias</h3>
          <div v-for="(carrera, idx) in carrerasObligatorias" :key="carrera.id" class="carrera-group">
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
        </div>

        <!-- Carreras opcionales -->
        <div v-if="carrerasOpcionales.length" class="form-carreras">
          <h3 class="form-section-title">
            Carreras opcionales (elegí {{ cantidadOpcionalesRequeridas }} de {{ carrerasOpcionales.length }})
          </h3>
          <div v-for="(carrera, idx) in carrerasOpcionales" :key="carrera.id" class="carrera-group">
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
          <input v-model="form.dni" placeholder="DNI" required class="input" />
        </div>
        <div class="form-group">
          <input v-model="form.celular" placeholder="Celular" required class="input" />
        </div>
        <div class="form-group">
          <input v-model="form.forma_pago" placeholder="Forma de pago" required class="input" />
        </div>

        <div v-if="serverError" class="form-error">{{ serverError }}</div>
        <button type="submit" class="btn-form" :disabled="!validarFormulario()">
          Enviar pronóstico
        </button>
      </form>
    </div>
  </div>
</template>


<script setup>
import { ref, reactive, watch, computed } from "vue";
import './FormularioProde.css';

const props = defineProps({ id: { type: Number, required: true } });
const emit = defineEmits(["cerrar", "guardado"]);

const prode = ref(null);
const loading = ref(true);
const error = ref("");
const serverError = ref("");

// --- LOGICA REACTIVA PRONOSTICOS ---

const carrerasObligatorias = computed(() => prode.value?.carreras?.filter(c => c.obligatoria) || []);
const carrerasOpcionales = computed(() => prode.value?.carreras?.filter(c => !c.obligatoria) || []);
const cantidadOpcionalesRequeridas = computed(() => prode.value?.configuracion?.cantidad_opcionales || 0);
const cantidadSuplentesRequeridos = computed(() => prode.value?.configuracion?.cantidad_suplentes || 0);

// Obligatorias: { [carreraId]: caballoId }
const pronosticosObligatorias = reactive({});
// Opcionales: { [carreraId]: caballoId }
const pronosticosOpcionales = reactive({});
const suplentes = reactive([]);

// Helpers
function getCaballosDeCarrera(carreraId) {
  const carrera = prode.value?.carreras?.find(c => c.id === carreraId);
  return carrera?.caballos || [];
}

// SOLO UNO por carrera (desmarca otros check al seleccionar uno)
function onCheckObligatoria(carreraId, caballoId) {
  pronosticosObligatorias[carreraId] =
    pronosticosObligatorias[carreraId] === caballoId ? "" : caballoId;
}
function onCheckOpcional(carreraId, caballoId) {
  pronosticosOpcionales[carreraId] =
    pronosticosOpcionales[carreraId] === caballoId ? "" : caballoId;
}
// Solo X opcionales permitidas
function isOpcionalDisabled(carreraId, caballoId) {
  const cantidadElegidas = Object.values(pronosticosOpcionales).filter(v => v).length;
  // Si ya elegí en esta carrera, no se deshabilita nunca
  return (
    !pronosticosOpcionales[carreraId] &&
    cantidadElegidas >= cantidadOpcionalesRequeridas.value
  );
}
// Suplentes: select carrera + solo uno por caballo
function onCheckSuplente(idx, caballoId) {
  suplentes[idx].caballoId =
    suplentes[idx].caballoId === caballoId ? "" : caballoId;
}
// Para suplentes: carreras opcionales NO usadas ni antes ni en otros suplentes
function carrerasOpcionalesNoUsadasEnSuplentes(idx) {
  const usadasEnOpcionales = Object.keys(pronosticosOpcionales).filter(cid => pronosticosOpcionales[cid]);
  const usadasEnSuplentes = suplentes.map((s, i) => i !== idx ? s.carreraId : null).filter(Boolean);
  return carrerasOpcionales.value.filter(
    c => !usadasEnOpcionales.includes(String(c.id)) && !usadasEnSuplentes.includes(c.id)
  );
}

const form = reactive({
  nombre: "",
  dni: "",
  celular: "",
  forma_pago: "",
});

// CARGA Y RESET
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

    // Inicializar obligatorias
    Object.keys(pronosticosObligatorias).forEach(key => delete pronosticosObligatorias[key]);
    carrerasObligatorias.value.forEach(c => pronosticosObligatorias[c.id] = "");

    // Inicializar opcionales
    Object.keys(pronosticosOpcionales).forEach(key => delete pronosticosOpcionales[key]);
    carrerasOpcionales.value.forEach(c => pronosticosOpcionales[c.id] = "");

    // Inicializar suplentes
    suplentes.length = 0;
    for (let i = 0; i < cantidadSuplentesRequeridos.value; i++) {
      suplentes.push({ carreraId: "", caballoId: "" });
    }

    // Reset datos usuario
    form.nombre = "";
    form.dni = "";
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
  emit("cerrar");
}

function validarFormulario() {
  // Obligatorias: todas completas
  if (carrerasObligatorias.value.some(c => !pronosticosObligatorias[c.id])) return false;
  // Opcionales: solo la cantidad justa, y solo en carreras diferentes
  const elegidas = Object.keys(pronosticosOpcionales).filter(cid => pronosticosOpcionales[cid]);
  if (elegidas.length !== cantidadOpcionalesRequeridas.value) return false;
  // Suplentes: todos completos y distintas carreras
  const suplentesIds = suplentes.map(s => s.carreraId);
  if (
    suplentes.length !== cantidadSuplentesRequeridos.value ||
    suplentes.some(s => !s.carreraId || !s.caballoId) ||
    new Set(suplentesIds).size !== suplentesIds.length
  ) return false;
  // Datos personales
  if (!form.nombre || !form.dni || !form.celular || !form.forma_pago) return false;
  return true;
}

async function enviarFormulario() {
  serverError.value = "";
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
    dni: form.dni,
    celular: form.celular,
    forma_pago: form.forma_pago,
    pronosticos,
  };

  // LOG para ver qué se manda
  console.log("Payload enviado al backend:", JSON.stringify(payload, null, 2));

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
    volver();
    emit("guardado");
  } catch (e) {
    serverError.value = e.message;
  }
}


</script>
