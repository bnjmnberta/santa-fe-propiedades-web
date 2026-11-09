// Mapa de propiedades + puntos estratégicos de Santa Fe (facultades, terminal, puerto, costanera).
// MapLibre GL + OpenFreeMap: mapa vectorial, gratis también para uso comercial, sin clave de API.
// Las propiedades son etiquetas con el precio; las cercanas se agrupan en una burbuja con la cantidad.
(function () {
  'use strict';
  var nodo = document.getElementById('mapa');
  var fuente = document.getElementById('datos-mapa');
  if (!nodo || !fuente) { return; }

  // MapLibre (CSS + JS, unos 180 KB) se pide recién cuando el mapa está por entrar en pantalla.
  // Si está oculto (por ejemplo, detrás del botón "Ver mapa" en celular), espera a que se muestre.
  var LIBRERIA = 'https://cdnjs.cloudflare.com/ajax/libs/maplibre-gl/4.7.1/';
  var traerLibreria = function (listo) {
    if (window.maplibregl) { listo(); return; }
    var estilo = document.createElement('link');
    estilo.rel = 'stylesheet';
    estilo.href = LIBRERIA + 'maplibre-gl.min.css';
    document.head.appendChild(estilo);
    var script = document.createElement('script');
    script.src = LIBRERIA + 'maplibre-gl.min.js';
    script.onload = listo;
    document.head.appendChild(script);
  };
  var arrancar = function () { traerLibreria(iniciar); };
  if ('IntersectionObserver' in window) {
    new IntersectionObserver(function (entradas, observador) {
      if (entradas.some(function (e) { return e.isIntersecting; })) { observador.disconnect(); arrancar(); }
    }, { rootMargin: '300px' }).observe(nodo);
  } else {
    arrancar();
  }

  function iniciar() {
  if (!window.maplibregl) { return; }
  var datos = JSON.parse(fuente.textContent);
  var ESTILO = 'https://tiles.openfreemap.org/styles/liberty';
  var CENTRO = [-60.700, -31.636];

  var escapar = function (texto) {
    return String(texto).replace(/[&<>"']/g, function (c) {
      return { '&': '&amp;', '<': '&lt;', '>': '&gt;', '"': '&quot;', "'": '&#39;' }[c];
    });
  };
  var iconos = { facultad: 'birrete', transporte: 'bus', puerto: 'ancla', costanera: 'ola', otro: 'pin' };
  // MapLibre les pone aria-label "Map marker" a todos los marcadores al agregarlos al mapa:
  // se reemplaza por el nombre propio de cada uno, guardado en data-nombre.
  var nombrar = function (elemento) { elemento.setAttribute('aria-label', elemento.dataset.nombre); };

  var mapa = new maplibregl.Map({
    container: nodo,
    style: ESTILO,
    center: CENTRO,
    zoom: 12.5,
    // Con mouse, la rueda sobre el mapa acerca y aleja, y fuera del mapa scrollea la página.
    // En pantallas táctiles, un dedo scrollea la página y dos mueven el mapa (si no, el mapa atrapa el scroll).
    cooperativeGestures: !window.matchMedia('(pointer: fine)').matches,
    attributionControl: { compact: true }
  });
  mapa.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');

  // Puntos estratégicos: íconos chicos, con el nombre al tocarlos. Son botones para que se puedan
  // usar con teclado y el lector de pantalla los anuncie.
  (datos.puntos || []).forEach(function (punto) {
    var elemento = document.createElement('button');
    elemento.type = 'button';
    elemento.className = 'poi';
    elemento.title = punto.nombre;
    elemento.dataset.nombre = punto.nombre;
    elemento.innerHTML = '<svg class="icono" aria-hidden="true"><use href="#i-' + (iconos[punto.categoria] || 'pin') + '"></use></svg>';
    new maplibregl.Marker({ element: elemento })
      .setLngLat([punto.lng, punto.lat])
      .setPopup(new maplibregl.Popup({ offset: 14, closeButton: false, className: 'popup-mapa' }).setText(punto.nombre))
      .addTo(mapa);
    nombrar(elemento);
  });

  // Vista previa de cada propiedad: tarjeta con hasta dos fotos, precio, título, ubicación y rasgos.
  // Con mouse aparece al pasar por encima de la etiqueta (o al llegar con Tab) y un clic abre la ficha;
  // en pantallas táctiles se abre al tocar la etiqueta y tocando la tarjeta se va a la ficha.
  // En la ficha (una sola propiedad, la que se está viendo) queda el globo simple de siempre.
  var conVista = !datos.centrar;
  var conMouse = window.matchMedia('(hover: hover) and (pointer: fine)').matches;
  var icono = function (nombre) { return '<svg class="icono" aria-hidden="true"><use href="#i-' + escapar(nombre) + '"></use></svg>'; };
  var tarjetaVista = function (p) {
    var fotos = (p.fotos && p.fotos.length ? p.fotos : (p.foto ? [p.foto] : [])).slice(0, 2);
    return '<a class="vista-mapa" href="' + escapar(p.url) + '">' +
      (fotos.length
        ? '<span class="vista-mapa__fotos vista-mapa__fotos--' + fotos.length + '">' +
            fotos.map(function (foto) { return '<img src="' + escapar(foto) + '" alt="">'; }).join('') +
            '<span class="vista-mapa__operacion vista-mapa__operacion--' + escapar(p.operacion) + '">' + escapar(p.operacionTexto) + '</span>' +
            (p.reservado ? '<span class="vista-mapa__reservado">Reservado</span>' : '') +
          '</span>'
        : '') +
      '<span class="vista-mapa__cuerpo">' +
        '<strong class="vista-mapa__precio' + (p.conPrecio ? '' : ' vista-mapa__precio--consultar') + '">' + escapar(p.precio) + '</strong>' +
        '<span class="vista-mapa__titulo">' + escapar(p.titulo) + '</span>' +
        '<span class="vista-mapa__ubicacion">' + icono('pin') + escapar(p.ubicacion) + '</span>' +
        ((p.rasgos || []).length
          ? '<span class="vista-mapa__rasgos">' + p.rasgos.map(function (r) { return '<span>' + icono(r[0]) + escapar(r[1]) + '</span>'; }).join('') + '</span>'
          : '') +
        '<span class="vista-mapa__ver">Ver la propiedad ' + icono('flecha') + '</span>' +
      '</span></a>';
  };

  // Con mouse hay una sola tarjeta flotante que se mueve de una etiqueta a otra. Se esconde con
  // una pequeña demora para poder pasar el mouse de la etiqueta a la tarjeta sin que desaparezca.
  // La etiqueta ocupa unos 37 px hacia arriba del punto y hasta ~50 px a cada lado. El mapa elige de
  // qué lado abrir la tarjeta según el espacio: cada lado tiene su distancia para no taparla nunca
  // (si la tapa, la etiqueta pierde el mouse, la tarjeta se cierra y vuelve a abrirse en bucle).
  var DISTANCIA_VISTA = {
    'top': [0, 8], 'top-left': [14, 8], 'top-right': [-14, 8],
    'bottom': [0, -44], 'bottom-left': [56, -4], 'bottom-right': [-56, -4],
    'left': [56, -18], 'right': [-56, -18]
  };
  var vista = new maplibregl.Popup({ offset: DISTANCIA_VISTA, closeButton: false, closeOnClick: false, maxWidth: '280px', className: 'popup-mapa popup-vista' });
  var demora = null;
  var codigoEnVista = null;
  var etiquetaEnVista = null;
  var esconderVista = function () {
    clearTimeout(demora);
    demora = setTimeout(function () {
      // Si el mouse sigue sobre la etiqueta o sobre la tarjeta, no se cierra.
      var tarjeta = vista.getElement();
      if ((etiquetaEnVista && etiquetaEnVista.matches(':hover')) || (tarjeta && tarjeta.querySelector('.maplibregl-popup-content:hover'))) { return; }
      vista.remove();
      codigoEnVista = null;
      etiquetaEnVista = null;
    }, 180);
  };
  var mostrarVista = function (p, etiqueta) {
    clearTimeout(demora);
    etiquetaEnVista = etiqueta;
    if (codigoEnVista === p.codigo && vista.isOpen()) { return; }
    codigoEnVista = p.codigo;
    vista.setLngLat([p.lng, p.lat]).setHTML(tarjetaVista(p)).addTo(mapa);
    var tarjeta = vista.getElement();
    tarjeta.addEventListener('mouseenter', function () { clearTimeout(demora); });
    tarjeta.addEventListener('mouseleave', esconderVista);
    tarjeta.addEventListener('focusin', function () { clearTimeout(demora); });
    tarjeta.addEventListener('focusout', esconderVista);
  };

  var crearEtiqueta = function (p) {
    var elemento = document.createElement('button');
    elemento.type = 'button';
    elemento.className = 'etiqueta-mapa etiqueta-mapa--' + p.operacion;
    elemento.textContent = p.etiqueta;
    elemento.dataset.nombre = p.titulo + ', ' + p.precio;
    var marcador = new maplibregl.Marker({ element: elemento, anchor: 'bottom' }).setLngLat([p.lng, p.lat]);

    if (conVista && conMouse) {
      elemento.addEventListener('mouseenter', function () { mostrarVista(p, elemento); });
      elemento.addEventListener('mouseleave', esconderVista);
      elemento.addEventListener('focus', function () { mostrarVista(p, elemento); });
      elemento.addEventListener('blur', esconderVista);
      elemento.addEventListener('click', function () { (window.sfIrConTransicion || function (url) { window.location.href = url; })(p.url); });
      return marcador;
    }
    if (conVista) {
      var globo = new maplibregl.Popup({ offset: DISTANCIA_VISTA, closeButton: false, maxWidth: '280px', className: 'popup-mapa popup-vista' }).setHTML(tarjetaVista(p));
      // El mapa del celular es chico y la tarjeta mide unos 250 px de alto: quedaba cortada abajo. Al abrirla, la etiqueta
      // sube hasta unos 64 px del borde de arriba y la tarjeta entra completa debajo.
      globo.on('open', function () {
        var alto = mapa.getContainer().clientHeight;
        mapa.easeTo({ center: [p.lng, p.lat], offset: [0, Math.min(0, 64 - alto / 2)], duration: 350 });
      });
      return marcador.setPopup(globo);
    }
    return marcador.setPopup(new maplibregl.Popup({ offset: 22, closeButton: false, maxWidth: '220px', className: 'popup-mapa' }).setHTML(
      '<a class="mapa-popup" href="' + escapar(p.url) + '">' +
        (p.foto ? '<img src="' + escapar(p.foto) + '" alt="">' : '') +
        '<strong>' + escapar(p.precio) + '</strong><span>' + escapar(p.titulo) + '</span></a>'
    ));
  };

  var crearGrupo = function (idGrupo, cantidad, coordenadas) {
    var elemento = document.createElement('button');
    elemento.type = 'button';
    elemento.className = 'grupo-mapa';
    elemento.textContent = cantidad;
    elemento.dataset.nombre = cantidad + ' propiedades: acercar';
    elemento.addEventListener('click', function () {
      mapa.getSource('propiedades').getClusterExpansionZoom(idGrupo).then(function (zoom) {
        mapa.easeTo({ center: coordenadas, zoom: zoom + 0.5 });
      });
    });
    return new maplibregl.Marker({ element: elemento }).setLngLat(coordenadas);
  };

  // Marcadores HTML sincronizados con los grupos que calcula MapLibre en cada vista.
  var creados = {};
  var visibles = {};
  var actualizar = function () {
    if (!mapa.getSource('propiedades') || !mapa.isSourceLoaded('propiedades')) { return; }
    var nuevos = {};
    mapa.querySourceFeatures('propiedades').forEach(function (feature) {
      var props = feature.properties;
      var clave = props.cluster ? 'g' + props.cluster_id : 'p' + props.indice;
      if (nuevos[clave]) { return; }
      if (!creados[clave]) {
        creados[clave] = props.cluster
          ? crearGrupo(props.cluster_id, props.point_count, feature.geometry.coordinates)
          : crearEtiqueta(datos.propiedades[props.indice]);
      }
      nuevos[clave] = creados[clave];
      if (!visibles[clave]) {
        creados[clave].addTo(mapa);
        nombrar(creados[clave].getElement());
      }
    });
    Object.keys(visibles).forEach(function (clave) {
      if (!nuevos[clave]) { visibles[clave].remove(); }
    });
    visibles = nuevos;
  };

  mapa.on('load', function () {
    mapa.addSource('propiedades', {
      type: 'geojson',
      cluster: !datos.centrar,
      clusterRadius: 32,
      clusterMaxZoom: 15,
      data: {
        type: 'FeatureCollection',
        features: datos.propiedades.map(function (p, indice) {
          return { type: 'Feature', geometry: { type: 'Point', coordinates: [p.lng, p.lat] }, properties: { indice: indice } };
        })
      }
    });
    // Capa invisible: hace falta para que MapLibre calcule los grupos de la fuente.
    mapa.addLayer({ id: 'propiedades-base', type: 'circle', source: 'propiedades', paint: { 'circle-radius': 0, 'circle-opacity': 0 } });
    mapa.on('sourcedata', function (evento) { if (evento.sourceId === 'propiedades') { actualizar(); } });
    mapa.on('moveend', actualizar);
    actualizar();
  });

  // Oficina (página de Contacto): los cuadros del logo con el nombre, apoyados sobre la dirección.
  if (datos.oficina) {
    var marcador = document.createElement('div');
    marcador.className = 'marcador-oficina';
    marcador.innerHTML = '<svg viewBox="0 0 52 52" aria-hidden="true">' +
      '<rect class="rojo" x="0" y="0" width="15" height="15"/><rect class="rojo" x="18.5" y="0" width="15" height="15"/><rect class="celeste" x="0" y="18.5" width="15" height="15"/><rect class="celeste" x="18.5" y="18.5" width="15" height="15"/><rect class="celeste" x="37" y="18.5" width="15" height="15"/><rect class="azul" x="0" y="37" width="15" height="15"/><rect class="azul" x="18.5" y="37" width="15" height="15"/><rect class="azul" x="37" y="37" width="15" height="15"/>' +
      '</svg><span>' + escapar(datos.oficina.nombre) + '</span>';
    new maplibregl.Marker({ element: marcador, anchor: 'bottom' }).setLngLat([datos.oficina.lng, datos.oficina.lat]).addTo(mapa);
    marcador.setAttribute('role', 'img');
    marcador.setAttribute('aria-label', datos.oficina.nombre + ', ' + datos.oficina.direccion);
    mapa.jumpTo({ center: [datos.oficina.lng, datos.oficina.lat], zoom: 15.5 });
  }

  // Encuadre: en la ficha, centrado en la propiedad; en el catálogo, las propiedades de la ciudad
  // (las de Sauce Viejo, Recreo o Colastiné se ven al alejar).
  if (datos.centrar && datos.propiedades.length) {
    mapa.jumpTo({ center: [datos.propiedades[0].lng, datos.propiedades[0].lat], zoom: 14.2 });
  } else if (datos.propiedades.length) {
    var enCiudad = datos.propiedades.filter(function (p) {
      return Math.abs(p.lat - CENTRO[1]) < 0.04 && Math.abs(p.lng - CENTRO[0]) < 0.045;
    });
    var lista = enCiudad.length ? enCiudad : datos.propiedades;
    var limites = new maplibregl.LngLatBounds();
    lista.forEach(function (p) { limites.extend([p.lng, p.lat]); });
    mapa.fitBounds(limites, { padding: 60, maxZoom: 15, duration: 0 });
  }
  }
})();
