<template>
  <div class="formulario-prode-page">
    <button class="volver-btn" @click="volver" aria-label="Volver">← Volver</button>
    <div class="formulario-prode-content">
      <!-- Logo fijo arriba -->
      <div class="prode-form-img-wrap">
        <img :src="logoUrl" alt="Logo del prode" class="prode-form-img" />
      </div>

      <!-- SOLO CÓMO JUGAR -->
 <div class="prode-reglas-box">
  <h3 class="prode-reglas-title">Cómo Jugar</h3>
  <!-- Lista para "puntos" -->
  <ol v-if="prode.tipo === 'puntos'" class="prode-reglas-list">
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
    <li><span class="prode-reglas-num">11.</span> El Premio lo gana el que TENGA más aciertos. Las carreras que son PUESTA también SUMAN un punto.</li>
    <li><span class="prode-reglas-num">12.</span> El pago a los ganadores se realizará al día siguiente de la última carrera del formulario.</li>
    <li><span class="prode-reglas-num">13.</span> El ganador con más aciertos se lleva el 90% de lo acumulado.</li>

  </ol>
  <!-- Lista para "libre" (o cualquier otro tipo) -->
  <ol v-else class="prode-reglas-list">
    <li><span class="prode-reglas-num">1.</span> Elegir el ganador de 10 carreras del fin de semana disponibles en el formulario.</li>
    <li><span class="prode-reglas-num">2.</span> Elegir 3 caballos suplentes para reemplazar algún caballo que no CORRE debido a lesiones, golpes, mal tiempo u otros inconvenientes.</li>
    <li><span class="prode-reglas-num">3.</span> Si algún caballo seleccionado NO CORRE, se reemplaza por el suplente 1. Si hay dos, se reemplazan por suplente 1 y 2, y así sucesivamente.</li>
    <li><span class="prode-reglas-num">4.</span> No hay restricciones sobre los ganadores: podés elegir cualquier caballo que participe.</li>
    <li><span class="prode-reglas-num">5.</span> Una vez seleccionados tus 10 ganadores y 3 suplentes, completá el formulario con tus datos y enviá tu PRODE.</li>
    <li><span class="prode-reglas-num">6.</span> No hay un límite de PRODES que puedas hacer: jugá y combiná cuantas veces quieras.</li>
    <li><span class="prode-reglas-num">7.</span> El valor del Prode es de $10.000.</li>
    <li><span class="prode-reglas-num">8.</span> Si aciertas en 10 carreras, ganás el Prode que equivale a $1.000.000.</li>
    <li><span class="prode-reglas-num">9.</span> Si NO aciertas a las 10 carreras, pero tenés más aciertos que otros jugadores, ganás $300.000.</li>
    <li><span class="prode-reglas-num">10.</span> Si hay 2 o más ganadores, el premio se reparte de manera proporcional.</li>
    <li><span class="prode-reglas-num">11.</span> Si hay 10 aciertos, el "sale o sale" de $300.000 no se entrega.</li>
    <li><span class="prode-reglas-num">12.</span> Realizá el depósito correspondiente al CBU indicado.</li>
    <li><span class="prode-reglas-num">13.</span> Se te enviará el PRODE que jugaste.</li>
    <li><span class="prode-reglas-num">14.</span> El sábado por la mañana se enviará un archivo Excel con todos los PRODES vendidos.</li>
    <li><span class="prode-reglas-num">15.</span> Esperá que se disputen las carreras.</li>
    <li><span class="prode-reglas-num">16.</span> El pago a los ganadores se realizará al día siguiente de la última carrera del formulario.</li>
  </ol>
</div>


      <!-- Formulario principal -->
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

        <!-- Datos personales -->
        <div class="form-group">
          <input v-model="form.nombre" placeholder="Nombre y apellido" required class="input" />
        </div>
        <div class="form-group">
          <input v-model="form.alias" placeholder="Alias / CBU" required class="input" />
        </div>
        <div class="form-group">
          <input v-model="form.celular" placeholder="Celular" required class="input" />
        </div>

        <!-- SELECT DE PRECIO PAGADO SIEMPRE CON OPCIONES Y APOSTADORES -->
        <div class="form-group">
          <label for="preciopagado" class="mb-1 font-semibold">Precio pagado</label>
          <select
            id="preciopagado"
            v-model.number="form.preciopagado"
            required
            class="select-carrera"
          >
            <option value="">Selecciona un monto</option>
            <option
              v-for="opcion in precioOpciones"
              :key="opcion"
              :value="opcion"
            >
              ${{ opcion.toLocaleString('es-AR') }}
              ({{ resumenMontos[opcion.toFixed(2)] || resumenMontos[String(opcion)] || 0 }} apostadores)
            </option>
          </select>
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
              prode-turf1
            </label>
            <label style="margin-left: 1em;">
              <input type="radio" v-model="form.alias_admin" value="Studvecinaslindas" />
              prode-turf2
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
import { useFormularioProde } from './FormularioProde.logic.js'
import './FormularioProde.css'

const props = defineProps({ id: { type: Number, required: true } })
const emit = defineEmits(["cerrar", "guardado"])

const {
  router, logoUrl, prode, loading, error, serverError,
  carrerasObligatorias, carrerasOpcionales, cantidadOpcionalesRequeridas,
  cantidadSuplentesRequeridos, pronosticosObligatorias, pronosticosOpcionales,
  suplentes, prodeVencido, carrerasObligatoriasAgrupadas, carrerasOpcionalesAgrupadas,
  getCaballosDeCarrera, onCheckObligatoria, onCheckOpcional, isOpcionalDisabled,
  onCheckSuplente, carrerasOpcionalesNoUsadasEnSuplentes, form,
  precioOpciones, resumenMontos, cargarProde, volver, validarFormulario, enviarFormulario
} = useFormularioProde(props, emit)
</script>
