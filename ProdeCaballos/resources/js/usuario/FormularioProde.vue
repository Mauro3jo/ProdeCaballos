<template>
  <div class="modal-backdrop" @click.self="cerrar">
    <div class="modal-content">
      <button class="cerrar-btn" @click="cerrar" aria-label="Cerrar modal">×</button>

      <h2 class="form-title">{{ prode?.nombre ?? 'Cargando...' }}</h2>

      <div v-if="loading" class="form-loading">Cargando datos del prode...</div>
      <div v-else-if="error" class="form-error">{{ error }}</div>

      <form v-else @submit.prevent="enviarFormulario" class="form-main" novalidate>
        <div class="form-group">
          <input
            v-model="form.nombre"
            placeholder="Nombre y apellido"
            required
            class="input"
          />
        </div>
        <div class="form-group">
          <input v-model="form.dni" placeholder="DNI" required class="input" />
        </div>
        <div class="form-group">
          <input
            v-model="form.celular"
            placeholder="Celular"
            required
            class="input"
          />
        </div>
        <div class="form-group">
          <input
            v-model="form.forma_pago"
            placeholder="Forma de pago"
            required
            class="input"
          />
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
        <button
          type="submit"
          class="btn-form"
          :disabled="!validarFormulario()"
        >
          Enviar pronóstico
        </button>
      </form>
    </div>
  </div>
</template>

<script setup>
import { ref, reactive, watch } from "vue";

const props = defineProps({
  id: {
    type: Number,
    required: true,
  },
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

// Cargar detalle de prode con POST y llenar form.pronosticos
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

function cerrar() {
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

// Función que valida el formulario
function validarFormulario() {
  if (!form.nombre || !form.dni || !form.celular || !form.forma_pago) {
    return false;
  }
  if (!prode.value) return false;

  // Separar carreras obligatorias y opcionales
  const carrerasObligatorias = prode.value.carreras.filter((c) => c.obligatoria);
  const carrerasOpcionales = prode.value.carreras.filter((c) => !c.obligatoria);

  // Validar que todas las obligatorias tengan caballo elegido
  const obligatoriasValidas = carrerasObligatorias.every((carrera) => {
    const pronostico = form.pronosticos.find((p) => p.carrera_id === carrera.id);
    return pronostico && pronostico.caballo_id !== "";
  });
  if (!obligatoriasValidas) return false;

  // Cantidad configurada (hardcodeado para ejemplo, cambiar por config real)
  const cantidadOpcionalesRequeridas = prode.value.configuracion?.cantidad_opcionales ?? 0;
  const cantidadSuplentesRequeridos = prode.value.configuracion?.cantidad_suplentes ?? 0;

  // Validar cantidad de opcionales elegidas
  const opcionalesElegidos = form.pronosticos.filter(
    (p) =>
      p.caballo_id !== "" &&
      carrerasOpcionales.some((c) => c.id === p.carrera_id)
  ).length;
  if (opcionalesElegidos !== cantidadOpcionalesRequeridas) return false;

  // Validar cantidad de suplentes elegidos (de entre opcionales no elegidas)
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
    cerrar();
    emit("guardado");
  } catch (e) {
    serverError.value = e.message;
  }
}
</script>

<style scoped>
@import url("https://fonts.googleapis.com/css?family=Roboto:400,700&display=swap");

.modal-backdrop {
  position: fixed;
  inset: 0;
  background: rgba(0, 0, 0, 0.6);
  display: flex;
  align-items: center;
  justify-content: center;
  z-index: 1000;
}

.modal-content {
  background: #fff;
  border-radius: 1em;
  box-shadow: 0 3px 24px rgba(0, 0, 0, 0.25);
  max-width: 520px;
  width: 90%;
  padding: 2em 2.5em 2.5em 2.5em;
  position: relative;
  font-family: "Roboto", sans-serif;
}

.cerrar-btn {
  position: absolute;
  top: 1em;
  right: 1.3em;
  font-size: 1.5em;
  background: transparent;
  border: none;
  cursor: pointer;
  color: #888;
  transition: color 0.2s;
}

.cerrar-btn:hover {
  color: #f53003;
}

.form-title {
  font-size: 1.4em;
  font-weight: bold;
  margin-bottom: 1.7em;
  text-align: center;
  color: #1a2237;
  letter-spacing: 0.04em;
}

.form-main {
  display: flex;
  flex-direction: column;
  gap: 1em;
}

.form-group {
  margin-bottom: 0.6em;
}

.input,
.select-caballo {
  width: 100%;
  padding: 0.7em 0.8em;
  border-radius: 0.7em;
  border: 1px solid #d1d5db;
  margin-bottom: 0.3em;
  font-size: 1em;
}

.form-carreras {
  margin: 1.3em 0 1em 0;
}

.form-section-title {
  font-size: 1.11em;
  font-weight: 500;
  color: #444;
  margin-bottom: 1em;
  text-align: center;
}

.carrera-group {
  margin-bottom: 1.2em;
}

.carrera-label {
  font-size: 1em;
  font-weight: 500;
  margin-bottom: 0.2em;
  display: flex;
  align-items: center;
  gap: 0.7em;
}

.tag-obligatoria {
  background: #f53003;
  color: #fff;
  border-radius: 0.4em;
  padding: 0.1em 0.55em;
  font-size: 0.85em;
  font-weight: 500;
}

.tag-opcional {
  background: #2b6cb0;
  color: #fff;
  border-radius: 0.4em;
  padding: 0.1em 0.55em;
  font-size: 0.85em;
  font-weight: 500;
}

.suplente-label {
  display: inline-flex;
  align-items: center;
  font-weight: 500;
  margin-top: 0.4em;
  font-size: 0.9em;
  cursor: pointer;
}

.suplente-label input[type="checkbox"] {
  margin-right: 0.4em;
}

.btn-form {
  background: #f53003;
  color: #fff;
  border: none;
  padding: 0.65em 0;
  border-radius: 0.8em;
  font-weight: 600;
  font-size: 1.11em;
  margin-top: 0.8em;
  cursor: pointer;
  box-shadow: 0 1.5px 6px 0 rgba(30, 40, 50, 0.1);
  transition: background 0.18s;
}

.btn-form:hover {
  background: #c41d00;
}

.form-error {
  color: #ef4444;
  background: #fee2e2;
  padding: 0.6em 1em;
  border-radius: 0.7em;
  font-size: 1em;
  text-align: center;
  margin-bottom: 1em;
}

.form-loading {
  text-align: center;
  font-size: 1.1em;
  padding: 2em 0;
}

@media (max-width: 640px) {
  .modal-content {
    max-width: 95vw;
    padding: 1.5em 1.5em 2em 1.5em;
  }
}

</style>
