// Avisos del panel (notificaciones push). Lo registra la pantalla de Chats cuando un administrador activa los avisos en su dispositivo.
self.addEventListener('install', function () { self.skipWaiting(); });
self.addEventListener('activate', function (evento) { evento.waitUntil(self.clients.claim()); });

self.addEventListener('push', function (evento) {
  var datos = {};
  try { datos = evento.data ? evento.data.json() : {}; } catch (error) { datos = { texto: evento.data ? evento.data.text() : '' }; }
  evento.waitUntil(self.registration.showNotification(datos.titulo || 'Santa Fe Propiedades', {
    body: datos.texto || '',
    icon: '/assets/img/favicon-48.png',
    badge: '/assets/img/favicon-48.png',
    tag: datos.etiqueta || 'aviso',
    renotify: true,
    data: { url: datos.url || '/panel/chats' }
  }));
});

self.addEventListener('notificationclick', function (evento) {
  evento.notification.close();
  var destino = new URL((evento.notification.data && evento.notification.data.url) || '/panel/chats', self.location.origin).href;
  evento.waitUntil(self.clients.matchAll({ type: 'window', includeUncontrolled: true }).then(function (ventanas) {
    for (var i = 0; i < ventanas.length; i++) {
      if (ventanas[i].url.indexOf(self.location.origin + '/panel') === 0 && 'focus' in ventanas[i]) {
        return ventanas[i].navigate(destino).then(function (ventana) { return ventana && ventana.focus(); });
      }
    }
    return self.clients.openWindow(destino);
  }));
});
