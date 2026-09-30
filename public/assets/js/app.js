// Santa Fe Propiedades — interacciones mínimas. La web funciona sin JavaScript;
// esto suma menú móvil, galería, filtros automáticos y eventos de Analytics.
(function () {
  'use strict';
  document.documentElement.classList.add('js');

  // Menú móvil
  var botonMenu = document.querySelector('.cabecera__menu');
  var navegacion = document.getElementById('navegacion');
  if (botonMenu && navegacion) {
    botonMenu.addEventListener('click', function () {
      var abierto = navegacion.classList.toggle('abierta');
      botonMenu.setAttribute('aria-expanded', abierto ? 'true' : 'false');
    });
    navegacion.addEventListener('click', function (evento) {
      if (evento.target.tagName === 'A') {
        navegacion.classList.remove('abierta');
        botonMenu.setAttribute('aria-expanded', 'false');
      }
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

  // Interruptor Alquiler / Venta de las destacadas (pestañas accesibles).
  document.querySelectorAll('[data-pestanias]').forEach(function (grupo) {
    var botones = grupo.querySelectorAll('[role="tab"]');
    botones.forEach(function (boton) {
      boton.addEventListener('click', function () {
        botones.forEach(function (otro) {
          var activo = otro === boton;
          otro.setAttribute('aria-selected', activo ? 'true' : 'false');
          document.getElementById(otro.getAttribute('aria-controls')).hidden = !activo;
        });
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
