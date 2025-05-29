<template>
  <div class="formulario-prode-page">
    <button class="volver-btn" @click="volver" aria-label="Volver">
      ← Volver
    </button>
    <div class="formulario-prode-content">
      <h2 class="form-title">{{ prode?.nombre ?? 'Cargando...' }}</h2>
      <div v-if="loading" class="form-loading">Cargando datos del prode...</div>
      <div v-else-if="error" class="form-error">{{ error }}</div>
      <form v-else @submit.prevent="enviarFormulario" class="form-main" novalidate>
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
        <div class="form-carreras">
          <h3 class="form-section-title">Tus pronósticos:</h3>
          <div
            v-for="(carrera, i) in prode.carreras ?? []"
            :key="carrera.id"
            class="carrera-group"
          >
            <label class="carrera-label">
              {{ i + 1 }}. {{ carrera.nombre }}
              <span v-if="carrera.obligatoria" class="tag-obligatoria">Obligatoria</span>
              <span v-else class="tag-opcional">Opcional</span>
            </label>
            <select
              class="select-caballo"
              v-model="form.pronosticos[i].caballo_id"
              :required="carrera.obligatoria"
            >
              <option value="" disabled>Elegir caballo</option>
              <option
                v-for="caballo in carrera.caballos"
                :key="caballo.id"
                :value="caballo.id"
              >
                {{ caballo.nombre }}
              </option>
            </select>
            <label class="suplente-label" v-if="!carrera.obligatoria">
              <input
                type="checkbox"
                v-model="form.pronosticos[i].es_suplente"
                :disabled="form.pronosticos[i].caballo_id !== ''"
              />
              Suplente
            </label>
          </div>
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
import { ref, reactive, watch } from "vue";
import './FormularioProde.css';

const props = defineProps({
  id: { type: Number, required: true },
});

const emit = defineEmits(["cerrar", "guardado"]);

const prode = ref(null);
const loading = ref(true);
const error = ref("");
const serverError = ref("");

// Formulario con datos y pronósticos
const form = reactive({
  nombre: "",
  dni: "",
  celular: "",
  forma_pago: "",
  pronosticos: [],
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
    form.pronosticos = data.carreras.map((c) => ({
      carrera_id: c.id,
      caballo_id: "",
      es_suplente: false,
    }));
  } catch (e) {
    error.value = e.message;
  } finally {
    loading.value = false;
  }
};

watch(
  () => props.id,
  (newId) => {
    if (newId) cargarProde(newId);
  },
  { immediate: true }
);

function volver() {
  emit("cerrar");
  resetForm();
}

function resetForm() {
  form.nombre = "";
  form.dni = "";
  form.celular = "";
  form.forma_pago = "";
  form.pronosticos = [];
}

function validarFormulario() {
  if (!form.nombre || !form.dni || !form.celular || !form.forma_pago) return false;
  if (!prode.value) return false;
  const carrerasObligatorias = prode.value.carreras.filter((c) => c.obligatoria);
  const carrerasOpcionales = prode.value.carreras.filter((c) => !c.obligatoria);
  const obligatoriasValidas = carrerasObligatorias.every((carrera) => {
    const pronostico = form.pronosticos.find((p) => p.carrera_id === carrera.id);
    return pronostico && pronostico.caballo_id !== "";
  });
  if (!obligatoriasValidas) return false;
  const cantidadOpcionalesRequeridas = prode.value.configuracion?.cantidad_opcionales ?? 0;
  const cantidadSuplentesRequeridos = prode.value.configuracion?.cantidad_suplentes ?? 0;
  const opcionalesElegidos = form.pronosticos.filter(
    (p) => p.caballo_id !== "" && carrerasOpcionales.some((c) => c.id === p.carrera_id)
  ).length;
  if (opcionalesElegidos !== cantidadOpcionalesRequeridas) return false;
  const opcionalesNoElegidosIds = carrerasOpcionales
    .map((c) => c.id)
    .filter(
      (id) =>
        !form.pronosticos.some(
          (p) => p.carrera_id === id && p.caballo_id !== ""
        )
    );
  const suplentesElegidos = form.pronosticos.filter(
    (p) =>
      p.es_suplente &&
      opcionalesNoElegidosIds.includes(p.carrera_id) &&
      p.caballo_id !== ""
  ).length;
  if (suplentesElegidos !== cantidadSuplentesRequeridos) return false;
  return true;
}

async function enviarFormulario() {
  serverError.value = "";
  if (!validarFormulario()) {
    serverError.value = "Completá todos los campos y pronósticos obligatorios correctamente.";
    return;
  }
  const payload = {
    prode_caballo_id: prode.value.id,
    nombre: form.nombre,
    dni: form.dni,
    celular: form.celular,
    forma_pago: form.forma_pago,
    pronosticos: form.pronosticos.map((p) => ({
      carrera_id: p.carrera_id,
      caballo_id: p.caballo_id,
      es_suplente: p.es_suplente || false,
    })),
  };
  try {
    const res = await fetch("/formularios", {
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
