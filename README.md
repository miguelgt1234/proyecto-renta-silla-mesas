# proyecto-renta-silla-mesas
sistema de renta de sillas y mesas proyecto integrador 2

## flujo de carrito, confirmación y autenticación
- el catálogo ahora muestra unidades disponibles por producto y permite agregar con cantidad solicitada.
- si el cliente no inició sesión, agregar al carrito redirige a inicio de sesión.
- el carrito permite ver cantidades, disminuir y eliminar productos.
- desde carrito se confirma pedido en `confirmar_pedido.php`.
- al confirmar se genera el pedido en base de datos, se guarda la dirección, se actualiza stock y se registra notificación interna.
- además, el flujo de confirmación intenta usar:
  - **google maps javascript api** en frontend para selección de ubicación.
  - **google calendar api** en backend para crear evento del pedido.
  - **firebase cloud messaging (fcm) http v1** para push al cliente.

## cómo solicitar y configurar google maps api
1. entra a [google cloud console](https://console.cloud.google.com/) y crea un proyecto.
2. activa facturación del proyecto (google maps lo requiere).
3. abre **apis y servicios > biblioteca** y habilita:
   - maps javascript api
   - places api (opcional, pero recomendada para autocompletar dirección)
   - geocoding api (opcional)
4. en **apis y servicios > credenciales**, crea una **api key**.
5. restringe la key por http referrer (dominio/sitio) para seguridad.
6. reemplaza `TU_API_KEY_DE_GOOGLE_MAPS` en `backend/config/google_apis.php`.

## cómo solicitar google calendar api
1. en el mismo proyecto de google cloud, habilita **google calendar api**.
2. crea credenciales oauth 2.0 para aplicación web o service account según tu arquitectura.
3. genera un access token oauth con scope:
   - `https://www.googleapis.com/auth/calendar.events`
4. configura en `backend/config/google_apis.php`:
   - `TU_ACCESS_TOKEN_DE_GOOGLE_CALENDAR`
   - `calendar_id`
5. el backend usa `events.insert` vía REST API para crear evento al confirmar pedido.

## cómo configurar firebase cloud messaging (fcm)
1. entra a [firebase console](https://console.firebase.google.com/) y crea un proyecto.
2. asocia el proyecto de google cloud o crea uno nuevo.
3. en **project settings > cloud messaging**, habilita fcm.
4. genera token oauth para fcm http v1 con scope:
   - `https://www.googleapis.com/auth/firebase.messaging`
5. configura en `backend/config/google_apis.php`:
   - `TU_PROJECT_ID_DE_FIREBASE`
   - `TU_ACCESS_TOKEN_DE_FCM`
6. en frontend web registra service worker y envía `fcm_device_token` al confirmar pedido.
7. el backend usa `messages:send` de fcm http v1 para push real.

## ejecución sugerida
- abrir catálogo desde `backend/controllers/catalago.php`.
- páginas cliente en php:
  - `frontend/HTML/cliente/catalogo.php`
  - `frontend/HTML/cliente/carrito.php`
  - `frontend/HTML/cliente/confirmar_pedido.php`
  - `frontend/HTML/cliente/pedido.php`
  - `frontend/HTML/cliente/inicio_de_sesion.php`
  - `frontend/HTML/cliente/registro.php`
