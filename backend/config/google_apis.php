<?php
/**
 * Configuración de Google APIs
 * Guarda aquí tus credenciales de Google
 */

return [
    // Google Maps API Key (usado en frontend)
    'maps_api_key' => 'AIzaSyBFYpRdvQXuQJw5FQtt4O8RkmOJBAGypR0',

    // Google Calendar API - Credenciales (OAuth 2.0)
    // Descarga el JSON desde Google Cloud Console: https://console.cloud.google.com/apis/credentials
    'calendar' => [
        // Ruta al archivo JSON de credenciales de Google (servicio)
        'credentials_path' => __DIR__ . '/google-calendar-credentials.json',
        
        // ID del calendario donde crear eventos (puedes usar 'primary' para el calendario principal)
        'calendar_id' => 'primary',
        
        // Token de acceso (se genera automáticamente con OAuth)
        'access_token' => null,
    ]
];
