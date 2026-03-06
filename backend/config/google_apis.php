<?php
/**
 * Configuración de Google APIs
 *
 * IMPORTANTE:
 * - Deja aquí solo placeholders.
 * - Reemplaza con valores reales en tu entorno local/producción.
 */

return [
    // Google Maps JavaScript API Key (frontend)
    'maps_api_key' => 'TU_API_KEY_DE_GOOGLE_MAPS',

    // Google Calendar API (OAuth 2.0)
    'calendar' => [
        // Token OAuth 2.0 con scope: https://www.googleapis.com/auth/calendar.events
        // Ejemplo: ya29.a0AfH6S... (NO subir uno real al repo)
        'access_token' => 'TU_ACCESS_TOKEN_DE_GOOGLE_CALENDAR',

        // ID del calendario donde crear eventos. Ejemplo: primary
        'calendar_id' => 'primary',

        // Timezone de los eventos
        'timezone' => 'America/Mexico_City',
    ],

    // Firebase Cloud Messaging HTTP v1
    'fcm' => [
        // ID de proyecto de Firebase/Google Cloud
        'project_id' => 'TU_PROJECT_ID_DE_FIREBASE',

        // Access token OAuth 2.0 para FCM HTTP v1
        // Scope: https://www.googleapis.com/auth/firebase.messaging
        'access_token' => 'TU_ACCESS_TOKEN_DE_FCM',
    ],
];
