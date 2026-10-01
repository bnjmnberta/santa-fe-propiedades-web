// Panel: confirmaciones, estado de la subida de fotos, copiar el texto para Instagram y mapa de ubicación.
(function () {
  'use strict';

  // Formularios que borran o dan de baja: piden confirmación antes de enviarse.
  document.querySelectorAll('form[data-confirmar]').forEach(function (formulario) {
    formulario.addEventListener('submit', function (evento) {
      if (!window.confirm(formulario.dataset.confirmar)) { evento.preventDefault(); }
    });
  });

  // Subida de fotos: muestra cuántas se eligieron y evita el doble envío.
  document.querySelectorAll('[data-subir-fotos]').forEach(function (formulario) {
    var campo = formulario.querySelector('input[type="file"]');
    var estado = formulario.querySelector('[data-subir-estado]');
    campo.addEventListener('change', function () {
      var cantidad = campo.files.length;
      estado.textContent = cantidad === 1 ? '1 foto elegida' : cantidad + ' fotos elegidas';
    });
    formulario.addEventListener('submit', function () {
      var boton = formulario.querySelector('button[type="submit"]');
      boton.disabled = true;
      boton.textContent = 'Subiendo…';
    });
  });

  // Copiar el texto armado para Instagram.
  var boton = document.querySelector('[data-copiar-instagram]');
  var texto = document.querySelector('[data-texto-instagram]');
  if (boton && texto) {
    boton.addEventListener('click', function () {
      var listo = function () {
        boton.textContent = '¡Copiado!';
        setTimeout(function () { boton.textContent = 'Copiar texto'; }, 2000);
      };
      if (navigator.clipboard) {
        navigator.clipboard.writeText(texto.value).then(listo);
      } else {
        texto.select();
        document.execCommand('copy');
        listo();
      }
    });
  }

  // Ubicación en el mapa: tocar el mapa o arrastrar el pin completa el campo de coordenadas,
  // y escribir o pegar en el campo mueve el pin. MapLibre se carga solo en esta pantalla.
  var lienzo = document.querySelector('[data-mapa-coordenadas]');
  if (lienzo) {
    var campo = document.getElementById(lienzo.dataset.mapaCoordenadas);
    var VERSION = 'https://cdnjs.cloudflare.com/ajax/libs/maplibre-gl/4.7.1/';
    var estilo = document.createElement('link');
    estilo.rel = 'stylesheet';
    estilo.href = VERSION + 'maplibre-gl.min.css';
    document.head.appendChild(estilo);
    var libreria = document.createElement('script');
    libreria.src = VERSION + 'maplibre-gl.min.js';
    libreria.onload = function () { armarMapa(lienzo, campo); };
    document.head.appendChild(libreria);
  }

  // Mismo criterio que ValidadorPropiedad::coordenadas(): "-31.63, -60.71" o un enlace de Google Maps,
  // dentro de Santa Fe y alrededores.
  function leerCoordenadas(texto) {
    var m = /(-3\d\.\d+)\s*,\s*(-6\d\.\d+)/.exec(texto);
    if (!m) { return null; }
    var lat = parseFloat(m[1]);
    var lng = parseFloat(m[2]);
    return lat > -32.5 && lat < -30.5 && lng > -61.5 && lng < -60 ? [lng, lat] : null;
  }

  function armarMapa(lienzo, campo) {
    var inicial = leerCoordenadas(campo.value);
    lienzo.hidden = false;
    var mapa = new maplibregl.Map({
      container: lienzo,
      style: 'https://tiles.openfreemap.org/styles/liberty',
      center: inicial || [-60.700, -31.636],
      zoom: inicial ? 15.5 : 12.5,
      cooperativeGestures: true,
      attributionControl: { compact: true }
    });
    mapa.addControl(new maplibregl.NavigationControl({ showCompass: false }), 'bottom-right');
    var pin = new maplibregl.Marker({ color: '#d0342c', draggable: true });
    if (inicial) { pin.setLngLat(inicial).addTo(mapa); }

    var escribir = function (lngLat) {
      campo.value = lngLat.lat.toFixed(6) + ', ' + lngLat.lng.toFixed(6);
      campo.dispatchEvent(new Event('input', { bubbles: true }));
    };
    mapa.on('click', function (evento) {
      pin.setLngLat(evento.lngLat).addTo(mapa);
      escribir(evento.lngLat);
    });
    pin.on('dragend', function () { escribir(pin.getLngLat()); });
    campo.addEventListener('change', function () {
      var punto = leerCoordenadas(campo.value);
      if (punto) {
        pin.setLngLat(punto).addTo(mapa);
        mapa.easeTo({ center: punto, zoom: Math.max(mapa.getZoom(), 15) });
      }
    });
  }
})();
