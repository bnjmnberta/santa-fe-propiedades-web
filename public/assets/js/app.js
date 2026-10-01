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
  // y solo la pestaña activa queda en el orden del Tab).
  document.querySelectorAll('[data-pestanias]').forEach(function (grupo) {
    var botones = Array.prototype.slice.call(grupo.querySelectorAll('[role="tab"]'));
    var activar = function (boton, enfocar) {
      botones.forEach(function (otro) {
        var activo = otro === boton;
        otro.setAttribute('aria-selected', activo ? 'true' : 'false');
        otro.tabIndex = activo ? 0 : -1;
        document.getElementById(otro.getAttribute('aria-controls')).hidden = !activo;
      });
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
