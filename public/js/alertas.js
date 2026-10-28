/**
 * alertas.js — Centro único de alertas del sistema (SweetAlert2)
 *
 * USO:
 *   switchAlert('eliminar_usuario', { id: 5, nombre: 'Ana' })
 *       .then(res => { if (res.isConfirmed) { ... } });
 *
 *   // Para enlaces <a> que necesitan confirmación:
 *   <a href="..." onclick="return switchAlertLink(event, 'aprobar_excusa')">
 *
 * Toda alerta, validación o confirmación del sistema vive en el `switch`
 * de abajo. Si necesitas un mensaje nuevo, agrega un `case` y listo.
 */
(function (global) {
  "use strict";

  // ── Colores unificados ───────────────────────────────────────────
  const COLOR = {
    primario: "#059669",
    peligro: "#dc2626",
    neutro: "#64748b",
  };

  // ── Utilidades ───────────────────────────────────────────────────
  // Escapa HTML para que nombres o textos del usuario no rompan la alerta
  function esc(valor) {
    return String(valor ?? "")
      .replace(/&/g, "&amp;")
      .replace(/</g, "&lt;")
      .replace(/>/g, "&gt;")
      .replace(/"/g, "&quot;");
  }

  // Alerta informativa (un solo botón "Entendido")
  function aviso(icon, title, html, extra) {
    return Swal.fire(
      Object.assign(
        {
          icon: icon,
          title: title,
          html: html,
          confirmButtonColor: COLOR.primario,
          confirmButtonText: "Entendido",
        },
        extra || {},
      ),
    );
  }

  // Alerta que se cierra sola (éxito)
  function autoCierre(icon, title, html, ms) {
    return Swal.fire({
      icon: icon,
      title: title,
      html: html,
      timer: ms || 3000,
      timerProgressBar: true,
      showConfirmButton: false,
    });
  }

  // Pregunta con botones Confirmar / Cancelar
  function confirmar(icon, title, html, textoConfirmar, colorConfirmar) {
    return Swal.fire({
      icon: icon,
      title: title,
      html: html,
      showCancelButton: true,
      confirmButtonColor: colorConfirmar || COLOR.peligro,
      cancelButtonColor: COLOR.neutro,
      confirmButtonText: textoConfirmar,
      cancelButtonText: "Cancelar",
    });
  }

  // Pantalla de "cargando"
  function cargando(title, texto) {
    return Swal.fire({
      title: title,
      text: texto,
      allowOutsideClick: false,
      didOpen: function () {
        Swal.showLoading();
      },
    });
  }

  // ═════════════════════════════════════════════════════════════════
  //  SWITCH ALERT — todas las alertas del sistema
  // ═════════════════════════════════════════════════════════════════
  function switchAlert(caso, d) {
    d = d || {};

    switch (caso) {
      // ─────────── CONFIRMACIONES (el usuario debe aceptar) ───────────
      case "eliminar_usuario":
        return confirmar(
          "warning",
          "¿Eliminar usuario?",
          '¿Estás seguro de eliminar a "<strong>' +
            esc(d.nombre) +
            '</strong>"? Esta acción no se puede deshacer.',
          "Sí, eliminar",
        );

      case "retirar_aprendiz":
        return confirmar(
          "warning",
          "¿Retirar aprendiz?",
          'El aprendiz "<strong>' +
            esc(d.nombre) +
            '</strong>" quedará en estado <strong>Retirado</strong> y no aparecerá en las listas de asistencia.',
          '<i class="bi bi-person-x me-1"></i>Sí, retirar aprendiz',
        );

      case "reactivar_aprendiz":
        return confirmar(
          "info",
          "¿Reactivar aprendiz?",
          'El aprendiz "<strong>' +
            esc(d.nombre) +
            '</strong>" volverá a estar <strong>Activo</strong> en el sistema y en las listas de asistencia.',
          '<i class="bi bi-arrow-counterclockwise me-1"></i>Sí, reactivar aprendiz',
          COLOR.primario,
        );

      case "eliminar_horario":
        return confirmar(
          "warning",
          "¿Eliminar horario completo?",
          "Esta acción eliminará todos los bloques de horario registrados para la <strong>Ficha " +
            esc(d.ficha) +
            "</strong>.<br><br>" +
            "Permitirá cargar un horario nuevo en caso de haber subido el archivo a la ficha equivocada.<br><br>" +
            '<span style="color:#dc2626; font-weight:600;">Acción exclusiva para el Administrador.</span>',
          '<i class="bi bi-trash3 me-1"></i>Sí, eliminar horario',
        );

      case "ocultar_ficha":
        return confirmar(
          "question",
          "¿Finalizar y Ocultar Ficha?",
          "La ficha N° " +
            esc(d.numero) +
            ' pasará al apartado de "Fichas Finalizadas / Ocultas". Podrás consultar o reactivar su registro en cualquier momento.',
          "Sí, finalizar y ocultar",
        );

      case "reactivar_ficha":
        return confirmar(
          "info",
          "¿Reactivar Ficha?",
          "La ficha N° " +
            esc(d.numero) +
            " volverá a estar activa en el sistema.",
          "Sí, reactivar",
          COLOR.primario,
        );

      case "cerrar_jornada":
        return confirmar(
          "question",
          "¿Cerrar la jornada de hoy?",
          "Se limpiarán los registros de aprendices correspondientes a hoy.",
          "Sí, cerrar jornada",
          COLOR.primario,
        );

      case "aprobar_excusa":
        return confirmar(
          "question",
          "¿Aprobar esta excusa médica?",
          "La excusa quedará registrada como aprobada para las clases a tu cargo.",
          "Sí, aprobar",
          COLOR.primario,
        );

      case "rechazar_excusa":
        return Swal.fire({
          icon: "warning",
          title: "¿Rechazar esta excusa médica?",
          html: '<p style="margin-bottom:0.75rem; font-size:0.875rem; color:#64748b;">Indica el motivo por el cual rechazas esta excusa para que el aprendiz pueda conocer la razón:</p>',
          input: "textarea",
          inputPlaceholder: "Escribe aquí la razón o motivo del rechazo...",
          inputAttributes: {
            "aria-label": "Motivo del rechazo",
            rows: 3,
            style: "font-size:0.875rem;",
          },
          inputValidator: function (value) {
            if (!value || !value.trim()) {
              return "Debes ingresar un motivo para poder rechazar la excusa.";
            }
          },
          showCancelButton: true,
          confirmButtonColor: COLOR.peligro,
          cancelButtonColor: COLOR.neutro,
          confirmButtonText:
            '<i class="bi bi-x-circle me-1"></i>Rechazar Excusa',
          cancelButtonText: "Cancelar",
        });

      case "confirmar_generico": // usado por los botones con data-confirm
        return confirmar(
          "question",
          "¿Estás seguro?",
          esc(d.mensaje || "¿Deseas continuar?"),
          "Sí, continuar",
          COLOR.primario,
        );

      // ─────────── VALIDACIONES (el usuario olvidó algo) ───────────
      case "ficha_no_seleccionada":
        return aviso(
          "warning",
          "Ficha no seleccionada",
          "Por favor selecciona la ficha de formación en el menú desplegable antes de continuar.",
        );

      case "archivo_no_seleccionado":
        return aviso(
          "warning",
          "Archivo no seleccionado",
          "Selecciona un archivo Excel (.xlsx) para cargar.",
        );

      case "formato_invalido_xlsx":
        return aviso(
          "error",
          "Formato no válido",
          "Solo se admiten archivos en formato Excel (.xlsx).",
        );

      case "formato_invalido_aprendices":
        return aviso(
          "error",
          "Formato no soportado",
          "Por favor selecciona un archivo con extensión .xls, .xlsx o .csv",
        );

      case "archivo_vacio":
        return aviso(
          "warning",
          "Archivo Vacío",
          "El archivo Excel no contiene filas o datos legibles.",
        );

      case "sin_datos_importar":
        return aviso(
          "warning",
          "Sin datos",
          "No hay aprendices válidos para importar.",
        );

      case "error_leer_excel":
        return aviso(
          "error",
          "Error al procesar Excel",
          "No se pudo leer la estructura del archivo. " + esc(d.detalle),
        );

      case "ficha_con_aprendices":
        return aviso(
          "warning",
          "No se puede ocultar la ficha",
          "La ficha N° <strong>" +
            esc(d.numero) +
            "</strong> aún tiene <strong>" +
            esc(d.cantidad || "varios") +
            "</strong> aprendices registrados.<br><br>" +
            "Para mantener la trazabilidad e historial, no se puede ocultar mientras tenga aprendices vinculados.",
        );

      // ─────────── RESULTADO DEL ESCÁNER DE HORARIO ───────────
      case "horario_existente_admin":
        return Swal.fire({
          icon: "warning",
          title: "Horario Rechazado: Ya Registrado",
          html:
            '<div style="text-align:left; font-size:0.9rem; line-height:1.5;">' +
            "<p>La <strong>Ficha " +
            esc(d.ficha) +
            "</strong> ya tiene un horario vinculado con <strong>" +
            esc(d.bloques || 0) +
            "</strong> bloques de clase.</p>" +
            '<p style="color:#dc2626; font-weight:600;">Para evitar duplicidades o sobreescritura accidental, no es posible subir otro horario encima.</p>' +
            "<p>Como <strong>Administrador</strong>, si subiste el archivo a la ficha equivocada o necesitas actualizarlo, puedes eliminar el horario actual para permitir la nueva subida.</p>" +
            "</div>",
          showCancelButton: true,
          confirmButtonText:
            '<i class="bi bi-trash3 me-1"></i>Eliminar Horario Anterior',
          cancelButtonText: "Cancelar",
          confirmButtonColor: COLOR.peligro,
          cancelButtonColor: COLOR.neutro,
        });

      case "horario_existente_instructor":
        return Swal.fire({
          icon: "error",
          title: "Horario Rechazado: Ya Vinculado",
          html:
            '<div style="text-align:left; font-size:0.9rem; line-height:1.5;">' +
            "<p>La <strong>Ficha " +
            esc(d.ficha) +
            "</strong> ya cuenta con un horario registrado con <strong>" +
            esc(d.bloques || 0) +
            "</strong> bloques de clase.</p>" +
            '<p style="color:#d97706; font-weight:600;">Para evitar molestias o sobreescrituras, el sistema no permite subir otro horario sobre esta ficha.</p>' +
            "<p>En caso de haberse subido un horario a la ficha equivocada, únicamente el <strong>Administrador</strong> tiene la potestad de eliminarlo.</p>" +
            "</div>",
          confirmButtonColor: COLOR.primario,
        });

      case "error_escaner":
        return aviso(
          "error",
          "Error en el Escáner",
          esc(d.mensaje || "Ocurrió un error al procesar el archivo."),
        );

      case "error_comunicacion":
        return aviso(
          "error",
          "Error de Comunicación",
          esc(d.mensaje || "No se pudo conectar con el servidor."),
        );

      case "error_importar_aprendices":
        return aviso(
          "error",
          "Error al importar",
          esc(d.mensaje || "Ocurrió un error al procesar los aprendices."),
        );

      case "horario_importado":
        return Swal.fire({
          icon: "success",
          title: "¡Horario Importado!",
          html:
            "Se registraron <strong>" +
            esc(d.bloques) +
            "</strong> bloques y <strong>" +
            esc(d.nuevos) +
            "</strong> instructores nuevos con clave <code>12345</code> en la ficha <strong>" +
            esc(d.ficha) +
            "</strong>.",
          confirmButtonColor: COLOR.primario,
          confirmButtonText: "Ver Horario en Vivo",
        });

      case "aprendices_importados":
        return Swal.fire({
          icon: "success",
          title: "¡Aprendices Registrados con Éxito!",
          html:
            '<div style="text-align:left; font-size:0.9rem; line-height:1.5;">' +
            "<p>Se procesaron <strong>" +
            esc(d.total) +
            "</strong> registros en la <strong>Ficha " +
            esc(d.ficha) +
            "</strong>.</p>" +
            '<ul style="margin:0; padding-left:1.25rem;">' +
            "<li><strong>" +
            esc(d.nuevos) +
            "</strong> perfiles nuevos creados con contraseña predeterminada <code>sena2025</code>.</li>" +
            "<li><strong>" +
            esc(d.existentes) +
            "</strong> aprendices ya estaban vinculados (omitidos para evitar duplicados).</li>" +
            "<li><strong>" +
            esc(d.nulls) +
            "</strong> registros tenían campos opcionales vacíos y se guardaron como <code>NULL</code>.</li>" +
            "<li>Tarjetas RFID configuradas en <code>NULL</code> para que el Administrador las asigne manualmente.</li>" +
            "</ul></div>",
          confirmButtonColor: COLOR.primario,
          confirmButtonText:
            '<i class="bi bi-people-fill me-1"></i>Ver Aprendices en la Ficha',
        });

      // ─────────── ELIMINACIÓN DE HORARIO (proceso) ───────────
      case "eliminando_horario":
        return cargando(
          "Eliminando horario...",
          d.texto || "Por favor espera mientras se limpian los bloques.",
        );

      case "horario_eliminado":
        return aviso(
          "success",
          "Horario Eliminado",
          esc(d.mensaje || "Se eliminó el horario de la ficha correctamente."),
        );

      case "error_eliminar_horario":
        return aviso(
          "error",
          "No se pudo eliminar",
          esc(d.mensaje || "Error al intentar eliminar el horario."),
        );

      // ─────────── AVISOS RÁPIDOS (se cierran solos) ───────────
      case "credenciales_copiadas":
        return Swal.fire({
          toast: true,
          position: "top-end",
          icon: "success",
          title: "Credenciales copiadas al portapapeles",
          showConfirmButton: false,
          timer: 2000,
        });

      case "ficha_finalizada":
        return autoCierre(
          "success",
          "¡Ficha Finalizada y Oculta!",
          "La ficha ha sido trasladada al apartado de Fichas Finalizadas.",
        );

      case "ficha_reactivada":
        return autoCierre(
          "success",
          "¡Ficha Reactivada!",
          "La ficha vuelve a estar disponible entre las fichas activas.",
        );

      case "ficha_guardada":
        return autoCierre(
          "success",
          "¡Ficha Guardada!",
          "La información de la ficha de formación ha sido guardada con éxito.",
        );

      case "usuario_registrado":
        return autoCierre(
          "success",
          "¡Usuario Creado!",
          "El usuario ha sido registrado correctamente en el sistema.",
        );

      case "usuario_actualizado":
        return autoCierre(
          "success",
          "¡Usuario Actualizado!",
          "Los datos del usuario han sido actualizados con éxito.",
        );

      case "usuario_eliminado":
        return autoCierre(
          "success",
          "¡Usuario Eliminado!",
          "El usuario ha sido eliminado correctamente.",
        );

      case "aprendiz_retirado":
        return autoCierre(
          "success",
          "¡Aprendiz Retirado!",
          "El aprendiz ha sido retirado correctamente y no saldrá en las listas de asistencia.",
        );

      case "aprendiz_reactivado":
        return autoCierre(
          "success",
          "¡Aprendiz Reactivado!",
          "El aprendiz ha sido reactivado con éxito.",
        );

      case "operacion_exitosa":
        return autoCierre(
          "success",
          "¡Éxito!",
          "Operación realizada con éxito.",
        );

      // ─────────── MENSAJES QUE VIENEN DEL SERVIDOR ($_SESSION['mensaje']) ───────────
      case "mensaje_servidor": {
        // módulo deserción
        const esError = d.tipo === "error";
        return Swal.fire({
          icon: d.tipo || "success",
          title: esError ? "Error" : "Operación Exitosa",
          text: d.texto || "",
          timer: 4000,
          timerProgressBar: true,
        });
      }

      case "registro_asistencia": {
        // módulo de registro RFID
        const porTipo = {
          success: { icon: "success", title: "¡Registro Exitoso!" },
          warning: { icon: "warning", title: "Atención" },
          error: { icon: "error", title: "Ingreso no registrado" },
        };
        const cfg = porTipo[d.tipo] || porTipo.success;
        return Swal.fire({
          icon: cfg.icon,
          title: cfg.title,
          text: d.texto || "",
          timer: 4000,
          timerProgressBar: true,
          showConfirmButton: false,
        });
      }

      // ─────────── CASO NO REGISTRADO ───────────
      default:
        console.warn("switchAlert: caso no definido ->", caso);
        return Swal.fire({
          icon: "info",
          title: "Aviso",
          text: d.mensaje || "",
          confirmButtonColor: COLOR.primario,
        });
    }
  }

  // ═════════════════════════════════════════════════════════════════
  //  Enlaces <a> con confirmación (reemplaza onclick="return confirm()")
  // ═════════════════════════════════════════════════════════════════
  function switchAlertLink(evento, caso, datos) {
    evento.preventDefault();
    const destino = evento.currentTarget.href;
    switchAlert(caso, datos).then(function (res) {
      if (res.isConfirmed) {
        let url = destino;
        if (
          res.value &&
          typeof res.value === "string" &&
          res.value.trim() !== ""
        ) {
          url +=
            (url.includes("?") ? "&" : "?") +
            "motivo=" +
            encodeURIComponent(res.value.trim());
        } else if (
          res.value &&
          typeof res.value === "object" &&
          res.value.motivo
        ) {
          url +=
            (url.includes("?") ? "&" : "?") +
            "motivo=" +
            encodeURIComponent(res.value.motivo.trim());
        }
        window.location.href = url;
      }
    });
    return false; // bloquea la navegación hasta que el usuario confirme
  }

  // ═════════════════════════════════════════════════════════════════
  //  Barra de progreso en estado RECHAZADO (todo en rojo)
  //  Las clases .paso-error / .progreso-error-fill / .panel-simulacion-error
  //  están definidas en public/css/styles.css
  // ═════════════════════════════════════════════════════════════════
  function marcarProgresoRechazado(o) {
    // Panel completo en rojo
    if (o.panel) o.panel.classList.add("panel-simulacion-error");

    // Barra llena y roja
    if (o.barra) {
      o.barra.style.width = "100%";
      o.barra.classList.add("progreso-error-fill");
    }
    if (o.porcentaje) {
      o.porcentaje.textContent = "✕";
      o.porcentaje.classList.add("texto-error");
    }

    // Todos los pasos en rojo con ícono de error
    (o.pasos || []).forEach(function (paso) {
      paso.classList.remove("paso-activo", "paso-completado");
      paso.classList.add("paso-error");
      const icono = paso.querySelector("i");
      if (icono) icono.className = "bi bi-x-circle-fill";
    });

    // Quitar el spinner y cambiar los textos de la cabecera
    if (o.spinner) o.spinner.style.display = "none";
    if (o.titulo) o.titulo.textContent = o.textoTitulo || "Proceso rechazado";
    if (o.titulo) o.titulo.classList.add("texto-error");
    if (o.subtitulo) o.subtitulo.textContent = o.textoSubtitulo || "";
    if (o.botonCerrar) o.botonCerrar.style.display = "inline-flex";
  }

  // Devuelve la barra a su estado normal (verde) antes de un nuevo intento
  function reiniciarProgreso(o) {
    if (o.panel) o.panel.classList.remove("panel-simulacion-error");
    if (o.barra) {
      o.barra.classList.remove("progreso-error-fill");
      o.barra.style.width = "0%";
    }
    if (o.porcentaje) {
      o.porcentaje.textContent = "0%";
      o.porcentaje.classList.remove("texto-error");
    }
    (o.pasos || []).forEach(function (paso) {
      paso.classList.remove("paso-activo", "paso-completado", "paso-error");
      const icono = paso.querySelector("i");
      if (icono) icono.className = "bi bi-circle text-muted";
    });
    if (o.spinner) o.spinner.style.display = "inline-block";
    if (o.titulo) {
      o.titulo.textContent = o.textoTituloOriginal || o.titulo.textContent;
      o.titulo.classList.remove("texto-error");
    }
    if (o.subtitulo) o.subtitulo.textContent = o.textoSubtituloOriginal || "";
    if (o.botonCerrar) o.botonCerrar.style.display = "none";
  }
  // Envía una acción por POST con token CSRF (reemplaza window.location.href = "...&id=..")
  function enviarPost(url, datos) {
    const token =
      document.querySelector('meta[name="csrf-token"]')?.content || "";
    const f = document.createElement("form");
    f.method = "POST";
    f.action = url;
    const add = (n, v) => {
      const i = document.createElement("input");
      i.type = "hidden";
      i.name = n;
      i.value = v;
      f.appendChild(i);
    };
    add("csrf_token", token);
    Object.entries(datos || {}).forEach(([k, v]) => add(k, v));
    document.body.appendChild(f);
    f.submit();
  }

  // Igual que switchAlertLink pero por POST. Usa data-url y data-params del botón.
  function switchAlertPost(evento, caso, datos) {
    evento.preventDefault();
    const el = evento.currentTarget;
    const url = el.dataset.url;
    const params = JSON.parse(el.dataset.params || "{}");
    switchAlert(caso, datos).then(function (res) {
      if (!res.isConfirmed) return;
      let motivo = "";
      if (typeof res.value === "string") motivo = res.value.trim();
      else if (res.value && typeof res.value === "object" && res.value.motivo)
        motivo = String(res.value.motivo).trim();
      if (motivo) params.motivo = motivo;
      enviarPost(url, params);
    });
    return false;
  }

  // ── Exportar al ámbito global ────────────────────────────────────
  global.enviarPost = enviarPost;
  global.switchAlertPost = switchAlertPost;
  global.switchAlert = switchAlert;
  global.switchAlertLink = switchAlertLink;
  global.marcarProgresoRechazado = marcarProgresoRechazado;
  global.reiniciarProgreso = reiniciarProgreso;
})(window);
