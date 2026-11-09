// Galería de la propiedad: elegir fotos de la biblioteca, quitarlas y reordenarlas arrastrando.
(function ($) {
  var lista = $('#sfp-galeria');
  var campo = $('#sfp_fotos');
  if (!lista.length) { return; }

  function marcarPortada() {
    lista.children().removeClass('sfp-foto--portada').first().addClass('sfp-foto--portada');
  }
  function guardar() {
    campo.val(lista.children().map(function () { return $(this).data('id'); }).get().join(','));
    marcarPortada();
  }

  lista.sortable({ update: guardar });
  lista.on('click', 'button', function () { $(this).closest('.sfp-foto').remove(); guardar(); });
  marcarPortada();

  var selector;
  $('#sfp-agregar-fotos').on('click', function () {
    if (!selector) {
      selector = wp.media({ title: 'Fotos de la propiedad', button: { text: 'Agregar' }, multiple: true, library: { type: 'image' } });
      selector.on('select', function () {
        selector.state().get('selection').each(function (foto) {
          var datos = foto.toJSON();
          var miniatura = (datos.sizes && (datos.sizes['sfp-chica'] || datos.sizes.thumbnail || datos.sizes.full)) || { url: datos.url };
          var bloque = $('<div class="sfp-foto"></div>').attr('data-id', datos.id).data('id', datos.id);
          bloque.append($('<img>').attr('src', miniatura.url)).append('<button type="button" aria-label="Quitar foto">×</button>');
          lista.append(bloque);
        });
        guardar();
      });
    }
    selector.open();
  });
})(jQuery);
