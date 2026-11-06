<?php
/** @var bool $conectado */
/** @var string $token */
?>
<div class="panel-encabezado">
  <div>
    <h1>Chats de WhatsApp</h1>
  </div>
</div>

<?php if (!$conectado): ?>
  <p class="panel-aviso panel-aviso--error" role="alert">El panel todavía no está conectado con el asistente. Falta cargar la dirección y la clave en la configuración del servidor.</p>
<?php else: ?>
<section class="wsp" data-chats data-base="/panel/chats" data-csrf="<?= e($token) ?>">
  <aside class="wsp__lista" aria-label="Conversaciones">
    <div class="wsp__lista-cabecera">
      <strong>Chats</strong>
      <span class="wsp__conexion" data-conexion title="Estado de la conexión con el asistente">●</span>
    </div>
    <div class="wsp__buscador">
      <svg class="wsp__lupa" viewBox="0 0 24 24" width="18" height="18" aria-hidden="true"><circle cx="11" cy="11" r="7" fill="none" stroke="currentColor" stroke-width="2"/><path d="M16.5 16.5 21 21" stroke="currentColor" stroke-width="2" stroke-linecap="round"/></svg>
      <input type="search" placeholder="Buscar chats o mensajes" aria-label="Buscar en los chats" autocomplete="off" enterkeyhint="search" data-buscar>
      <button type="button" class="wsp__limpiar" aria-label="Borrar la búsqueda" title="Borrar la búsqueda (Esc)" data-limpiar hidden>✕</button>
    </div>
    <p class="wsp__resumen" data-resumen role="status" hidden></p>
    <div class="wsp__temas" role="tablist" aria-label="Tipo de consulta" data-temas>
      <button type="button" role="tab" class="wsp__tema wsp__tema--activo" aria-selected="true" data-tema="todos">Todos <span data-cuenta-tema="todos"></span></button>
      <button type="button" role="tab" class="wsp__tema" aria-selected="false" data-tema="venta">Venta <span data-cuenta-tema="venta"></span></button>
      <button type="button" role="tab" class="wsp__tema" aria-selected="false" data-tema="alquiler">Alquiler <span data-cuenta-tema="alquiler"></span></button>
      <button type="button" role="tab" class="wsp__tema" aria-selected="false" data-tema="locales">Locales <span data-cuenta-tema="locales"></span></button>
      <button type="button" role="tab" class="wsp__tema" aria-selected="false" data-tema="otros">Otros <span data-cuenta-tema="otros"></span></button>
    </div>
    <ul class="wsp__chats" data-lista></ul>
    <p class="wsp__vacio" data-vacio hidden>Todavía no hay conversaciones.</p>
  </aside>

  <div class="wsp__conversacion" data-conversacion>
    <div class="wsp__inicio" data-sin-chat>
      <div class="wsp__inicio-icono">💬</div>
      <p><strong>Elegí un chat</strong> para ver la conversación.</p>
      <p class="wsp__chico">Cuando un cliente pregunte por una compra, el chat te aparece acá con una marca y le llega un aviso al WhatsApp del equipo.</p>
    </div>

    <div class="wsp__chat" data-chat hidden>
      <header class="wsp__chat-cabecera">
        <button type="button" class="wsp__volver" data-volver aria-label="Volver a la lista">←</button>
        <span class="wsp__avatar" data-chat-avatar></span>
        <div class="wsp__chat-titulo">
          <strong data-chat-nombre></strong>
          <span data-chat-numero></span>
        </div>
        <button type="button" class="boton boton--chico wsp__modo" data-modo></button>
      </header>
      <p class="wsp__banner" data-banner hidden></p>
      <div class="wsp__mensajes" data-mensajes role="log" aria-live="polite"></div>
      <form class="wsp__escribir" data-formulario autocomplete="off">
        <textarea rows="1" placeholder="Escribí un mensaje" aria-label="Mensaje" data-texto maxlength="4000"></textarea>
        <button type="button" class="wsp__enviar wsp__mic" aria-label="Grabar un audio" title="Grabar un audio" data-mic>🎤</button>
        <button type="submit" class="wsp__enviar" aria-label="Enviar" data-enviar>➤</button>
      </form>
      <div class="wsp__grabando" data-grabando hidden>
        <button type="button" class="wsp__enviar wsp__enviar--descartar" aria-label="Descartar el audio" title="Descartar" data-descartar>🗑</button>
        <span class="wsp__punto" aria-hidden="true"></span>
        <span class="wsp__tiempo" data-tiempo aria-live="off">0:00</span>
        <span class="wsp__chico">Grabando…</span>
        <button type="button" class="wsp__enviar" aria-label="Enviar el audio" title="Enviar el audio" data-enviar-audio>➤</button>
      </div>
    </div>
  </div>
</section>
<script src="<?= e(asset('assets/js/chats.js')) ?>" defer></script>
<?php endif ?>
