// Pantalla de carga y transición entre secciones (versión sin React de "Santa Fe Loader":
// IntroLoader.jsx y PageTransition.jsx). Los tiempos son los mismos y tienen que coincidir con
// cargadores.css (--sf-pt-in / --sf-pt-out).
(function () {
  'use strict';
  var html = document.documentElement;
  var intro = document.querySelector('.sf-intro');
  var telon = document.querySelector('.sf-pt');
  var reducir = window.matchMedia('(prefers-reduced-motion: reduce)').matches;

  // ---------- Pantalla de carga inicial (solo la primera visita de la sesión) ----------
  var BUILD_MS = 8 * 90 + 550; // 8 cuadrados escalonados + duración de la caída
  var LEAVE_MS = 950;          // salida de cuadrados + telón hacia arriba
  var MIN_MS = 1800;           // duración mínima, como minDuration en IntroLoader
  var MAX_MS = 6000;           // si el evento load tarda demasiado, se va igual

  if (intro && html.classList.contains('sf-con-intro')) {
    var fase = function (nombre) { intro.className = 'sf-intro is-' + nombre; };
    var minimoCumplido = false;
    var cargada = document.readyState === 'complete';
    var saliendo = false;
    document.body.style.overflow = 'hidden'; // sin scroll mientras está la pantalla de carga

    var salir = function () {
      if (saliendo || !minimoCumplido || !cargada) { return; }
      saliendo = true;
      fase('leave');
      setTimeout(function () {
        intro.hidden = true;
        html.classList.remove('sf-con-intro');
        document.body.style.overflow = '';
      }, LEAVE_MS);
    };
    setTimeout(function () { if (intro.classList.contains('is-build')) { fase('loop'); } }, reducir ? 0 : BUILD_MS);
    setTimeout(function () { minimoCumplido = true; salir(); }, reducir ? 300 : MIN_MS);
    window.addEventListener('load', function () { cargada = true; salir(); });
    setTimeout(function () { minimoCumplido = true; cargada = true; salir(); }, MAX_MS);
  }

  // ---------- Transición entre secciones ----------
  var IN_MS = 650 + 2 * 90;  // 3 paneles escalonados que suben y tapan
  var HOLD_MS = 550;         // logo visible mientras se monta la nueva página
  var OUT_MS = 700 + 2 * 90; // paneles que se van hacia arriba
  if (!telon) { return; }
  var faseTelon = function (nombre) { telon.className = 'sf-pt is-' + nombre; };
  var ocupado = false;

  // Llegada: la página se pintó tapada. El logo queda en pantalla hasta completar HOLD_MS
  // contando lo que tardó en cargar, y después los paneles se van hacia arriba.
  if (html.classList.contains('sf-llegando')) {
    faseTelon('hold');
    html.classList.remove('sf-llegando');
    setTimeout(function () {
      faseTelon('out');
      setTimeout(function () { faseTelon('idle'); }, OUT_MS);
    }, Math.max(0, HOLD_MS - performance.now()));
  }

  // Salida: los paneles suben y tapan, y recién ahí se va a la otra página.
  var irA = function (destino) {
    if (ocupado) { return; }
    if (reducir) { window.location.href = destino; return; }
    ocupado = true;
    faseTelon('in');
    setTimeout(function () {
      try { sessionStorage.setItem('sf-pt', '1'); } catch (e) { /* sin almacenamiento: llega sin telón */ }
      window.location.href = destino;
    }, IN_MS);
  };
  window.sfIrConTransicion = irA; // para navegar desde código (por ejemplo, las etiquetas del mapa)

  // Enlaces internos de la web pública. Quedan afuera: Ctrl/Cmd/Shift/Alt+clic, target="_blank",
  // descargas, otros sitios, el panel, los anclas dentro de la misma página y data-sin-transicion.
  document.addEventListener('click', function (evento) {
    if (evento.defaultPrevented || evento.button !== 0 || evento.metaKey || evento.ctrlKey || evento.shiftKey || evento.altKey) { return; }
    var enlace = evento.target.closest('a[href]');
    if (!enlace || enlace.hasAttribute('download') || enlace.hasAttribute('data-sin-transicion')) { return; }
    if (enlace.target && enlace.target !== '_self') { return; }
    var url = new URL(enlace.href, window.location.href);
    if (url.origin !== window.location.origin || url.pathname.indexOf('/panel') === 0) { return; }
    if (url.pathname === window.location.pathname && url.search === window.location.search) { return; }
    evento.preventDefault();
    irA(url.href);
  });

  // Volver con el botón Atrás puede restaurar esta página tal como quedó (tapada): se destapa.
  window.addEventListener('pageshow', function (evento) {
    if (evento.persisted) {
      ocupado = false;
      faseTelon('idle');
    }
  });
})();
