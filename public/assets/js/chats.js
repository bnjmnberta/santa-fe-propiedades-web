// Panel > Chats: la pantalla "como WhatsApp". Muestra los chats del asistente, se actualiza sola y deja escribirle al cliente.
(function () {
  'use strict';

  var raiz = document.querySelector('[data-chats]');
  if (!raiz) { return; }

  var base = raiz.dataset.base;
  var csrf = raiz.dataset.csrf;
  var $ = function (selector) { return raiz.querySelector(selector); };
  var elLista = $('[data-lista]');
  var elVacio = $('[data-vacio]');
  var elBuscar = $('[data-buscar]');
  var elConexion = $('[data-conexion]');
  var elSinChat = $('[data-sin-chat]');
  var elChat = $('[data-chat]');
  var elNombre = $('[data-chat-nombre]');
  var elNumero = $('[data-chat-numero]');
  var elAvatar = $('[data-chat-avatar]');
  var elModo = $('[data-modo]');
  var elBanner = $('[data-banner]');
  var elMensajes = $('[data-mensajes]');
  var elForm = $('[data-formulario]');
  var elTexto = $('[data-texto]');
  var elEnviar = $('[data-enviar]');
  var elCuenta = document.querySelector('[data-chats-cuenta]');
  var elLimpiar = $('[data-limpiar]');
  var elResumen = $('[data-resumen]');
  var coincidencias = {};   // contacto -> { n, fragmento }: chats donde lo buscado aparece en los mensajes
  var terminos = [];        // palabras buscadas (sin tildes ni mayúsculas)
  var pedidoBusqueda = 0;   // para ignorar respuestas viejas
  var temporizador = null;

  var chats = [];
  var abierto = null;       // número del chat abierto
  var ultimoId = 0;         // último mensaje que ya se dibujó
  var modo = 'bot';
  var ventanaAbierta = true;
  var esperaMinutos = null; // hace cuánto espera el cliente una respuesta de una persona
  var ultimoDia = '';
  var enviando = false;
  var temaActivo = 'todos'; // solapa elegida: todos, venta, alquiler, locales u otros

  // ------------------------------------------------------------ pedidos

  function pedir(metodo, ruta, datos) {
    var opciones = { method: metodo, credentials: 'same-origin', headers: { 'Accept': 'application/json' } };
    if (datos) {
      var cuerpo = new FormData();
      cuerpo.append('_csrf', csrf);
      Object.keys(datos).forEach(function (clave) { cuerpo.append(clave, datos[clave]); });
      opciones.body = cuerpo;
    }
    return fetch(base + ruta, opciones).then(function (respuesta) {
      return respuesta.json().catch(function () { return {}; }).then(function (json) {
        if (respuesta.status === 401) { window.location.href = '/panel/ingresar?volver=' + encodeURIComponent('/panel/chats'); }
        return { ok: respuesta.ok, status: respuesta.status, datos: json };
      });
    }).then(function (resultado) {
      marcarConexion(resultado.status < 500 && resultado.status !== 0);
      return resultado;
    }, function () {
      marcarConexion(false);
      return { ok: false, status: 0, datos: { error: 'No hay conexión con el panel.' } };
    });
  }

  function marcarConexion(bien) {
    elConexion.classList.toggle('wsp__conexion--mal', !bien);
    elConexion.title = bien ? 'Conectado con el asistente' : 'Sin conexión con el asistente: reintentando…';
  }

  // ------------------------------------------------------------ formato

  function aFecha(texto) {
    var p = texto.split(/[- :]/).map(Number);
    return new Date(p[0], p[1] - 1, p[2], p[3] || 0, p[4] || 0, p[5] || 0);
  }
  function dos(n) { return (n < 10 ? '0' : '') + n; }
  function hora(texto) { var d = aFecha(texto); return dos(d.getHours()) + ':' + dos(d.getMinutes()); }
  function diaTexto(texto) {
    var d = aFecha(texto);
    var hoy = new Date();
    var ayer = new Date(hoy.getFullYear(), hoy.getMonth(), hoy.getDate() - 1);
    if (d.toDateString() === hoy.toDateString()) { return 'Hoy'; }
    if (d.toDateString() === ayer.toDateString()) { return 'Ayer'; }
    return dos(d.getDate()) + '/' + dos(d.getMonth() + 1) + '/' + d.getFullYear();
  }
  function horaLista(texto) {
    var d = aFecha(texto);
    var dia = diaTexto(texto);
    return dia === 'Hoy' ? hora(texto) : (dia === 'Ayer' ? 'Ayer' : dos(d.getDate()) + '/' + dos(d.getMonth() + 1));
  }
  function numeroLindo(contacto) {
    var m = /^549(\d{3,4})(\d{6,7})$/.exec(contacto);
    return m ? '+54 9 ' + m[1] + ' ' + m[2] : '+' + contacto;
  }
  function duracion(minutos) {
    if (minutos < 1) { return 'menos de 1 min'; }
    if (minutos < 60) { return minutos + ' min'; }
    var horas = Math.floor(minutos / 60);
    return horas < 24 ? horas + ' h' : Math.floor(horas / 24) + ' d';
  }
  function titulo(chat) { return chat.nombre ? chat.nombre : numeroLindo(chat.contacto); }
  function inicial(chat) {
    var t = chat.nombre ? chat.nombre.trim() : '';
    return t ? t.charAt(0).toUpperCase() : '#';
  }
  function colorAvatar(contacto) {
    var colores = ['#6b7cff', '#c2579a', '#0f9d8a', '#d9822b', '#7a5cc7', '#3f8fd2', '#b5524a', '#5d8f3b'];
    var suma = 0;
    for (var i = 0; i < contacto.length; i++) { suma += contacto.charCodeAt(i); }
    return colores[suma % colores.length];
  }
  function icono(tipo) {
    return { audio: '🎤 ', imagen: '📷 ', foto: '🖼️ ', ubicacion: '📍 ', otro: '📎 ' }[tipo] || '';
  }
  function tildes(estado) {
    if (estado === 'leido') { return { t: '✓✓', c: 'wsp__tilde wsp__tilde--leido', a: 'Leído' }; }
    if (estado === 'entregado') { return { t: '✓✓', c: 'wsp__tilde', a: 'Entregado' }; }
    if (estado === 'fallido') { return { t: '⚠', c: 'wsp__tilde wsp__tilde--fallo', a: 'No se pudo entregar' }; }
    return { t: '✓', c: 'wsp__tilde', a: 'Enviado' };
  }

  // ------------------------------------------------------------ búsqueda

  var MAPA = { 'á': 'a', 'à': 'a', 'ä': 'a', 'â': 'a', 'é': 'e', 'è': 'e', 'ë': 'e', 'ê': 'e', 'í': 'i', 'ì': 'i', 'ï': 'i', 'î': 'i', 'ó': 'o', 'ò': 'o', 'ö': 'o', 'ô': 'o', 'ú': 'u', 'ù': 'u', 'ü': 'u', 'û': 'u', 'ñ': 'n' };
  // Minúsculas y sin tildes, letra por letra: el largo no cambia y sirve para marcar la coincidencia en el texto original.
  function plegar(texto) {
    return texto.toLowerCase().replace(/[àáäâèéëêìíïîòóöôùúüûñ]/g, function (c) { return MAPA[c] || c; });
  }

  function coincide(texto) {
    if (!terminos.length) { return true; }
    var plano = plegar(texto);
    return terminos.every(function (t) { return plano.indexOf(t) !== -1; });
  }

  // Devuelve el texto con lo buscado marcado (con nodos, nunca con HTML: el texto es del cliente).
  function resaltar(texto) {
    var trozo = document.createDocumentFragment();
    if (!terminos.length || !texto) { trozo.appendChild(document.createTextNode(texto || '')); return trozo; }
    var plano = plegar(texto);
    var marcas = [];
    terminos.forEach(function (t) {
      var i = plano.indexOf(t);
      while (i !== -1) { marcas.push([i, i + t.length]); i = plano.indexOf(t, i + t.length); }
    });
    marcas.sort(function (a, b) { return a[0] - b[0]; });
    var fin = 0;
    marcas.forEach(function (m) {
      if (m[1] <= fin) { return; }
      var desde = Math.max(m[0], fin);
      if (desde > fin) { trozo.appendChild(document.createTextNode(texto.slice(fin, desde))); }
      var marca = document.createElement('mark');
      marca.textContent = texto.slice(desde, m[1]);
      trozo.appendChild(marca);
      fin = m[1];
    });
    if (fin < texto.length) { trozo.appendChild(document.createTextNode(texto.slice(fin))); }
    return trozo;
  }

  function buscando() { return terminos.length > 0; }

  function alCambiarBusqueda() {
    var q = elBuscar.value.trim();
    terminos = q ? plegar(q).split(/\s+/).filter(Boolean) : [];
    elLimpiar.hidden = !q;
    clearTimeout(temporizador);
    if (!buscando()) {
      elMensajes.querySelectorAll('mark').forEach(function (marca) { marca.replaceWith(document.createTextNode(marca.textContent)); });
      elMensajes.normalize();
    }
    if (!buscando() || terminos.join('').length < 2) {
      coincidencias = {};
      pedidoBusqueda++;
      dibujarLista();
      return;
    }
    dibujarLista(); // al instante: nombre y número; después llegan los mensajes
    temporizador = setTimeout(buscarEnMensajes, 250);
  }

  function buscarEnMensajes() {
    var numero = ++pedidoBusqueda;
    var q = elBuscar.value.trim();
    pedir('GET', '/buscar?q=' + encodeURIComponent(q)).then(function (r) {
      if (numero !== pedidoBusqueda || !r.ok || !r.datos.resultados) { return; }
      coincidencias = {};
      r.datos.resultados.forEach(function (x) { coincidencias[x.contacto] = { n: x.coincidencias, fragmento: x.fragmento }; });
      dibujarLista();
    });
  }

  function limpiarBusqueda() {
    elBuscar.value = '';
    alCambiarBusqueda();
    elBuscar.focus();
  }

  // ------------------------------------------------------------ lista

  function dibujarLista() {
    var filtro = elBuscar.value.trim().toLowerCase();
    // Cada solapa muestra cuántos mensajes sin leer tiene (el asistente clasifica los chats solo, según lo que escribe el cliente).
    var sinLeer = { todos: 0, venta: 0, alquiler: 0, locales: 0, otros: 0 };
    chats.forEach(function (c) {
      var n = c.sin_leer || 0;
      sinLeer.todos += n;
      sinLeer[c.tema in sinLeer ? c.tema : 'otros'] += n;
    });
    raiz.querySelectorAll('[data-cuenta-tema]').forEach(function (el) {
      var n = sinLeer[el.dataset.cuentaTema] || 0;
      el.textContent = n > 0 ? String(n) : '';
      el.hidden = n === 0;
    });
    // Con una búsqueda en marcha se mira en todos los chats (las solapas se ignoran) y se encuentra por nombre, número o contenido.
    var visibles = chats.filter(function (c) {
      if (buscando()) {
        return coincide(titulo(c) + ' ' + c.contacto + ' ' + numeroLindo(c.contacto)) || !!coincidencias[c.contacto];
      }
      return temaActivo === 'todos' || (c.tema in sinLeer ? c.tema : 'otros') === temaActivo;
    });
    elLista.textContent = '';
    elVacio.hidden = visibles.length > 0;
    elVacio.textContent = chats.length === 0 ? 'Todavía no hay conversaciones.'
      : (buscando() ? 'No encontré chats con “' + elBuscar.value.trim() + '”. Probá con otra palabra, parte del nombre o los últimos números del teléfono.' : 'No hay chats en esta solapa.');
    elResumen.hidden = !buscando();
    elResumen.textContent = buscando() ? (visibles.length === 1 ? '1 chat encontrado' : visibles.length + ' chats encontrados') + ' · buscando en todas las solapas' : '';
    raiz.classList.toggle('wsp--buscando', buscando());
    visibles.forEach(function (chat) {
      var li = document.createElement('li');
      var boton = document.createElement('button');
      boton.type = 'button';
      boton.className = 'wsp__chat-item' + (chat.contacto === abierto ? ' wsp__chat-item--activo' : '');
      boton.dataset.contacto = chat.contacto;

      var avatar = document.createElement('span');
      avatar.className = 'wsp__avatar';
      avatar.style.background = colorAvatar(chat.contacto);
      avatar.textContent = inicial(chat);

      var cuerpo = document.createElement('span');
      cuerpo.className = 'wsp__chat-cuerpo';
      var arriba = document.createElement('span');
      arriba.className = 'wsp__chat-arriba';
      var nombre = document.createElement('strong');
      nombre.appendChild(resaltar(titulo(chat)));
      var cuando = document.createElement('time');
      cuando.className = 'wsp__chat-hora' + (chat.sin_leer > 0 ? ' wsp__chat-hora--nuevo' : '');
      cuando.textContent = chat.ultimo ? horaLista(chat.ultimo.hora) : '';
      arriba.appendChild(nombre);
      arriba.appendChild(cuando);

      var abajo = document.createElement('span');
      abajo.className = 'wsp__chat-abajo';
      var vista = document.createElement('span');
      vista.className = 'wsp__chat-vista';
      if (chat.ultimo) {
        if (chat.ultimo.sentido === 's') {
          var t = tildes(chat.ultimo.estado || 'enviado');
          var marca = document.createElement('span');
          marca.className = t.c;
          marca.textContent = t.t + ' ';
          vista.appendChild(marca);
        }
        vista.appendChild(document.createTextNode(icono(chat.ultimo.tipo) + (chat.ultimo.texto || '')));
      }
      if (chat.espera_minutos !== null && chat.espera_minutos !== undefined) {
        // Un cliente que escribió y todavía no tiene respuesta de una persona: se destaca en rojo.
        vista.className += ' wsp__chat-vista--espera';
        vista.textContent = '';
        vista.appendChild(document.createTextNode('⏳ Espera respuesta hace ' + duracion(chat.espera_minutos)));
      }
      var hallazgo = buscando() ? coincidencias[chat.contacto] : null;
      if (hallazgo) {
        // Como en WhatsApp: se muestra el mensaje donde aparece lo buscado, con la palabra marcada.
        vista.className = 'wsp__chat-vista wsp__chat-vista--hallazgo';
        vista.textContent = '';
        vista.appendChild(resaltar(hallazgo.fragmento));
        if (hallazgo.n > 1) {
          var cuantos = document.createElement('span');
          cuantos.className = 'wsp__etiqueta wsp__etiqueta--tema';
          cuantos.textContent = hallazgo.n + ' mensajes';
          abajo.appendChild(vista);
          abajo.appendChild(cuantos);
        } else { abajo.appendChild(vista); }
      } else { abajo.appendChild(vista); }
      if (!buscando() && temaActivo === 'todos' && chat.tema && chat.tema !== 'otros' && !(chat.modo === 'persona' && chat.motivo === 'venta')) {
        var tema = document.createElement('span');
        tema.className = 'wsp__etiqueta wsp__etiqueta--tema';
        tema.textContent = { venta: 'Venta', alquiler: 'Alquiler', locales: 'Locales' }[chat.tema] || '';
        abajo.appendChild(tema);
      }
      if (chat.modo === 'persona') {
        var etiqueta = document.createElement('span');
        etiqueta.className = 'wsp__etiqueta' + (chat.motivo === 'venta' ? ' wsp__etiqueta--venta' : '');
        etiqueta.textContent = chat.motivo === 'venta' ? 'Compra' : 'Vos';
        abajo.appendChild(etiqueta);
      }
      if (chat.sin_leer > 0) {
        var nuevo = document.createElement('span');
        nuevo.className = 'wsp__contador';
        nuevo.textContent = String(chat.sin_leer);
        abajo.appendChild(nuevo);
      }

      cuerpo.appendChild(arriba);
      cuerpo.appendChild(abajo);
      boton.appendChild(avatar);
      boton.appendChild(cuerpo);
      li.appendChild(boton);
      elLista.appendChild(li);
    });
  }

  function cargarLista() {
    return pedir('GET', '/lista').then(function (r) {
      if (!r.ok || !r.datos.chats) { return; }
      chats = r.datos.chats;
      dibujarLista();
      actualizarCuenta(r.datos.sin_leer_total || 0);
    });
  }

  function actualizarCuenta(total) {
    if (!elCuenta) { return; }
    elCuenta.hidden = total < 1;
    elCuenta.textContent = total > 99 ? '99+' : String(total);
  }

  // ------------------------------------------------------------ un chat

  function buscar(contacto) {
    for (var i = 0; i < chats.length; i++) { if (chats[i].contacto === contacto) { return chats[i]; } }
    return null;
  }

  function abrir(contacto) {
    abierto = contacto;
    ultimoId = 0;
    ultimoDia = '';
    elMensajes.textContent = '';
    elSinChat.hidden = true;
    elChat.hidden = false;
    raiz.classList.add('wsp--abierto');
    var chat = buscar(contacto) || { contacto: contacto, nombre: null };
    pintarCabecera(chat);
    dibujarLista();
    pedir('GET', '/' + contacto + '/mensajes').then(function (r) {
      if (!r.ok) { mostrarBanner(r.datos.error || 'No se pudieron cargar los mensajes.', true); return; }
      modo = r.datos.modo;
      ventanaAbierta = r.datos.ventana_abierta;
      esperaMinutos = r.datos.espera_minutos === undefined ? null : r.datos.espera_minutos;
      if (r.datos.nombre) { chat.nombre = r.datos.nombre; pintarCabecera(chat); }
      dibujarMensajes(r.datos.mensajes, true);
      actualizarEstadoDelChat();
      leer();
      // Si se llegó desde una búsqueda, se va directo al primer mensaje donde aparece lo buscado.
      var primero = buscando() ? elMensajes.querySelector('mark') : null;
      if (primero) { primero.scrollIntoView({ block: 'center' }); } else if (window.matchMedia('(min-width: 900px)').matches) { elTexto.focus(); }
    });
  }

  function pintarCabecera(chat) {
    elNombre.textContent = titulo(chat);
    elNumero.textContent = chat.nombre ? numeroLindo(chat.contacto) : '';
    elAvatar.textContent = inicial(chat);
    elAvatar.className = 'wsp__avatar';
    elAvatar.style.background = colorAvatar(chat.contacto);
  }

  function actualizarEstadoDelChat() {
    elModo.textContent = modo === 'persona' ? 'Devolver al bot' : 'Tomar el chat';
    elModo.className = 'boton boton--chico wsp__modo ' + (modo === 'persona' ? 'panel-boton-suave' : 'boton--primario');
    if (!ventanaAbierta) {
      mostrarBanner('Pasaron más de 24 horas desde el último mensaje del cliente. WhatsApp solo deja escribirle de nuevo cuando él escribe.', false);
    } else if (modo === 'persona' && esperaMinutos !== null) {
      mostrarBanner('El cliente espera tu respuesta hace ' + duracion(esperaMinutos) + '. Contestale desde acá; el asistente no le escribe en este chat.', true);
    } else if (modo === 'persona') {
      mostrarBanner('Lo atendés vos: el asistente no contesta en este chat. Cuando termines, tocá "Devolver al bot".', false, true);
    } else {
      mostrarBanner('Este chat lo atiende el asistente. Si escribís un mensaje, el asistente se pone en pausa y seguís vos.', false, true);
    }
    elTexto.disabled = !ventanaAbierta;
    elEnviar.disabled = !ventanaAbierta;
    elMic.disabled = !ventanaAbierta;
    elTexto.placeholder = ventanaAbierta ? 'Escribí un mensaje' : 'No se puede escribir: pasaron más de 24 h';
  }

  function mostrarBanner(texto, error, suave) {
    elBanner.hidden = !texto;
    elBanner.textContent = texto || '';
    elBanner.className = 'wsp__banner' + (error ? ' wsp__banner--error' : '') + (suave ? ' wsp__banner--suave' : '');
  }

  function dibujarMensajes(lista, alFondo) {
    var pegado = alFondo || (elMensajes.scrollHeight - elMensajes.scrollTop - elMensajes.clientHeight < 80);
    lista.forEach(function (m) {
      if (m.id <= ultimoId) { return; }
      ultimoId = m.id;
      var dia = diaTexto(m.hora);
      if (dia !== ultimoDia) {
        ultimoDia = dia;
        var separador = document.createElement('div');
        separador.className = 'wsp__dia';
        var chip = document.createElement('span');
        chip.textContent = dia;
        separador.appendChild(chip);
        elMensajes.appendChild(separador);
      }
      var propio = m.sentido === 's';
      var burbuja = document.createElement('div');
      burbuja.className = 'wsp__burbuja ' + (propio ? 'wsp__burbuja--salida' : 'wsp__burbuja--entrada') + (m.origen === 'persona' ? ' wsp__burbuja--persona' : '');
      burbuja.dataset.id = String(m.id);
      if (propio) {
        var quien = document.createElement('span');
        quien.className = 'wsp__quien';
        quien.textContent = m.origen === 'persona' ? 'Equipo' : 'Asistente';
        burbuja.appendChild(quien);
      }
      var cuerpo = document.createElement('span');
      cuerpo.className = 'wsp__texto';
      cuerpo.appendChild(resaltar(icono(m.tipo) + (m.texto || '')));
      burbuja.appendChild(cuerpo);
      var pie = document.createElement('span');
      pie.className = 'wsp__pie';
      pie.appendChild(document.createTextNode(hora(m.hora)));
      if (propio) {
        var t = tildes(m.estado);
        var marca = document.createElement('span');
        marca.className = t.c;
        marca.title = t.a;
        marca.textContent = ' ' + t.t;
        marca.dataset.estadoDe = String(m.id);
        pie.appendChild(marca);
      }
      burbuja.appendChild(pie);
      elMensajes.appendChild(burbuja);
    });
    if (pegado) { elMensajes.scrollTop = elMensajes.scrollHeight; }
  }

  // Los tildes cambian después (entregado, leído): se vuelven a dibujar los de los mensajes propios.
  function refrescarTildes(lista) {
    lista.forEach(function (m) {
      if (m.sentido !== 's') { return; }
      var marca = elMensajes.querySelector('[data-estado-de="' + m.id + '"]');
      if (!marca) { return; }
      var t = tildes(m.estado);
      marca.className = t.c;
      marca.title = t.a;
      marca.textContent = ' ' + t.t;
    });
  }

  function actualizarChat() {
    if (!abierto) { return; }
    var contacto = abierto;
    pedir('GET', '/' + contacto + '/mensajes?desde=' + ultimoId).then(function (r) {
      if (!r.ok || contacto !== abierto) { return; }
      var habiaNuevos = r.datos.mensajes.length > 0;
      var entro = r.datos.mensajes.some(function (m) { return m.sentido === 'e'; });
      modo = r.datos.modo;
      ventanaAbierta = r.datos.ventana_abierta;
      esperaMinutos = r.datos.espera_minutos === undefined ? null : r.datos.espera_minutos;
      dibujarMensajes(r.datos.mensajes, false);
      actualizarEstadoDelChat();
      if (habiaNuevos && entro && !document.hidden) { leer(); }
      // Cada tanto se pide el chat entero para actualizar los tildes de lo ya enviado.
      if (++vueltas % 4 === 0) {
        pedir('GET', '/' + contacto + '/mensajes').then(function (completo) {
          if (completo.ok && contacto === abierto) { refrescarTildes(completo.datos.mensajes); }
        });
      }
    });
  }
  var vueltas = 0;

  function leer() {
    if (!abierto) { return; }
    pedir('POST', '/' + abierto + '/leer', {}).then(cargarLista);
  }

  // ------------------------------------------------------------ acciones

  function enviar(evento) {
    evento.preventDefault();
    var texto = elTexto.value.trim();
    if (!texto || !abierto || enviando) { return; }
    enviando = true;
    elEnviar.disabled = true;
    pedir('POST', '/' + abierto + '/enviar', { texto: texto }).then(function (r) {
      enviando = false;
      elEnviar.disabled = !ventanaAbierta;
      if (!r.ok) {
        var motivo = r.datos.error === 'fuera_de_ventana' ? r.datos.mensaje : (r.datos.error || 'No se pudo enviar.');
        if (r.datos.error === 'fuera_de_ventana') { ventanaAbierta = false; actualizarEstadoDelChat(); }
        mostrarBanner(motivo, true);
        return;
      }
      elTexto.value = '';
      autoalto();
      modo = 'persona';
      actualizarEstadoDelChat();
      actualizarChat();
      cargarLista();
    });
  }

  // ------------------------------------------------------------ audios

  var elMic = $('[data-mic]');
  var elGrabando = $('[data-grabando]');
  var elTiempo = $('[data-tiempo]');
  var grabacion = null; // { grabador, trozos, flujo, inicio, reloj, cancelada, contacto }

  // WhatsApp acepta ogg/opus y mp4/aac, pero no webm. Se usa el primero que el navegador sepa grabar.
  function formatoDeAudio() {
    if (typeof MediaRecorder === 'undefined') { return null; }
    var candidatos = ['audio/ogg;codecs=opus', 'audio/mp4;codecs=mp4a.40.2', 'audio/mp4'];
    for (var i = 0; i < candidatos.length; i++) {
      if (MediaRecorder.isTypeSupported(candidatos[i])) { return candidatos[i]; }
    }
    return null;
  }

  function relojTexto(segundos) { return Math.floor(segundos / 60) + ':' + dos(segundos % 60); }

  function empezarGrabacion() {
    if (grabacion || !abierto || !ventanaAbierta) { return; }
    var formato = formatoDeAudio();
    if (!formato || !navigator.mediaDevices || !navigator.mediaDevices.getUserMedia) {
      mostrarBanner('Este navegador no puede grabar audios en un formato que WhatsApp acepte. Usá Brave, Chrome o Firefox actualizados.', true);
      return;
    }
    navigator.mediaDevices.getUserMedia({ audio: true }).then(function (flujo) {
      var grabador = new MediaRecorder(flujo, { mimeType: formato });
      var actual = { grabador: grabador, trozos: [], flujo: flujo, inicio: Date.now(), cancelada: false, contacto: abierto, formato: formato };
      grabador.addEventListener('dataavailable', function (e) { if (e.data && e.data.size) { actual.trozos.push(e.data); } });
      grabador.addEventListener('stop', function () { terminarGrabacion(actual); });
      grabador.start();
      actual.reloj = setInterval(function () {
        var seg = Math.floor((Date.now() - actual.inicio) / 1000);
        elTiempo.textContent = relojTexto(seg);
        if (seg >= 300) { pararGrabacion(false); } // tope de 5 minutos
      }, 250);
      grabacion = actual;
      elTiempo.textContent = '0:00';
      elForm.hidden = true;
      elGrabando.hidden = false;
    }, function () {
      mostrarBanner('No se pudo usar el micrófono. Permití el acceso al micrófono en el navegador y probá de nuevo.', true);
    });
  }

  function pararGrabacion(cancelar) {
    if (!grabacion) { return; }
    grabacion.cancelada = cancelar;
    if (grabacion.grabador.state !== 'inactive') { grabacion.grabador.stop(); }
  }

  function terminarGrabacion(actual) {
    clearInterval(actual.reloj);
    actual.flujo.getTracks().forEach(function (pista) { pista.stop(); });
    if (grabacion === actual) { grabacion = null; }
    elGrabando.hidden = true;
    elForm.hidden = false;
    var segundos = Math.max(1, Math.round((Date.now() - actual.inicio) / 1000));
    if (actual.cancelada || !actual.trozos.length) { return; }
    var tipo = actual.formato.split(';')[0];
    var archivo = new Blob(actual.trozos, { type: tipo });
    var cuerpo = new FormData();
    cuerpo.append('_csrf', csrf);
    cuerpo.append('duracion', String(segundos));
    cuerpo.append('audio', archivo, tipo === 'audio/ogg' ? 'audio.ogg' : 'audio.m4a');
    elMic.disabled = true;
    fetch(base + '/' + actual.contacto + '/audio', { method: 'POST', credentials: 'same-origin', headers: { 'Accept': 'application/json' }, body: cuerpo })
      .then(function (respuesta) {
        return respuesta.json().catch(function () { return {}; }).then(function (json) { return { ok: respuesta.ok, status: respuesta.status, datos: json }; });
      })
      .then(function (r) {
        elMic.disabled = false;
        if (!r.ok) {
          var motivo = r.datos.mensaje || r.datos.error || 'No se pudo enviar el audio.';
          if (r.datos.error === 'fuera_de_ventana') { ventanaAbierta = false; actualizarEstadoDelChat(); }
          mostrarBanner(motivo, true);
          return;
        }
        modo = 'persona';
        actualizarEstadoDelChat();
        actualizarChat();
        cargarLista();
      }, function () {
        elMic.disabled = false;
        mostrarBanner('No hay conexión con el panel. El audio no se envió.', true);
      });
  }

  function cambiarModo() {
    if (!abierto) { return; }
    var nuevo = modo === 'persona' ? 'bot' : 'persona';
    pedir('POST', '/' + abierto + '/modo', { modo: nuevo }).then(function (r) {
      if (!r.ok) { mostrarBanner(r.datos.error || 'No se pudo cambiar.', true); return; }
      modo = nuevo;
      actualizarEstadoDelChat();
      cargarLista();
    });
  }

  function autoalto() {
    elTexto.style.height = 'auto';
    elTexto.style.height = Math.min(elTexto.scrollHeight, 140) + 'px';
    // Como en WhatsApp: con el campo vacío se ve el micrófono; apenas hay texto, el botón de enviar.
    var hayTexto = elTexto.value.trim() !== '';
    elEnviar.hidden = !hayTexto;
    elMic.hidden = hayTexto;
  }

  // ------------------------------------------------------------ eventos

  elLista.addEventListener('click', function (evento) {
    var boton = evento.target.closest('[data-contacto]');
    if (boton) { abrir(boton.dataset.contacto); }
  });
  elBuscar.addEventListener('input', alCambiarBusqueda);
  elBuscar.addEventListener('keydown', function (evento) {
    if (evento.key === 'Escape' && elBuscar.value) { evento.preventDefault(); limpiarBusqueda(); }
  });
  elLimpiar.addEventListener('click', limpiarBusqueda);
  // Atajo: "/" lleva al buscador, como en muchas aplicaciones.
  document.addEventListener('keydown', function (evento) {
    var destino = evento.target;
    var escribiendo = destino && (destino.tagName === 'INPUT' || destino.tagName === 'TEXTAREA' || destino.isContentEditable);
    if (evento.key === '/' && !escribiendo && !evento.ctrlKey && !evento.metaKey) { evento.preventDefault(); raiz.classList.remove('wsp--abierto'); elBuscar.focus(); }
  });
  $('[data-temas]').addEventListener('click', function (evento) {
    var boton = evento.target.closest('[data-tema]');
    if (!boton) { return; }
    temaActivo = boton.dataset.tema;
    raiz.querySelectorAll('[data-tema]').forEach(function (b) {
      var activo = b === boton;
      b.classList.toggle('wsp__tema--activo', activo);
      b.setAttribute('aria-selected', activo ? 'true' : 'false');
    });
    dibujarLista();
  });
  // Apariencia: claro, oscuro o igual que el sistema (se recuerda en este navegador; el <head> la aplica antes de dibujar).
  var oscuroDelSistema = window.matchMedia ? window.matchMedia('(prefers-color-scheme: dark)') : null;
  function aplicarApariencia(eleccion) {
    var oscuro = eleccion === 'oscuro' || (eleccion === 'sistema' && oscuroDelSistema && oscuroDelSistema.matches);
    document.documentElement.dataset.tema = oscuro ? 'oscuro' : 'claro';
    document.documentElement.dataset.apariencia = eleccion;
    raiz.querySelectorAll('[data-apariencia]').forEach(function (b) {
      b.setAttribute('aria-pressed', b.dataset.apariencia === eleccion ? 'true' : 'false');
    });
  }
  function aparienciaGuardada() {
    try { return localStorage.getItem('sfp-chats-apariencia') || 'sistema'; } catch (e) { return 'sistema'; }
  }
  raiz.querySelectorAll('[data-apariencia]').forEach(function (boton) {
    boton.addEventListener('click', function () {
      try { localStorage.setItem('sfp-chats-apariencia', boton.dataset.apariencia); } catch (e) { /* sin almacenamiento: vale solo por esta visita */ }
      aplicarApariencia(boton.dataset.apariencia);
    });
  });
  // Si está en "sistema" y el sistema cambia (por ejemplo, de día a noche), la pantalla lo sigue.
  if (oscuroDelSistema && oscuroDelSistema.addEventListener) {
    oscuroDelSistema.addEventListener('change', function () { if (aparienciaGuardada() === 'sistema') { aplicarApariencia('sistema'); } });
  }
  aplicarApariencia(aparienciaGuardada());

  elForm.addEventListener('submit', enviar);
  elMic.addEventListener('click', empezarGrabacion);
  $('[data-enviar-audio]').addEventListener('click', function () { pararGrabacion(false); });
  $('[data-descartar]').addEventListener('click', function () { pararGrabacion(true); });
  elModo.addEventListener('click', cambiarModo);
  elTexto.addEventListener('input', autoalto);
  elTexto.addEventListener('keydown', function (evento) {
    if (evento.key === 'Enter' && !evento.shiftKey) { enviar(evento); }
  });
  $('[data-volver]').addEventListener('click', function () {
    raiz.classList.remove('wsp--abierto');
    abierto = null;
    elChat.hidden = true;
    elSinChat.hidden = false;
    dibujarLista();
  });

  autoalto();
  cargarLista().then(function () {
    var pedido = /[?&]abrir=(\d+)/.exec(window.location.search);
    if (pedido) { abrir(pedido[1]); }
  });
  setInterval(function () { if (!document.hidden) { cargarLista(); } }, 6000);
  setInterval(function () { if (!document.hidden) { actualizarChat(); } }, 3000);
})();
