// Panel: confirmaciones, estado de la subida de fotos y copiar el texto para Instagram.
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
})();
