# proyecto-renta-silla-mesas
sistema de renta de sillas y mesas proyecto integrador 2

## flujo de carrito, confirmación y autenticación
- el catálogo ahora muestra unidades disponibles por producto y permite agregar con cantidad solicitada.
- si el cliente no inició sesión, agregar al carrito redirige a inicio de sesión.
- el carrito permite ver cantidades, disminuir y eliminar productos.
- desde carrito se confirma pedido en `confirmar_pedido.php`.
- al confirmar se genera el pedido en base de datos, se guarda la dirección, se actualiza stock y se registra notificación interna.

## cómo solicitar y configurar google maps api
1. entra a [google cloud console](https://console.cloud.google.com/) y crea un proyecto.
2. activa facturación del proyecto (google maps lo requiere).
3. abre **apis y servicios > biblioteca** y habilita:
   - maps javascript api
   - places api (opcional, pero recomendada para autocompletar dirección)
   - geocoding api (opcional)
4. en **apis y servicios > credenciales**, crea una **api key**.
5. restringe la key por http referrer (dominio/sitio) para seguridad.
6. reemplaza `TU_API_KEY_DE_GOOGLE_MAPS` en `frontend/HTML/cliente/confirmar_pedido.php`.

## cómo solicitar google calendar api
1. en el mismo proyecto de google cloud, habilita **google calendar api**.
2. crea credenciales oauth 2.0 para aplicación web.
3. registra url autorizadas de tu app.
4. para integración completa (crear evento automático), implementa flujo oauth y luego usa el endpoint `events.insert`.
5. actualmente el sistema deja un enlace rápido para crear evento en calendar (sin oauth).

## cómo configurar firebase cloud messaging (fcm)
1. entra a [firebase console](https://console.firebase.google.com/) y crea un proyecto.
2. asocia el proyecto de google cloud o crea uno nuevo.
3. en **project settings > cloud messaging**, habilita fcm y copia:
   - sender id
   - server key / credenciales de cuenta de servicio (http v1)
4. en tu frontend web registra un service worker para obtener el token de dispositivo.
5. guarda ese token por cliente en base de datos (tabla sugerida: `tokens_dispositivo_cliente`).
6. al confirmar pedido, en backend usa firebase admin sdk o llamada http v1 a fcm para enviar mensaje push real al token.
7. en este estado inicial, el sistema deja una notificación interna en la tabla `notificaciones` como base para la integración final.

## ejecución sugerida
- abrir catálogo desde `backend/controllers/catalago.php`.
- páginas cliente en php:
  - `frontend/HTML/cliente/catalogo.php`
  - `frontend/HTML/cliente/carrito.php`
  - `frontend/HTML/cliente/confirmar_pedido.php`
  - `frontend/HTML/cliente/pedido.php`
  - `frontend/HTML/cliente/inicio_de_sesion.php`
  - `frontend/HTML/cliente/registro.php`
