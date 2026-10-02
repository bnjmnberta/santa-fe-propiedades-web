// Santa Fe Propiedades — interacciones mínimas. La web funciona sin JavaScript;
// esto suma menú móvil, galería, filtros automáticos, botón flotante y eventos de Analytics.
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  // Menú móvil: se cierra con Escape, tocando afuera o eligiendo un enlace.
  var botonMenu = document.querySelector('.cabecera__menu');
  var navegacion = document.getElementById('navegacion');
  if (botonMenu && navegacion) {
    var cerrarMenu = function (devolverFoco) {
      if (!navegacion.classList.contains('abierta')) { return; }
      navegacion.classList.remove('abierta');
      botonMenu.setAttribute('aria-expanded', 'false');
      if (devolverFoco) { botonMenu.focus(); }
    };
    botonMenu.addEventListener('click', function () {
      var abierto = navegacion.classList.toggle('abierta');
      botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
      if (abierto) { navegacion.querySelector('a').focus(); }
    });
    navegacion.addEventListener('click', function (evento) {
      if (evento.target.tagName === 'A') { cerrarMenu(false); }
    });
    document.addEventListener('keydown', function (evento) {
      if (evento.key === 'Escape') { cerrarMenu(true); }
    });
    document.addEventListener('click', function (evento) {
      if (!navegacion.contains(evento.target) && !botonMenu.contains(evento.target)) { cerrarMenu(false); }
    });
  }

  // Botón flotante de WhatsApp: se esconde mientras se ve otro botón de WhatsApp, una franja
  // de captación o el pie, para no tapar texto que llega al borde derecho.
  var flotante = document.querySelector('.whatsapp-flotante');
  if (flotante && 'IntersectionObserver' in window) {
    var aLaVista = new Set();
    var observador = new IntersectionObserver(function (entradas) {
      entradas.forEach(function (entrada) {
        if (entrada.isIntersecting) { aLaVista.add(entrada.target); } else { aLaVista.delete(entrada.target); }
      });
      flotante.classList.toggle('whatsapp-flotante--oculto', aLaVista.size > 0);
    });
    document.querySelectorAll('.boton--whatsapp:not(.cabecera__cta), .franja, .pie').forEach(function (nodo) {
      observador.observe(nodo);
    });
  }

  // Filtros del catálogo: se aplican al cambiar la selección.
  document.querySelectorAll('[data-envio-automatico] select').forEach(function (select) {
    select.addEventListener('change', function () { select.form.submit(); });
  });

  // Pestañas del catálogo: en celular se deslizan; la sección actual queda a la vista.
  // Se mide con la tipografía ya cargada, porque cambia el ancho de las pestañas.
  var mostrarPestaniaActual = function () {
    document.querySelectorAll('.pestanias').forEach(function (fila) {
      var actual = fila.querySelector('[aria-current="page"]');
      if (!actual) { return; }
      var sobra = actual.getBoundingClientRect().right - fila.getBoundingClientRect().right;
      if (sobra > 0) { fila.scrollLeft += sobra + 36; }
    });
  };
  if (document.fonts) { document.fonts.ready.then(mostrarPestaniaActual); } else { mostrarPestaniaActual(); }

  // Galería de la ficha: flechas y contador sobre un carrusel con scroll-snap.
  document.querySelectorAll('[data-galeria]').forEach(function (galeria) {
    var pista = galeria.querySelector('.galeria__pista');
    var contador = galeria.querySelector('[data-galeria-contador]');
    if (!pista) { return; }
    var total = pista.children.length;
    // Una foto por paso, aunque se vean dos a la vez (placas verticales en escritorio).
    var anchoPaso = function () {
      return pista.children[0].getBoundingClientRect().width + (parseFloat(getComputedStyle(pista).columnGap) || 0);
    };
    var actual = function () { return Math.round(pista.scrollLeft / anchoPaso()); };
    var ir = function (paso) {
      var destino = (actual() + paso + total) % total;
      pista.scrollTo({ left: destino * anchoPaso(), behavior: 'smooth' });
    };
    var anterior = galeria.querySelector('[data-galeria-anterior]');
    var siguiente = galeria.querySelector('[data-galeria-siguiente]');
    if (anterior) { anterior.addEventListener('click', function () { ir(-1); }); }
    if (siguiente) { siguiente.addEventListener('click', function () { ir(1); }); }
    pista.addEventListener('keydown', function (evento) {
      if (evento.key === 'ArrowLeft') { ir(-1); }
      if (evento.key === 'ArrowRight') { ir(1); }
    });
    if (contador) {
      pista.addEventListener('scroll', function () {
        contador.textContent = (actual() + 1) + ' / ' + total;
      }, { passive: true });
    }
  });

  // Interruptor Alquiler / Venta de las destacadas (patrón de pestañas: ← → Inicio Fin,
  // y solo la pestaña activa queda en el orden del Tab). Una pastilla se desliza hasta la opción
  // elegida cambiando de color, y las tarjetas entran desde ese lado.
  document.querySelectorAll('[data-pestanias]').forEach(function (grupo) {
    var botones = Array.prototype.slice.call(grupo.querySelectorAll('[role="tab"]'));
    var pastilla = document.createElement('span');
    pastilla.className = 'interruptor__pastilla';
    pastilla.setAttribute('aria-hidden', 'true');
    grupo.insertBefore(pastilla, grupo.firstChild);

    var elegido = function () { return botones.filter(function (b) { return b.getAttribute('aria-selected') === 'true'; })[0] || botones[0]; };
    var ubicarPastilla = function (boton) {
      grupo.style.setProperty('--x-pastilla', boton.offsetLeft + 'px');
      grupo.style.setProperty('--ancho-pastilla', boton.offsetWidth + 'px');
      grupo.dataset.operacion = boton.classList.contains('interruptor__opcion--venta') ? 'venta' : 'alquiler';
    };
    // Primera ubicación y cambios de ancho (fuente cargada, rotar el celular): sin animación.
    var reubicar = function () {
      grupo.classList.add('interruptor--quieto');
      ubicarPastilla(elegido());
      void pastilla.offsetWidth;
      grupo.classList.remove('interruptor--quieto');
    };
    reubicar();
    grupo.classList.add('interruptor--deslizante');
    if (document.fonts) { document.fonts.ready.then(reubicar); }
    window.addEventListener('resize', reubicar);

    var activar = function (boton, enfocar) {
      var anterior = botones.indexOf(elegido());
      var nuevo = botones.indexOf(boton);
      botones.forEach(function (otro) {
        var activo = otro === boton;
        otro.setAttribute('aria-selected', activo ? 'true' : 'false');
        otro.tabIndex = activo ? 0 : -1;
        document.getElementById(otro.getAttribute('aria-controls')).hidden = !activo;
      });
      ubicarPastilla(boton);
      if (nuevo !== anterior) {
        var panel = document.getElementById(boton.getAttribute('aria-controls'));
        panel.removeAttribute('data-entrada');
        void panel.offsetWidth; // reinicia la animación si se cambia rápido de una a otra
        panel.dataset.entrada = nuevo > anterior ? 'derecha' : 'izquierda';
      }
      if (enfocar) { boton.focus(); }
    };
    botones.forEach(function (boton, indice) {
      boton.tabIndex = boton.getAttribute('aria-selected') === 'true' ? 0 : -1;
      boton.addEventListener('click', function () { activar(boton, false); });
      boton.addEventListener('keydown', function (evento) {
        var destino = { ArrowRight: indice + 1, ArrowLeft: indice - 1, Home: 0, End: botones.length - 1 }[evento.key];
        if (destino === undefined) { return; }
        evento.preventDefault();
        activar(botones[(destino + botones.length) % botones.length], true);
      });
    });
  });

  // Formulario de Contacto: arma el mensaje con nombre, motivo, código y texto, lo muestra
  // como va a llegar y abre WhatsApp. Sin JavaScript, el formulario manda solo el texto.
  document.querySelectorAll('[data-formulario-whatsapp]').forEach(function (formulario) {
    var campo = function (nombre) { return formulario.querySelector('[data-consulta="' + nombre + '"]'); };
    var vista = formulario.querySelector('[data-consulta-vista]');
    var textoVista = formulario.querySelector('[data-consulta-texto]');
    var armar = function () {
      var nombre = campo('nombre').value.trim();
      var motivo = formulario.querySelector('[data-consulta="motivo"]:checked');
      var codigo = campo('codigo').value.replace(/\D+/g, '');
      var mensaje = campo('mensaje').value.trim();
      var partes = ['Hola' + (nombre ? ', soy ' + nombre : '') + '.'];
      if (motivo) { partes.push('Quiero ' + motivo.dataset.frase + '.'); }
      if (codigo) { partes.push('Me interesa la propiedad #' + codigo + '.'); }
      var texto = partes.join(' ');
      if (mensaje) { texto += '\n' + mensaje; }
      return partes.length === 1 && !mensaje && !nombre ? '' : texto;
    };
    var actualizar = function () {
      var texto = armar();
      vista.hidden = texto === '';
      textoVista.textContent = texto;
    };
    formulario.addEventListener('input', actualizar);
    formulario.addEventListener('change', actualizar);
    formulario.addEventListener('submit', function (evento) {
      evento.preventDefault();
      var texto = armar() || 'Hola, les escribo desde la web de Santa Fe Propiedades.';
      window.open('https://wa.me/' + formulario.dataset.formularioWhatsapp + '?text=' + encodeURIComponent(texto), '_blank', 'noopener');
    });
  });

  // Servicios: scroll horizontal guiado por el scroll vertical. La escena queda fija (position: sticky)
  // y, a medida que se baja, la pista de servicios se desplaza de costado. No se intercepta la rueda:
  // es el scroll normal de la página, así que se puede salir hacia arriba o hacia abajo en cualquier momento.
  document.querySelectorAll('[data-servicios-h]').forEach(function (seccion) {
    if (window.matchMedia('(prefers-reduced-motion: reduce)').matches) { return; }
    var escena = seccion.querySelector('.servicios-h__escena');
    var paneles = Array.prototype.slice.call(seccion.querySelectorAll('[data-servicio-h]'));
    var pasos = Array.prototype.slice.call(seccion.querySelectorAll('[data-paso]'));
    var contador = seccion.querySelector('[data-servicios-contador]');
    var n = paneles.length;
    if (n < 2) { return; }
    var cabecera = document.querySelector('.cabecera');
    var alto = 65;
    var posicion = 0;
    var pendiente = false;
    seccion.classList.add('servicios-h--fijo');

    var recorrido = function () { return seccion.offsetHeight - escena.offsetHeight; };
    var medir = function () {
      alto = cabecera ? cabecera.offsetHeight : 65;
      seccion.style.setProperty('--alto-cab', alto + 'px');
    };
    var pintar = function () {
      pendiente = false;
      var largo = recorrido();
      var avance = largo > 0 ? Math.min(1, Math.max(0, (alto - seccion.getBoundingClientRect().top) / largo)) : 0;
      posicion = avance * (n - 1);
      seccion.style.setProperty('--pos', posicion.toFixed(4));
      paneles.forEach(function (panel, i) {
        var d = i - posicion;
        panel.style.setProperty('--d', d.toFixed(3));
        panel.style.setProperty('--ad', Math.min(1, Math.abs(d)).toFixed(3));
      });
      var activo = Math.round(posicion);
      pasos.forEach(function (paso, i) {
        paso.style.setProperty('--f', Math.min(1, Math.max(0, posicion - i + 1)).toFixed(3));
        if (i === activo) { paso.setAttribute('aria-current', 'step'); } else { paso.removeAttribute('aria-current'); }
      });
      if (contador) {
        contador.textContent = (activo + 1) + ' de ' + n + ' · ' + paneles[activo].querySelector('.servicio-h__titulo').textContent;
      }
    };
    var programar = function () {
      if (!pendiente) { pendiente = true; window.requestAnimationFrame(pintar); }
    };
    var irA = function (i) {
      var destino = window.pageYOffset + seccion.getBoundingClientRect().top - alto + (i / (n - 1)) * recorrido();
      window.scrollTo({ top: destino, behavior: 'smooth' });
    };

    pasos.forEach(function (paso, i) { paso.addEventListener('click', function () { irA(i); }); });
    // Con Tab, el foco puede llegar a un botón que está fuera de pantalla: se lleva la página hasta ese servicio.
    seccion.addEventListener('focusin', function (evento) {
      var panel = evento.target.closest('[data-servicio-h]');
      var i = panel ? paneles.indexOf(panel) : -1;
      if (i >= 0 && Math.abs(i - posicion) > 0.5) { irA(i); }
    });
    window.addEventListener('scroll', programar, { passive: true });
    window.addEventListener('resize', function () { medir(); programar(); });
    medir();
    pintar();
    if (document.fonts) { document.fonts.ready.then(function () { medir(); pintar(); }); }
  });

  // Carrusel de destacadas: las flechas avanzan una tarjeta.
  document.querySelectorAll('[data-carrusel]').forEach(function (carrusel) {
    var pista = carrusel.querySelector('.carrusel__pista');
    var mover = function (sentido) {
      var tarjeta = pista.querySelector('.tarjeta');
      if (tarjeta) { pista.scrollBy({ left: sentido * (tarjeta.getBoundingClientRect().width + 16), behavior: 'smooth' }); }
    };
    carrusel.querySelector('[data-carrusel-anterior]').addEventListener('click', function () { mover(-1); });
    carrusel.querySelector('[data-carrusel-siguiente]').addEventListener('click', function () { mover(1); });
  });

  // Regla j: cada consulta por WhatsApp (y cada clic en teléfono) se registra en Analytics.
  document.addEventListener('click', function (evento) {
    var enlace = evento.target.closest('[data-evento]');
    if (!enlace || typeof window.gtag !== 'function') { return; }
    window.gtag('event', enlace.dataset.evento, {
      origen: enlace.dataset.origen || '',
      codigo_propiedad: enlace.dataset.codigo || '',
      operacion: enlace.dataset.operacion || '',
      tipo_propiedad: enlace.dataset.tipo || ''
    });
  });
})();
