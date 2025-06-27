import { ref, reactive, watch, computed } from "vue"
import { useRouter } from "vue-router"

export function useFormularioProde(props, emit) {
  const router = useRouter()

  const imagesPath = (import.meta.env.VITE_IMAGES_PUBLIC_PATH || '').replace(/\/$/, '')
  const prode = ref(null)
  const loading = ref(true)
  const error = ref("")
  const serverError = ref("")

  // Imagen según tipo, por defecto es libre
  const logoUrl = computed(() => {
    if (prode.value?.tipo === 'puntos') {
      return `${imagesPath}/ProdeXPuntos.jpg`
    }
    // Cualquier otro caso, incluso undefined/null, es 'libre'
    return `${imagesPath}/ProdeLibre.jpg`
  })

  const carrerasObligatorias = computed(() => prode.value?.carreras?.filter(c => c.obligatoria) || [])
  const carrerasOpcionales = computed(() => prode.value?.carreras?.filter(c => !c.obligatoria) || [])
  const cantidadOpcionalesRequeridas = computed(() => prode.value?.configuracion?.cantidad_opcionales || 0)
  const cantidadSuplentesRequeridos = computed(() => prode.value?.configuracion?.cantidad_suplentes || 0)

  const pronosticosObligatorias = reactive({})
  const pronosticosOpcionales = reactive({})
  const suplentes = reactive([])

  const precioOpciones = ref([])
  const resumenMontos = ref({})

  // Nuevo: computed para mostrar los montos ordenados de menor a mayor y como número
  const resumenMontosOrdenado = computed(() => {
    return Object.entries(resumenMontos.value)
      .map(([monto, cantidad]) => ({ monto: Number(monto), cantidad }))
      .sort((a, b) => a.monto - b.monto)
  })

  const prodeVencido = computed(() => {
    if (!prode.value?.fechafin) return false
    const ahora = ahoraARG()
    return new Date(prode.value.fechafin) <= ahora
  })

  function agruparPorHipico(carreras) {
    const grupos = {}
    carreras.forEach(c => {
      const key = c.hipico && c.hipico.trim() ? c.hipico : ''
      if (!grupos[key]) grupos[key] = []
      grupos[key].push(c)
    })
    return Object.entries(grupos)
      .sort(([a], [b]) => (a || '').localeCompare(b || ''))
      .map(([hipico, carreras]) => ({ hipico, carreras }))
  }

  const carrerasObligatoriasAgrupadas = computed(() => agruparPorHipico(carrerasObligatorias.value))
  const carrerasOpcionalesAgrupadas = computed(() => agruparPorHipico(carrerasOpcionales.value))

  function ahoraARG() {
    return new Date(new Date().toLocaleString("en-US", { timeZone: "America/Argentina/Buenos_Aires" }))
  }
  function getCaballosDeCarrera(carreraId) {
    const carrera = prode.value?.carreras?.find(c => c.id === carreraId)
    return carrera?.caballos || []
  }
  function onCheckObligatoria(carreraId, caballoId) {
    pronosticosObligatorias[carreraId] =
      pronosticosObligatorias[carreraId] === caballoId ? "" : caballoId
  }
  function onCheckOpcional(carreraId, caballoId) {
    pronosticosOpcionales[carreraId] =
      pronosticosOpcionales[carreraId] === caballoId ? "" : caballoId
  }
  function isOpcionalDisabled(carreraId, caballoId) {
    const cantidadElegidas = Object.values(pronosticosOpcionales).filter(v => v).length
    return (
      !pronosticosOpcionales[carreraId] &&
      cantidadElegidas >= cantidadOpcionalesRequeridas.value
    )
  }
  function onCheckSuplente(idx, caballoId) {
    suplentes[idx].caballoId =
      suplentes[idx].caballoId === caballoId ? "" : caballoId
  }
  function carrerasOpcionalesNoUsadasEnSuplentes(idx) {
    const usadasEnOpcionales = Object.keys(pronosticosOpcionales).filter(cid => pronosticosOpcionales[cid])
    const usadasEnSuplentes = suplentes.map((s, i) => i !== idx ? s.carreraId : null).filter(Boolean)
    return carrerasOpcionales.value.filter(
      c => !usadasEnOpcionales.includes(String(c.id)) && !usadasEnSuplentes.includes(c.id)
    )
  }

  const form = reactive({
    nombre: "",
    alias: "",
    alias_admin: "",
    celular: "",
    forma_pago: "",
    preciopagado: "", // SOLO GUARDA EL MONTO
  })

  const cargarProde = async (id) => {
    loading.value = true
    error.value = ""
    try {
      const res = await fetch("/formularios/detalle", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
        },
        body: JSON.stringify({ id }),
      })
      if (!res.ok) throw new Error("No se pudo cargar el detalle del prode")
      const data = await res.json()
      prode.value = data

      // Guardar las cantidades de apostadores por monto (o vacío si no viene)
      resumenMontos.value = data.resumen_montos || {}

      Object.keys(pronosticosObligatorias).forEach(key => delete pronosticosObligatorias[key])
      carrerasObligatorias.value.forEach(c => pronosticosObligatorias[c.id] = "")

      Object.keys(pronosticosOpcionales).forEach(key => delete pronosticosOpcionales[key])
      carrerasOpcionales.value.forEach(c => pronosticosOpcionales[c.id] = "")

      suplentes.length = 0
      for (let i = 0; i < cantidadSuplentesRequeridos.value; i++) {
        suplentes.push({ carreraId: "", caballoId: "" })
      }

      form.nombre = ""
      form.alias = ""
      form.alias_admin = ""
      form.celular = ""
      form.forma_pago = ""
      form.preciopagado = ""

      // OPCIONES DE PRECIO SEGÚN TIPO
      if (data.tipo === 'puntos') {
        precioOpciones.value = [5000, 10000, 20000, 40000, 50000, 100000]
      } else {
        // Por defecto es "libre" si no viene tipo o es otro valor
        precioOpciones.value = [5000, 10000, 20000, 40000, 50000, 100000]
      }
    } catch (e) {
      error.value = e.message
    } finally {
      loading.value = false
    }
  }

  watch(
    () => props.id,
    (newId) => { if (newId) cargarProde(newId) },
    { immediate: true }
  )

  function volver() {
    router.push('/')
  }

  function validarFormulario() {
    if (prodeVencido.value) return false
    if (carrerasObligatorias.value.some(c => !pronosticosObligatorias[c.id])) return false
    const elegidas = Object.keys(pronosticosOpcionales).filter(cid => pronosticosOpcionales[cid])
    if (elegidas.length !== cantidadOpcionalesRequeridas.value) return false
    const suplentesIds = suplentes.map(s => s.carreraId)
    if (
      suplentes.length !== cantidadSuplentesRequeridos.value ||
      suplentes.some(s => !s.carreraId || !s.caballoId) ||
      new Set(suplentesIds).size !== suplentesIds.length
    ) return false
    if (!form.nombre || !form.alias || !form.celular || !form.forma_pago || !form.preciopagado) return false
    if (!["Efectivo", "Transferencia"].includes(form.forma_pago)) return false
    if (form.forma_pago === "Transferencia" && !form.alias_admin) return false
    return true
  }

  async function enviarFormulario() {
    serverError.value = ""
    if (prodeVencido.value) {
      serverError.value = "Este prode ya finalizó. No podés enviar pronóstico."
      return
    }
    if (!validarFormulario()) {
      serverError.value = "Completá todos los campos y pronósticos correctamente."
      return
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
    ]
    const payload = {
      prode_caballo_id: prode.value.id,
      nombre: form.nombre,
      alias: form.alias,
      alias_admin: form.forma_pago === "Transferencia" ? form.alias_admin : null,
      celular: form.celular,
      forma_pago: form.forma_pago,
      preciopagado: form.preciopagado,
      pronosticos,
    }

    try {
      const res = await fetch("/api/guardar-formulario", {
        method: "POST",
        headers: {
          "Content-Type": "application/json",
          "X-CSRF-TOKEN": document.querySelector('meta[name="csrf-token"]').getAttribute("content"),
        },
        body: JSON.stringify(payload),
      })
      const result = await res.json()
      if (!res.ok) throw new Error(result.error || "Error al guardar el formulario")
      alert("¡Pronóstico enviado correctamente!")
      await cargarProde(props.id)
      emit("guardado")
    } catch (e) {
      serverError.value = e.message
    }
  }

  return {
    router, logoUrl, prode, loading, error, serverError,
    carrerasObligatorias, carrerasOpcionales,
    cantidadOpcionalesRequeridas, cantidadSuplentesRequeridos,
    pronosticosObligatorias, pronosticosOpcionales, suplentes,
    prodeVencido, carrerasObligatoriasAgrupadas, carrerasOpcionalesAgrupadas,
    getCaballosDeCarrera, onCheckObligatoria, onCheckOpcional,
    isOpcionalDisabled, onCheckSuplente, carrerasOpcionalesNoUsadasEnSuplentes,
    form, precioOpciones, resumenMontos, resumenMontosOrdenado, cargarProde, volver, validarFormulario, enviarFormulario
  }
}
