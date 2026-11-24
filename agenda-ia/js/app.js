/* ==========================================================================
   AgendaWeb · Lógica de la aplicación en JavaScript puro (sin servidor)
   Persistencia local en localStorage bajo la clave "agendaweb.eventos".
   ========================================================================== */

(function () {
  "use strict";

  // Constantes del modelo de datos (idéntico al esquema original en PHP + MySQL)
  const CLAVE_STORAGE = "agendaweb.eventos";
  const CATEGORIAS_VALIDAS = ["Trabajo", "Personal", "Estudio", "Ocio / Deporte"];
  const MAX_TITULO = 120;
  const MAX_DESCRIPCION = 500;
  const RETRASO_BUSQUEDA_MS = 300;

  // Estado en memoria de la aplicación
  let eventos = [];
  let idEnEdicion = null;
  let textoBusqueda = "";
  let categoriaFiltro = "";
  let temporizadorBusqueda = null;
  let almacenamientoDanado = false;
  let accionConfirmacionPendiente = null;

  // Referencias al DOM
  const enlacesNav = document.querySelectorAll(".enlace-nav");
  const alertaAlmacenamiento = document.getElementById("alerta-almacenamiento");
  const textoAlertaAlmacenamiento = document.getElementById("texto-alerta-almacenamiento");
  const btnLimpiarCorrupto = document.getElementById("btn-limpiar-corrupto");

  const mensajeEstado = document.getElementById("mensaje-estado");
  const textoMensajeEstado = document.getElementById("texto-mensaje-estado");

  const tituloFormulario = document.getElementById("titulo-formulario");
  const formEvento = document.getElementById("form-evento");
  const campoTitulo = document.getElementById("campo-titulo");
  const campoFecha = document.getElementById("campo-fecha");
  const campoHora = document.getElementById("campo-hora");
  const campoCategoria = document.getElementById("campo-categoria");
  const campoDescripcion = document.getElementById("campo-descripcion");
  const contadorTitulo = document.getElementById("contador-titulo");
  const contadorDescripcion = document.getElementById("contador-descripcion");

  const errorTitulo = document.getElementById("error-titulo");
  const errorFecha = document.getElementById("error-fecha");
  const errorHora = document.getElementById("error-hora");
  const errorCategoria = document.getElementById("error-categoria");
  const errorDescripcion = document.getElementById("error-descripcion");

  const btnGuardar = document.getElementById("btn-guardar");
  const btnCancelarEdicion = document.getElementById("btn-cancelar-edicion");

  const btnExportar = document.getElementById("btn-exportar");
  const btnImportar = document.getElementById("btn-importar");
  const inputImportar = document.getElementById("input-importar");

  const buscadorTexto = document.getElementById("buscador-texto");
  const filtroCategoria = document.getElementById("filtro-categoria");
  const btnLimpiarFiltros = document.getElementById("btn-limpiar-filtros");
  const contadorEventos = document.getElementById("contador-eventos");
  const listaEventos = document.getElementById("lista-eventos");
  const estadoVacio = document.getElementById("estado-vacio");
  const textoEstadoVacio = document.getElementById("texto-estado-vacio");

  const modalConfirmacion = document.getElementById("modal-confirmacion");
  const modalTitulo = document.getElementById("modal-titulo");
  const modalMensaje = document.getElementById("modal-mensaje");
  const modalBtnCancelar = document.getElementById("modal-btn-cancelar");
  const modalBtnConfirmar = document.getElementById("modal-btn-confirmar");

  /* ========================================================================
     1. Capa de persistencia (ÚNICO punto de lectura/escritura en localStorage)
     ======================================================================== */

  /**
   * Lee los eventos desde localStorage y valida que el JSON sea íntegro.
   * Si el contenido está dañado, NO borra nada y marca el estado de advertencia.
   * @returns {{ eventos: Array<Object>, danado: boolean, mensajeError: string }}
   */
  function cargarEventos() {
    let contenidoCrudo = null;
    try {
      contenidoCrudo = localStorage.getItem(CLAVE_STORAGE);
    } catch (error) {
      return {
        eventos: [],
        danado: true,
        mensajeError: "No se pudo acceder a localStorage en este navegador."
      };
    }

    // Primera visita: aún no existe la clave, no es un error
    if (contenidoCrudo === null || contenidoCrudo === "") {
      return { eventos: [], danado: false, mensajeError: "" };
    }

    try {
      const datos = JSON.parse(contenidoCrudo);
      if (!Array.isArray(datos)) {
        return {
          eventos: [],
          danado: true,
          mensajeError:
            "El contenido guardado en localStorage no tiene formato de lista. No se borró ningún dato."
        };
      }

      // Verificar que cada registro cumpla el modelo de datos
      const todosValidos = datos.every(esEventoValido);
      if (!todosValidos) {
        return {
          eventos: [],
          danado: true,
          mensajeError:
            "Algunos datos en localStorage están dañados o incompletos. No se borró ningún dato automáticamente."
        };
      }

      return { eventos: datos, danado: false, mensajeError: "" };
    } catch (error) {
      return {
        eventos: [],
        danado: true,
        mensajeError:
          "Los datos guardados en localStorage están dañados (JSON inválido). No se borró nada automáticamente."
      };
    }
  }

  /**
   * Serializa el arreglo de eventos y lo guarda en localStorage.
   * @param {Array<Object>} lista
   * @returns {boolean}
   */
  function guardarEventos(lista) {
    try {
      localStorage.setItem(CLAVE_STORAGE, JSON.stringify(lista));
      return true;
    } catch (error) {
      mostrarMensajeEstado(
        "No se pudieron guardar los cambios en localStorage.",
        "error"
      );
      return false;
    }
  }

  /* ========================================================================
     2. Validación del modelo de datos y utilidades de formato
     ======================================================================== */

  /**
   * Valida que una cadena tenga formato AAAA-MM-DD y sea una fecha real.
   * @param {string} fechaTexto
   * @returns {boolean}
   */
  function esFechaValida(fechaTexto) {
    if (typeof fechaTexto !== "string" || !/^\d{4}-\d{2}-\d{2}$/.test(fechaTexto)) {
      return false;
    }
    const partes = fechaTexto.split("-").map(Number);
    const anio = partes[0];
    const mes = partes[1];
    const dia = partes[2];
    const fechaPrueba = new Date(anio, mes - 1, dia);

    return (
      fechaPrueba.getFullYear() === anio &&
      fechaPrueba.getMonth() === mes - 1 &&
      fechaPrueba.getDate() === dia
    );
  }

  /**
   * Valida formato HH:MM en reloj de 24 horas.
   * @param {string} horaTexto
   * @returns {boolean}
   */
  function esHoraValida(horaTexto) {
    return typeof horaTexto === "string" && /^([01]\d|2[0-3]):[0-5]\d$/.test(horaTexto);
  }

  /**
   * Comprueba si un objeto cumple exactamente el modelo de datos de AgendaWeb.
   * @param {unknown} item
   * @returns {boolean}
   */
  function esEventoValido(item) {
    if (typeof item !== "object" || item === null || Array.isArray(item)) {
      return false;
    }
    const idValido = typeof item.id === "string" && item.id.trim().length > 0;
    const tituloValido =
      typeof item.titulo === "string" &&
      item.titulo.trim().length > 0 &&
      item.titulo.length <= MAX_TITULO;
    const fechaValida = esFechaValida(item.fecha);
    const horaValida =
      typeof item.hora === "string" && (item.hora === "" || esHoraValida(item.hora));
    const categoriaValida =
      typeof item.categoria === "string" && CATEGORIAS_VALIDAS.includes(item.categoria);
    const descripcionValida =
      typeof item.descripcion === "string" && item.descripcion.length <= MAX_DESCRIPCION;
    const creadoEnValido =
      typeof item.creadoEn === "string" &&
      item.creadoEn.trim().length > 0 &&
      !Number.isNaN(Date.parse(item.creadoEn));

    return (
      idValido &&
      tituloValido &&
      fechaValida &&
      horaValida &&
      categoriaValida &&
      descripcionValida &&
      creadoEnValido
    );
  }

  /**
   * Genera un identificador único con crypto.randomUUID().
   * @returns {string}
   */
  function generarIdEvento() {
    if (typeof crypto !== "undefined" && typeof crypto.randomUUID === "function") {
      return crypto.randomUUID();
    }
    // Respaldo estándar UUID v4 solo si el contexto no expone randomUUID
    const bytes = new Uint8Array(16);
    crypto.getRandomValues(bytes);
    bytes[6] = (bytes[6] & 0x0f) | 0x40;
    bytes[8] = (bytes[8] & 0x3f) | 0x80;
    const hex = Array.from(bytes, (b) => b.toString(16).padStart(2, "0")).join("");
    return `${hex.slice(0, 8)}-${hex.slice(8, 12)}-${hex.slice(12, 16)}-${hex.slice(16, 20)}-${hex.slice(20)}`;
  }

  /**
   * Convierte una fecha AAAA-MM-DD a DD/MM/AAAA para mostrar en tarjeta.
   * @param {string} fechaISO
   * @returns {string}
   */
  function formatearFechaDMY(fechaISO) {
    const partes = fechaISO.split("-");
    if (partes.length !== 3) {
      return fechaISO;
    }
    return `${partes[2]}/${partes[1]}/${partes[0]}`;
  }

  /**
   * Normaliza texto quitando acentos/diacríticos y pasando a minúsculas.
   * @param {string} texto
   * @returns {string}
   */
  function normalizarTexto(texto) {
    return texto
      .normalize("NFD")
      .replace(/[\u0300-\u036f]/g, "")
      .toLowerCase();
  }

  /**
   * Ordena eventos cronológicamente (las fechas y horas más próximas primero).
   * @param {Array<Object>} lista
   * @returns {Array<Object>}
   */
  function ordenarEventos(lista) {
    return [...lista].sort((a, b) => {
      if (a.fecha !== b.fecha) {
        return a.fecha.localeCompare(b.fecha);
      }
      const horaA = a.hora || "99:99";
      const horaB = b.hora || "99:99";
      if (horaA !== horaB) {
        return horaA.localeCompare(horaB);
      }
      return a.creadoEn.localeCompare(b.creadoEn);
    });
  }

  /* ========================================================================
     3. Mensajes de interfaz y diálogo de confirmación
     ======================================================================== */

  /**
   * Muestra un mensaje temporal de estado en el tablero.
   * @param {string} texto
   * @param {"info" | "error"} tipo
   */
  function mostrarMensajeEstado(texto, tipo) {
    textoMensajeEstado.textContent = texto;
    mensajeEstado.classList.toggle("alerta-error", tipo === "error");
    mensajeEstado.hidden = false;
  }

  function ocultarMensajeEstado() {
    mensajeEstado.hidden = true;
    textoMensajeEstado.textContent = "";
    mensajeEstado.classList.remove("alerta-error");
  }

  function actualizarBannerAlmacenamientoDanado(mensaje) {
    if (almacenamientoDanado) {
      textoAlertaAlmacenamiento.textContent = mensaje;
      alertaAlmacenamiento.hidden = false;
    } else {
      textoAlertaAlmacenamiento.textContent = "";
      alertaAlmacenamiento.hidden = true;
    }
  }

  /**
   * Abre el diálogo accesible de confirmación y ejecuta el callback si se acepta.
   * @param {string} titulo
   * @param {string} mensaje
   * @param {string} textoConfirmar
   * @param {Function} alConfirmar
   */
  function abrirConfirmacion(titulo, mensaje, textoConfirmar, alConfirmar) {
    modalTitulo.textContent = titulo;
    modalMensaje.textContent = mensaje;
    modalBtnConfirmar.textContent = textoConfirmar;
    accionConfirmacionPendiente = alConfirmar;

    if (typeof modalConfirmacion.showModal === "function") {
      modalConfirmacion.showModal();
    }
  }

  function cerrarConfirmacion() {
    accionConfirmacionPendiente = null;
    if (modalConfirmacion.open) {
      modalConfirmacion.close();
    }
  }

  /* ========================================================================
     4. Validación del formulario (mensajes junto a cada campo)
     ======================================================================== */

  function mostrarErrorCampo(campo, elementoError, mensaje) {
    elementoError.textContent = mensaje;
    elementoError.hidden = false;
    campo.setAttribute("aria-invalid", "true");
  }

  function limpiarErrorCampo(campo, elementoError) {
    elementoError.textContent = "";
    elementoError.hidden = true;
    campo.removeAttribute("aria-invalid");
  }

  function limpiarTodosLosErrores() {
    limpiarErrorCampo(campoTitulo, errorTitulo);
    limpiarErrorCampo(campoFecha, errorFecha);
    limpiarErrorCampo(campoHora, errorHora);
    limpiarErrorCampo(campoCategoria, errorCategoria);
    limpiarErrorCampo(campoDescripcion, errorDescripcion);
  }

  function actualizarContadoresCaracteres() {
    contadorTitulo.textContent = String(campoTitulo.value.length);
    contadorDescripcion.textContent = String(campoDescripcion.value.length);
  }

  /**
   * Valida todos los campos del formulario y coloca los errores junto a cada campo.
   * @returns {boolean} true si el formulario es válido
   */
  function validarFormulario() {
    limpiarTodosLosErrores();
    let primerCampoInvalido = null;

    const tituloLimpio = campoTitulo.value.trim();
    if (tituloLimpio.length === 0) {
      mostrarErrorCampo(campoTitulo, errorTitulo, "El título es obligatorio.");
      primerCampoInvalido = primerCampoInvalido || campoTitulo;
    } else if (tituloLimpio.length > MAX_TITULO) {
      mostrarErrorCampo(
        campoTitulo,
        errorTitulo,
        `El título no puede superar los ${MAX_TITULO} caracteres.`
      );
      primerCampoInvalido = primerCampoInvalido || campoTitulo;
    }

    const fechaValor = campoFecha.value;
    if (!fechaValor) {
      mostrarErrorCampo(campoFecha, errorFecha, "La fecha es obligatoria.");
      primerCampoInvalido = primerCampoInvalido || campoFecha;
    } else if (!esFechaValida(fechaValor)) {
      mostrarErrorCampo(
        campoFecha,
        errorFecha,
        "Ingresa una fecha válida con formato AAAA-MM-DD."
      );
      primerCampoInvalido = primerCampoInvalido || campoFecha;
    }

    const horaValor = campoHora.value;
    if (horaValor !== "" && !esHoraValida(horaValor)) {
      mostrarErrorCampo(
        campoHora,
        errorHora,
        "Ingresa una hora válida con formato HH:MM."
      );
      primerCampoInvalido = primerCampoInvalido || campoHora;
    }

    const categoriaValor = campoCategoria.value;
    if (!CATEGORIAS_VALIDAS.includes(categoriaValor)) {
      mostrarErrorCampo(
        campoCategoria,
        errorCategoria,
        "Selecciona una categoría válida."
      );
      primerCampoInvalido = primerCampoInvalido || campoCategoria;
    }

    const descripcionValor = campoDescripcion.value.trim();
    if (descripcionValor.length > MAX_DESCRIPCION) {
      mostrarErrorCampo(
        campoDescripcion,
        errorDescripcion,
        `La descripción no puede superar los ${MAX_DESCRIPCION} caracteres.`
      );
      primerCampoInvalido = primerCampoInvalido || campoDescripcion;
    }

    if (primerCampoInvalido) {
      primerCampoInvalido.focus();
      return false;
    }
    return true;
  }

  /* ========================================================================
     5. Renderizado seguro del tablero (solo createElement + textContent)
     ======================================================================== */

  /**
   * Formatea el contador de eventos con singular y plural correctos.
   * @param {number} cantidad
   * @param {boolean} hayFiltro
   * @returns {string}
   */
  function formatearContador(cantidad, hayFiltro) {
    const palabraEvento = cantidad === 1 ? "evento" : "eventos";
    const palabraEstado = hayFiltro
      ? cantidad === 1
        ? "encontrado"
        : "encontrados"
      : cantidad === 1
        ? "registrado"
        : "registrados";
    return `${cantidad} ${palabraEvento} ${palabraEstado}`;
  }

  /**
   * Construye un elemento DOM <article> seguro para un evento usando textContent.
   * @param {Object} evento
   * @returns {HTMLElement}
   */
  function crearTarjetaEvento(evento) {
    const tarjeta = document.createElement("article");
    tarjeta.className = "tarjeta-evento";

    // Cabecera: badge de categoría + fecha y hora
    const cabecera = document.createElement("div");
    cabecera.className = "tarjeta-evento-cabecera";

    const badge = document.createElement("span");
    badge.className = "badge-categoria";
    badge.textContent = evento.categoria;

    const fechaHora = document.createElement("div");
    fechaHora.className = "evento-fecha-hora";

    const spanFecha = document.createElement("time");
    spanFecha.setAttribute("datetime", evento.fecha);
    spanFecha.textContent = formatearFechaDMY(evento.fecha);
    fechaHora.appendChild(spanFecha);

    if (evento.hora) {
      const separador = document.createElement("span");
      separador.setAttribute("aria-hidden", "true");
      separador.textContent = "·";

      const spanHora = document.createElement("span");
      spanHora.textContent = evento.hora;

      fechaHora.appendChild(separador);
      fechaHora.appendChild(spanHora);
    }

    cabecera.appendChild(badge);
    cabecera.appendChild(fechaHora);

    // Título (siempre con textContent para evitar XSS)
    const titulo = document.createElement("h2");
    titulo.className = "evento-titulo";
    titulo.textContent = evento.titulo;

    tarjeta.appendChild(cabecera);
    tarjeta.appendChild(titulo);

    // Descripción opcional
    if (evento.descripcion) {
      const descripcion = document.createElement("p");
      descripcion.className = "evento-descripcion";
      descripcion.textContent = evento.descripcion;
      tarjeta.appendChild(descripcion);
    }

    // Acciones: Editar y Borrar
    const acciones = document.createElement("div");
    acciones.className = "tarjeta-evento-acciones";

    const btnEditar = document.createElement("button");
    btnEditar.type = "button";
    btnEditar.className = "boton boton-secundario boton-pequeno";
    btnEditar.textContent = "Editar";
    btnEditar.addEventListener("click", () => iniciarEdicion(evento.id));

    const btnBorrar = document.createElement("button");
    btnBorrar.type = "button";
    btnBorrar.className = "boton boton-peligro-sutil boton-pequeno";
    btnBorrar.textContent = "Borrar";
    btnBorrar.addEventListener("click", () => solicitarBorrado(evento));

    acciones.appendChild(btnEditar);
    acciones.appendChild(btnBorrar);
    tarjeta.appendChild(acciones);

    return tarjeta;
  }

  /**
   * Filtra, ordena y dibuja las tarjetas de eventos en el tablero.
   */
  function renderizarTablero() {
    const consultaNormalizada = normalizarTexto(textoBusqueda.trim());
    const hayFiltroActivo = consultaNormalizada !== "" || categoriaFiltro !== "";

    btnLimpiarFiltros.hidden = !hayFiltroActivo;

    const eventosOrdenados = ordenarEventos(eventos);
    const eventosFiltrados = eventosOrdenados.filter((evento) => {
      const coincideCategoria =
        categoriaFiltro === "" || evento.categoria === categoriaFiltro;
      if (!coincideCategoria) {
        return false;
      }

      if (consultaNormalizada === "") {
        return true;
      }

      const tituloNorm = normalizarTexto(evento.titulo);
      const descripcionNorm = normalizarTexto(evento.descripcion);
      return (
        tituloNorm.includes(consultaNormalizada) ||
        descripcionNorm.includes(consultaNormalizada)
      );
    });

    // Actualizar contador con singular/plural correcto
    contadorEventos.textContent = formatearContador(
      eventosFiltrados.length,
      hayFiltroActivo
    );

    // Limpiar contenedor de tarjetas sin usar innerHTML
    listaEventos.replaceChildren();

    if (eventos.length === 0) {
      estadoVacio.hidden = false;
      textoEstadoVacio.textContent = "Aún no tienes eventos registrados.";
      return;
    }

    if (eventosFiltrados.length === 0) {
      estadoVacio.hidden = false;
      textoEstadoVacio.textContent = "Sin resultados para tu búsqueda.";
      return;
    }

    estadoVacio.hidden = true;
    const fragmento = document.createDocumentFragment();
    eventosFiltrados.forEach((evento) => {
      fragmento.appendChild(crearTarjetaEvento(evento));
    });
    listaEventos.appendChild(fragmento);
  }

  /* ========================================================================
     6. Operaciones CRUD (Crear, Editar, Borrar)
     ======================================================================== */

  function reiniciarFormulario() {
    idEnEdicion = null;
    formEvento.reset();
    limpiarTodosLosErrores();
    actualizarContadoresCaracteres();
    tituloFormulario.textContent = "Nuevo evento";
    btnGuardar.textContent = "Guardar evento";
    btnCancelarEdicion.hidden = true;
  }

  function iniciarEdicion(id) {
    const evento = eventos.find((item) => item.id === id);
    if (!evento) {
      return;
    }

    idEnEdicion = evento.id;
    limpiarTodosLosErrores();

    campoTitulo.value = evento.titulo;
    campoFecha.value = evento.fecha;
    campoHora.value = evento.hora;
    campoCategoria.value = evento.categoria;
    campoDescripcion.value = evento.descripcion;
    actualizarContadoresCaracteres();

    tituloFormulario.textContent = "Editar evento";
    btnGuardar.textContent = "Guardar cambios";
    btnCancelarEdicion.hidden = false;

    actualizarEnlaceActivo("seccion-formulario");
    campoTitulo.focus();
  }

  function persistirEventos(nuevaLista, mensajeExito) {
    const ejecutarGuardado = () => {
      if (guardarEventos(nuevaLista)) {
        almacenamientoDanado = false;
        actualizarBannerAlmacenamientoDanado("");
        eventos = nuevaLista;
        renderizarTablero();
        mostrarMensajeEstado(mensajeExito, "info");
      }
    };

    // Si había datos corruptos sin borrar, pedir confirmación antes de sobrescribir
    if (almacenamientoDanado) {
      abrirConfirmacion(
        "Reemplazar almacenamiento dañado",
        "Tu navegador tiene datos dañados guardados previamente en localStorage. ¿Deseas reemplazarlos para guardar estos cambios?",
        "Sí, reemplazar",
        ejecutarGuardado
      );
      return;
    }

    ejecutarGuardado();
  }

  function manejarEnvioFormulario(eventoSubmit) {
    eventoSubmit.preventDefault();
    ocultarMensajeEstado();

    if (!validarFormulario()) {
      return;
    }

    const titulo = campoTitulo.value.trim();
    const fecha = campoFecha.value;
    const hora = campoHora.value;
    const categoria = campoCategoria.value;
    const descripcion = campoDescripcion.value.trim();

    if (idEnEdicion !== null) {
      const indice = eventos.findIndex((item) => item.id === idEnEdicion);
      if (indice === -1) {
        reiniciarFormulario();
        return;
      }

      const listaActualizada = eventos.map((item) =>
        item.id === idEnEdicion
          ? {
              ...item,
              titulo,
              fecha,
              hora,
              categoria,
              descripcion
            }
          : item
      );

      reiniciarFormulario();
      persistirEventos(listaActualizada, "Evento actualizado correctamente.");
      return;
    }

    const nuevoEvento = {
      id: generarIdEvento(),
      titulo,
      fecha,
      hora,
      categoria,
      descripcion,
      creadoEn: new Date().toISOString()
    };

    const listaConNuevo = [...eventos, nuevoEvento];
    reiniciarFormulario();
    persistirEventos(listaConNuevo, "Evento guardado correctamente.");
  }

  function solicitarBorrado(evento) {
    ocultarMensajeEstado();
    abrirConfirmacion(
      "Eliminar evento",
      `¿Seguro que deseas eliminar el evento "${evento.titulo}"? Esta acción no se puede deshacer.`,
      "Sí, eliminar",
      () => {
        const listaRestante = eventos.filter((item) => item.id !== evento.id);
        if (idEnEdicion === evento.id) {
          reiniciarFormulario();
        }
        persistirEventos(listaRestante, "Evento eliminado.");
      }
    );
  }

  /* ========================================================================
     7. Exportar e Importar respaldo JSON
     ======================================================================== */

  function exportarJSON() {
    ocultarMensajeEstado();
    const contenidoJSON = JSON.stringify(eventos, null, 2);
    const blob = new Blob([contenidoJSON], { type: "application/json;charset=utf-8" });
    const urlDescarga = URL.createObjectURL(blob);

    const enlaceTemporal = document.createElement("a");
    enlaceTemporal.href = urlDescarga;
    enlaceTemporal.download = "agendaweb-eventos.json";
    document.body.appendChild(enlaceTemporal);
    enlaceTemporal.click();
    document.body.removeChild(enlaceTemporal);
    URL.revokeObjectURL(urlDescarga);

    mostrarMensajeEstado("Archivo agendaweb-eventos.json descargado.", "info");
  }

  function manejarSeleccionArchivoJSON(eventoCambio) {
    ocultarMensajeEstado();
    const archivo = eventoCambio.target.files && eventoCambio.target.files[0];
    // Reiniciar el input para permitir volver a seleccionar el mismo archivo
    inputImportar.value = "";

    if (!archivo) {
      return;
    }

    const lector = new FileReader();
    lector.onload = () => {
      let datosParseados;
      try {
        datosParseados = JSON.parse(String(lector.result));
      } catch (error) {
        mostrarMensajeEstado(
          "El archivo seleccionado no es un JSON válido. Tus eventos actuales no han cambiado.",
          "error"
        );
        return;
      }

      if (!Array.isArray(datosParseados)) {
        mostrarMensajeEstado(
          "El archivo JSON debe contener un arreglo de eventos. Tus eventos actuales no han cambiado.",
          "error"
        );
        return;
      }

      const todosValidos = datosParseados.every(esEventoValido);
      if (!todosValidos) {
        mostrarMensajeEstado(
          "Uno o más eventos del archivo no cumplen con el modelo de datos requerido. Tus eventos actuales no han cambiado.",
          "error"
        );
        return;
      }

      const idsUnicos = new Set(datosParseados.map((item) => item.id));
      if (idsUnicos.size !== datosParseados.length) {
        mostrarMensajeEstado(
          "El archivo contiene identificadores de evento duplicados. Tus eventos actuales no han cambiado.",
          "error"
        );
        return;
      }

      const cantidadNueva = datosParseados.length;
      const cantidadActual = eventos.length;
      const textoActual = cantidadActual === 1 ? "1 evento actual" : `${cantidadActual} eventos actuales`;
      const textoNuevo = cantidadNueva === 1 ? "1 evento" : `${cantidadNueva} eventos`;

      abrirConfirmacion(
        "Importar respaldo JSON",
        `Esta acción reemplazará ${textoActual} por ${textoNuevo} del archivo seleccionado. ¿Deseas continuar?`,
        "Sí, reemplazar",
        () => {
          if (guardarEventos(datosParseados)) {
            almacenamientoDanado = false;
            actualizarBannerAlmacenamientoDanado("");
            eventos = datosParseados;
            reiniciarFormulario();
            renderizarTablero();
            mostrarMensajeEstado(
              `Respaldo importado correctamente (${textoNuevo}).`,
              "info"
            );
          }
        }
      );
    };

    lector.onerror = () => {
      mostrarMensajeEstado(
        "Ocurrió un error al leer el archivo seleccionado. Tus eventos actuales no han cambiado.",
        "error"
      );
    };

    lector.readAsText(archivo, "UTF-8");
  }

  /* ========================================================================
     8. Navegación y enlace de eventos del DOM
     ======================================================================== */

  function actualizarEnlaceActivo(idSeccion) {
    enlacesNav.forEach((enlace) => {
      const coincide = enlace.dataset.seccion === idSeccion;
      enlace.classList.toggle("activo", coincide);
      if (coincide) {
        enlace.setAttribute("aria-current", "page");
      } else {
        enlace.removeAttribute("aria-current");
      }
    });
  }

  function inicializarEventosDOM() {
    // Navegación del encabezado
    enlacesNav.forEach((enlace) => {
      enlace.addEventListener("click", () => {
        if (enlace.dataset.seccion) {
          actualizarEnlaceActivo(enlace.dataset.seccion);
        }
      });
    });

    // Formulario de alta / edición
    formEvento.addEventListener("submit", manejarEnvioFormulario);
    btnCancelarEdicion.addEventListener("click", () => {
      reiniciarFormulario();
      ocultarMensajeEstado();
    });

    // Limpiar errores conforme el usuario corrige cada campo
    campoTitulo.addEventListener("input", () => {
      actualizarContadoresCaracteres();
      if (!errorTitulo.hidden) {
        limpiarErrorCampo(campoTitulo, errorTitulo);
      }
    });
    campoFecha.addEventListener("input", () => {
      if (!errorFecha.hidden) {
        limpiarErrorCampo(campoFecha, errorFecha);
      }
    });
    campoHora.addEventListener("input", () => {
      if (!errorHora.hidden) {
        limpiarErrorCampo(campoHora, errorHora);
      }
    });
    campoCategoria.addEventListener("change", () => {
      if (!errorCategoria.hidden) {
        limpiarErrorCampo(campoCategoria, errorCategoria);
      }
    });
    campoDescripcion.addEventListener("input", () => {
      actualizarContadoresCaracteres();
      if (!errorDescripcion.hidden) {
        limpiarErrorCampo(campoDescripcion, errorDescripcion);
      }
    });

    // Buscador en vivo con espera de 300 ms tras la última tecla
    buscadorTexto.addEventListener("input", () => {
      clearTimeout(temporizadorBusqueda);
      temporizadorBusqueda = setTimeout(() => {
        textoBusqueda = buscadorTexto.value;
        renderizarTablero();
      }, RETRASO_BUSQUEDA_MS);
    });

    // Filtro por categoría
    filtroCategoria.addEventListener("change", () => {
      categoriaFiltro = filtroCategoria.value;
      renderizarTablero();
    });

    // Limpiar filtros activos
    btnLimpiarFiltros.addEventListener("click", () => {
      clearTimeout(temporizadorBusqueda);
      buscadorTexto.value = "";
      filtroCategoria.value = "";
      textoBusqueda = "";
      categoriaFiltro = "";
      renderizarTablero();
      buscadorTexto.focus();
    });

    // Exportar e importar JSON
    btnExportar.addEventListener("click", exportarJSON);
    btnImportar.addEventListener("click", () => inputImportar.click());
    inputImportar.addEventListener("change", manejarSeleccionArchivoJSON);

    // Botón para restablecer almacenamiento dañado (con confirmación)
    btnLimpiarCorrupto.addEventListener("click", () => {
      abrirConfirmacion(
        "Restablecer almacenamiento",
        "Esto eliminará los datos dañados guardados en localStorage para iniciar una agenda vacía. ¿Deseas continuar?",
        "Sí, restablecer",
        () => {
          if (guardarEventos([])) {
            almacenamientoDanado = false;
            eventos = [];
            actualizarBannerAlmacenamientoDanado("");
            renderizarTablero();
            mostrarMensajeEstado("Almacenamiento restablecido correctamente.", "info");
          }
        }
      );
    });

    // Controles del diálogo modal de confirmación
    modalBtnCancelar.addEventListener("click", cerrarConfirmacion);
    modalBtnConfirmar.addEventListener("click", () => {
      const callback = accionConfirmacionPendiente;
      cerrarConfirmacion();
      if (typeof callback === "function") {
        callback();
      }
    });
    modalConfirmacion.addEventListener("cancel", () => {
      accionConfirmacionPendiente = null;
    });
  }

  /* ========================================================================
     9. Arranque de la aplicación
     ======================================================================== */
  function iniciar() {
    const resultadoCarga = cargarEventos();
    eventos = resultadoCarga.eventos;
    almacenamientoDanado = resultadoCarga.danado;

    actualizarBannerAlmacenamientoDanado(resultadoCarga.mensajeError);
    actualizarContadoresCaracteres();
    inicializarEventosDOM();
    renderizarTablero();
  }

  document.addEventListener("DOMContentLoaded", iniciar);
})();
