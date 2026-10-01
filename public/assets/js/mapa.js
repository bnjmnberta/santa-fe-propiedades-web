// Mapa de propiedades + puntos estratégicos de Santa Fe (facultades, terminal, puerto, costanera).
// MapLibre GL + OpenFreeMap: mapa vectorial, gratis también para uso comercial, sin clave de API.
// Las propiedades son etiquetas con el precio; las cercanas se agrupan en una burbuja con la cantidad.
(function () {
  'use strict';
  var nodo = document.getElementById('mapa');
  var fuente = document.getElementById('datos-mapa');
  if (!nodo || !fuente || !window.maplibregl) { return; }
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
    cooperativeGestures: true, // con un dedo se scrollea la página; con dos, se mueve el mapa
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

  var crearEtiqueta = function (p) {
    var elemento = document.createElement('button');
    elemento.type = 'button';
    elemento.className = 'etiqueta-mapa etiqueta-mapa--' + p.operacion;
    elemento.textContent = p.etiqueta;
    elemento.dataset.nombre = p.titulo + ', ' + p.precio;
    var popup = new maplibregl.Popup({ offset: 22, closeButton: false, maxWidth: '220px', className: 'popup-mapa' }).setHTML(
      '<a class="mapa-popup" href="' + escapar(p.url) + '">' +
        (p.foto ? '<img src="' + escapar(p.foto) + '" alt="">' : '') +
        '<strong>' + escapar(p.precio) + '</strong><span>' + escapar(p.titulo) + '</span></a>'
    );
    return new maplibregl.Marker({ element: elemento, anchor: 'bottom' }).setLngLat([p.lng, p.lat]).setPopup(popup);
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
    marcador.innerHTML = '<svg viewBox="0 0 26 26" aria-hidden="true">' +
      '<rect x="9" y="0" width="8" height="8"/><rect x="18" y="0" width="8" height="8"/>' +
      '<rect class="azul" x="0" y="9" width="8" height="8"/><rect class="azul" x="9" y="9" width="8" height="8"/><rect class="azul" x="18" y="9" width="8" height="8"/>' +
      '<rect class="azul" x="0" y="18" width="8" height="8"/><rect class="azul" x="9" y="18" width="8" height="8"/><rect class="azul" x="18" y="18" width="8" height="8"/>' +
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
})();
