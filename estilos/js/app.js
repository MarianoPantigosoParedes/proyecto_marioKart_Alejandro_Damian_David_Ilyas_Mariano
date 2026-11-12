function obtenerEtiqueta(campo) {
    if (campo.labels.length > 0) {
        return campo.labels[0].textContent.trim();
    }

    return "Este campo";
}

function textoErrorCampo(campo) {
    var etiqueta = obtenerEtiqueta(campo);
    var valor = campo.value.trim();

    if (valor === "") {
        return "";
    }

    if (campo.type === "email" && valor.indexOf("@") === -1) {
        return etiqueta + ": revisa el formato.";
    }

    if (campo.pattern && !new RegExp("^" + campo.pattern + "$").test(valor)) {
        return etiqueta + ": revisa el formato.";
    }

    if (campo.minLength > 0 && valor.length < campo.minLength) {
        return etiqueta + ": el valor es inferior al mínimo permitido.";
    }

    if (campo.maxLength > 0 && valor.length > campo.maxLength) {
        return etiqueta + ": el valor supera el máximo permitido.";
    }

    if (campo.type === "number") {
        if (isNaN(valor)) {
            return etiqueta + ": introduce un valor válido.";
        }

        if (campo.min !== "" && Number(valor) < Number(campo.min)) {
            return etiqueta + ": el valor es inferior al mínimo permitido.";
        }

        if (campo.max !== "" && Number(valor) > Number(campo.max)) {
            return etiqueta + ": el valor supera el máximo permitido.";
        }
    }

    return "";
}

function obtenerCamposFormulario(formulario) {
    var campos = [];
    var elementos = formulario.elements;
    var i;
    var campo;

    for (i = 0; i < elementos.length; i += 1) {
        campo = elementos[i];
        if (
            campo.matches("input, select, textarea")
            && !campo.disabled
            && campo.type !== "hidden"
        ) {
            campos.push(campo);
        }
    }

    return campos;
}

function borrarAvisoCampo(campo) {
    var aviso = campo.nextElementSibling;

    if (aviso && aviso.classList.contains("aviso-campo")) {
        aviso.remove();
    }

    if (campo.getAttribute("aria-describedby") === "aviso-validacion-gestion") {
        campo.removeAttribute("aria-describedby");
    }
}

function obtenerCamposInvalidos(formulario) {
    var campos = formulario.querySelectorAll("input, select, textarea");
    var invalidos = [];
    var i;

    for (i = 0; i < campos.length; i += 1) {
        if (campos[i].getAttribute("aria-invalid") === "true") {
            invalidos.push(campos[i]);
        }
    }

    return invalidos;
}

function eliminarAvisoGestion() {
    var aviso = document.querySelector("#aviso-validacion-gestion");

    if (aviso) {
        aviso.remove();
    }
}

function limpiarErrorCampo(campo) {
    var formulario;
    var invalidos;
    var mensajes = [];
    var i;

    if (!campo) {
        return;
    }

    campo.removeAttribute("aria-invalid");
    borrarAvisoCampo(campo);

    formulario = campo.form;
    if (!formulario || formulario.dataset.validacionCompacta !== "true") {
        return;
    }

    invalidos = obtenerCamposInvalidos(formulario);
    for (i = 0; i < invalidos.length; i += 1) {
        mensajes.push(textoErrorCampo(invalidos[i]));
    }

    if (invalidos.length === 0) {
        eliminarAvisoGestion();
    } else {
        mostrarErrorGestion(mensajes, invalidos);
    }
}

function mostrarErrorGestion(errores, invalidos) {
    var aviso = document.querySelector("#aviso-validacion-gestion");
    var titulo;
    var lista;
    var elemento;
    var i;

    if (!aviso) {
        aviso = document.createElement("div");
        aviso.id = "aviso-validacion-gestion";
        aviso.className = "aviso-validacion-gestion";
        aviso.setAttribute("role", "alert");
        aviso.setAttribute("aria-live", "assertive");
        document.body.append(aviso);
    }

    aviso.replaceChildren();
    titulo = document.createElement("strong");
    titulo.textContent = "Revisa los campos marcados";
    lista = document.createElement("ul");

    for (i = 0; i < errores.length; i += 1) {
        elemento = document.createElement("li");
        elemento.textContent = errores[i];
        lista.append(elemento);
    }

    aviso.append(titulo, lista);

    for (i = 0; i < invalidos.length; i += 1) {
        invalidos[i].setAttribute("aria-describedby", aviso.id);
    }
}

function esCampoDeTexto(campo) {
    var tiposTexto = ["text", "search", "email", "url", "tel"];

    return tiposTexto.indexOf(campo.type) !== -1
        || campo.tagName === "TEXTAREA";
}

function aplicarReglasEspeciales(campo) {
    var bytes;

    campo.setCustomValidity("");

    if (esCampoDeTexto(campo) && /[\u0000-\u001f\u007f]/u.test(campo.value)) {
        campo.setCustomValidity("El campo contiene caracteres no permitidos.");
        return;
    }

    if (campo.name === "password" && campo.value !== "") {
        bytes = new TextEncoder().encode(campo.value).length;
        if (bytes < 12 || bytes > 72) {
            campo.setCustomValidity("La contraseña debe tener entre 12 y 72 bytes.");
        }
    }
}

function obtenerAvisoFormulario(formulario, compacto) {
    var aviso = formulario.querySelector(".aviso-formulario");

    if (!compacto && !aviso) {
        aviso = document.createElement("p");
        aviso.className = "aviso aviso-error aviso-formulario";
        aviso.setAttribute("role", "alert");
        formulario.prepend(aviso);
    }

    return aviso;
}

function validarFormulario(formulario, evento) {
    var campos = obtenerCamposFormulario(formulario);
    var invalidos = [];
    var errores = [];
    var compacto;
    var resumenError;
    var i;

    for (i = 0; i < campos.length; i += 1) {
        aplicarReglasEspeciales(campos[i]);
        if (!campos[i].checkValidity()) {
            invalidos.push(campos[i]);
            errores.push(textoErrorCampo(campos[i]));
        }
    }

    compacto = formulario.classList.contains("formulario-gestion")
        || formulario.hasAttribute("data-validacion-gestion");
    formulario.dataset.validacionCompacta = compacto ? "true" : "false";
    resumenError = obtenerAvisoFormulario(formulario, compacto);

    if (invalidos.length === 0) {
        if (resumenError) {
            resumenError.hidden = true;
        }
        return true;
    }

    evento.preventDefault();

    for (i = 0; i < invalidos.length; i += 1) {
        limpiarErrorCampo(invalidos[i]);
        invalidos[i].setAttribute("aria-invalid", "true");
    }

    if (compacto) {
        mostrarErrorGestion(errores, invalidos);
    } else {
        for (i = 0; i < invalidos.length; i += 1) {
            var avisoCampo = document.createElement("small");
            avisoCampo.className = "aviso-campo";
            avisoCampo.textContent = errores[i];
            invalidos[i].insertAdjacentElement("afterend", avisoCampo);
        }

        resumenError.textContent = invalidos.length === 1
            ? "Hay un dato que necesita revisión."
            : "Hay " + invalidos.length + " datos que necesitan revisión.";
        resumenError.hidden = false;
    }

    invalidos[0].focus();
    return false;
}

function obtenerOpciones(select) {
    var opciones = [];
    var i;

    for (i = 0; i < select.options.length; i += 1) {
        opciones.push(select.options[i]);
    }

    return opciones;
}

function borrarSeleccionSiEstaOculta(select) {
    var seleccion = select.options[select.selectedIndex];

    if (seleccion && seleccion.hidden) {
        select.value = "";
    }
}

function limpiarFiltroFormulario(formulario) {
    formulario.reset();
    window.location.href = formulario.action;
}

function limpiarFiltroSeccion(seccion) {
    var campos = seccion.querySelectorAll("input, select");
    var selects = seccion.querySelectorAll("select");
    var i;

    for (i = 0; i < campos.length; i += 1) {
        campos[i].value = "";
    }

    for (i = 0; i < selects.length; i += 1) {
        if (selects[i].options.length > 0) {
            selects[i].selectedIndex = 0;
        }
    }

    filtrarSeccionesLocal();
}

function aplicarFiltrosModulos(formulario) {
    var filtroZona = formulario.querySelector("[data-filtro-zona]");
    var filtroSala = formulario.querySelector('[name="sala"]');
    var filtroEstado = formulario.querySelector('[name="estado"]');
    var modulos = document.querySelectorAll("[data-modulo-sala]");
    var opcionesSala = obtenerOpciones(filtroSala);
    var salas = document.querySelectorAll("[data-tarjeta-sala]");
    var zonas = document.querySelectorAll("[data-zona-distribucion]");
    var visibles = 0;
    var estadoElegido = filtroEstado.value === "libres" ? "libre" : "ocupado";
    var i;
    var modulo;
    var coincideZona;
    var coincideSala;
    var coincideEstado;
    var modulosSala;
    var j;
    var hayModuloVisible;

    for (i = 0; i < opcionesSala.length; i += 1) {
        opcionesSala[i].hidden = Boolean(filtroZona.value)
            && Boolean(opcionesSala[i].dataset.zona)
            && opcionesSala[i].dataset.zona !== filtroZona.value;
    }
    borrarSeleccionSiEstaOculta(filtroSala);

    for (i = 0; i < modulos.length; i += 1) {
        modulo = modulos[i];
        coincideZona = !filtroZona.value
            || modulo.dataset.moduloZona === filtroZona.value;
        coincideSala = !filtroSala.value
            || modulo.dataset.moduloSala === filtroSala.value;
        coincideEstado = filtroEstado.value === "todos"
            || modulo.dataset.moduloEstado === estadoElegido;
        modulo.hidden = !coincideZona || !coincideSala || !coincideEstado;

        if (!modulo.hidden) {
            visibles += 1;
        }
    }

    for (i = 0; i < salas.length; i += 1) {
        modulosSala = salas[i].querySelectorAll("[data-modulo-sala]");
        hayModuloVisible = false;

        for (j = 0; j < modulosSala.length; j += 1) {
            if (!modulosSala[j].hidden) {
                hayModuloVisible = true;
                break;
            }
        }

        salas[i].hidden = modulosSala.length > 0 && !hayModuloVisible;
    }

    for (i = 0; i < zonas.length; i += 1) {
        zonas[i].hidden = !zonas[i].querySelector("[data-tarjeta-sala]:not([hidden])");
    }

    var conteoModulos = document.querySelector("[data-conteo-modulos]");
    if (conteoModulos) {
        conteoModulos.textContent = visibles + " módulo(s) visibles";
    }
}

function ajustarFiltrosHistorial(formulario) {
    var filtroZona = formulario.querySelector("[data-filtro-zona-historial]");
    var filtroSala = formulario.querySelector("[data-filtro-sala]");
    var filtroModulo = formulario.querySelector("[data-filtro-modulo]");
    var opcionesSala = obtenerOpciones(filtroSala);
    var opcionesModulo = obtenerOpciones(filtroModulo);
    var zonaElegida = filtroZona.value;
    var i;
    var coincideZona;
    var coincideSala;

    for (i = 0; i < opcionesSala.length; i += 1) {
        opcionesSala[i].hidden = Boolean(zonaElegida)
            && Boolean(opcionesSala[i].dataset.zona)
            && opcionesSala[i].dataset.zona !== zonaElegida;
    }
    borrarSeleccionSiEstaOculta(filtroSala);

    for (i = 0; i < opcionesModulo.length; i += 1) {
        coincideZona = !zonaElegida
            || !opcionesModulo[i].dataset.zona
            || opcionesModulo[i].dataset.zona === zonaElegida;
        coincideSala = !filtroSala.value
            || !opcionesModulo[i].dataset.sala
            || opcionesModulo[i].dataset.sala === filtroSala.value;
        opcionesModulo[i].hidden = !coincideZona || !coincideSala;
    }
    borrarSeleccionSiEstaOculta(filtroModulo);
}

function normalizarTexto(texto) {
    return texto
        .normalize("NFD")
        .replace(/[\u0300-\u036f]/g, "")
        .toLocaleLowerCase("es")
        .trim();
}

function obtenerValorCampo(contenedor, selector, valorDefecto) {
    var campo = contenedor.querySelector(selector);

    return campo ? campo.value : valorDefecto;
}

function filtrarSeccionesLocal() {
    var filtros = document.querySelectorAll(".filtro-seccion");
    var i;
    var filtro;
    var seccion;
    var busqueda;
    var estado;
    var zona;
    var relacion;
    var elementos;
    var visibles;
    var j;
    var elemento;
    var textoCoincide;
    var estadoCoincide;
    var zonaCoincide;
    var relacionCoincide;
    var contador;
    var zonasAdministracion;
    var salasVisibles;
    var zonasVisibles;
    var contadorZonas;
    var salas;
    var contadorSalas;

    for (i = 0; i < filtros.length; i += 1) {
        filtro = filtros[i];
        seccion = filtro.dataset.filtroSeccion;
        busqueda = normalizarTexto(obtenerValorCampo(filtro, 'input[type="search"]', ""));
        estado = obtenerValorCampo(filtro, "[data-filtro-estado]", "todos");
        zona = obtenerValorCampo(filtro, "[data-filtro-zona]", "");
        relacion = obtenerValorCampo(filtro, "[data-filtro-relacion]", "");
        elementos = document.querySelectorAll('[data-filtro-item="' + seccion + '"]');
        visibles = 0;

        for (j = 0; j < elementos.length; j += 1) {
            elemento = elementos[j];
            textoCoincide = !busqueda
                || normalizarTexto(elemento.dataset.filtroTexto || "").indexOf(busqueda) !== -1;
            estadoCoincide = estado === "todos"
                || (estado === "activos" && elemento.dataset.filtroEstado === "activo")
                || (estado === "archivados" && elemento.dataset.filtroEstado === "archivado");
            zonaCoincide = !zona || elemento.dataset.filtroZona === zona;
            relacionCoincide = !relacion || elemento.dataset.filtroRelacion === relacion;
            elemento.hidden = !textoCoincide
                || !estadoCoincide
                || !zonaCoincide
                || !relacionCoincide;

            if (!elemento.hidden) {
                visibles += 1;
            }
        }

        contador = filtro.querySelector("[data-filtro-conteo]");
        if (contador) {
            contador.textContent = visibles + " resultado(s)";
        }
    }

    zonasAdministracion = document.querySelectorAll(".zona-administracion[data-filtro-item='zonas']");
    for (i = 0; i < zonasAdministracion.length; i += 1) {
        salasVisibles = zonasAdministracion[i].querySelector("[data-filtro-item='salas']:not([hidden])");
        zonasAdministracion[i].hidden = !salasVisibles;
    }

    zonasVisibles = document.querySelectorAll(".zona-administracion[data-filtro-item='zonas']:not([hidden])").length;
    contadorZonas = document.querySelector('[data-filtro-seccion="zonas"] [data-filtro-conteo]');
    if (contadorZonas) {
        contadorZonas.textContent = zonasVisibles + " resultado(s)";
    }

    salas = document.querySelectorAll('.zona-administracion [data-filtro-item="salas"]');
    salasVisibles = 0;
    for (i = 0; i < salas.length; i += 1) {
        if (!salas[i].hidden && !salas[i].closest(".zona-administracion[hidden]")) {
            salasVisibles += 1;
        }
    }

    contadorSalas = document.querySelector('[data-filtro-seccion="salas"] [data-filtro-conteo]');
    if (contadorSalas) {
        contadorSalas.textContent = salasVisibles + " resultado(s)";
    }
}

function confirmarEnvio(formulario, evento) {
    var enviar;

    if (formulario.dataset.confirmado === "si") {
        return true;
    }

    evento.preventDefault();
    enviar = function () {
        formulario.dataset.confirmado = "si";
        formulario.submit();
    };

    if (window.Swal) {
        window.Swal.fire({
            title: "¿Continuar?",
            text: formulario.dataset.confirmacion,
            icon: "question",
            showCancelButton: true,
            confirmButtonText: "Sí, continuar",
            cancelButtonText: "Cancelar",
            confirmButtonColor: "#18c8ff",
            cancelButtonColor: "#28436a",
            background: "#0c1730",
            color: "#f4f7fb"
        }).then(function (resultado) {
            if (resultado.isConfirmed) {
                enviar();
            }
        });
    } else if (window.confirm(formulario.dataset.confirmacion)) {
        enviar();
    }

    return false;
}

function mostrarAvisoEstado() {
    var aviso = document.querySelector(".aviso[role='status']");

    if (!aviso || !window.Swal) {
        return;
    }

    window.Swal.fire({
        title: aviso.classList.contains("aviso-error") ? "No se pudo completar" : "Hecho",
        text: aviso.textContent.trim(),
        icon: aviso.classList.contains("aviso-error") ? "error" : "success",
        confirmButtonText: "Aceptar",
        confirmButtonColor: "#18c8ff",
        background: "#0c1730",
        color: "#f4f7fb"
    });
}

function iniciarFiltros() {
    var formularioModulos = document.querySelector("[data-filtros-modulos]");
    var formularioHistorial = document.querySelector("[data-filtros-historial]");

    mostrarAvisoEstado();

    if (formularioModulos && document.querySelector("[data-modulo-sala]")) {
        aplicarFiltrosModulos(formularioModulos);
    }

    if (formularioHistorial) {
        ajustarFiltrosHistorial(formularioHistorial);
    }

    if (document.querySelector(".filtro-seccion")) {
        filtrarSeccionesLocal();
    }
}

iniciarFiltros();
