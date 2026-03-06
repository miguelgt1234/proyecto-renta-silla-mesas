<?php
/**
 * Controlador para integración con Google Calendar API.
 *
 * Usa REST API directa (events.insert):
 * POST https://www.googleapis.com/calendar/v3/calendars/{calendarId}/events
 */

class GoogleCalendarHandler
{
    private array $config;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/google_apis.php';
        $this->config = $config['calendar'] ?? [];
    }

    public function crearEventoPedido(array $pedidoData): array
    {
        $accessToken = $this->config['access_token'] ?? '';
        $calendarId = $this->config['calendar_id'] ?? 'primary';
        $timezone = $this->config['timezone'] ?? 'America/Mexico_City';

        if (!$this->esValorConfigurado($accessToken)) {
            return [
                'success' => false,
                'message' => 'Google Calendar no configurado: define TU_ACCESS_TOKEN_DE_GOOGLE_CALENDAR.',
            ];
        }

        $payload = [
            'summary' => 'Renta de Mobiliario - Pedido #' . ($pedidoData['id_pedido'] ?? ''),
            'description' => $this->construirDescripcion($pedidoData),
            'location' => (string)($pedidoData['direccion'] ?? ''),
            'start' => [
                'dateTime' => $this->toIso8601($pedidoData['fecha_entrega'] ?? ''),
                'timeZone' => $timezone,
            ],
            'end' => [
                'dateTime' => $this->toIso8601($pedidoData['fecha_recogida'] ?? ''),
                'timeZone' => $timezone,
            ],
        ];

        $url = 'https://www.googleapis.com/calendar/v3/calendars/' . rawurlencode($calendarId) . '/events';

        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => true,
            CURLOPT_HTTPHEADER => [
                'Authorization: Bearer ' . $accessToken,
                'Content-Type: application/json',
            ],
            CURLOPT_POSTFIELDS => json_encode($payload, JSON_UNESCAPED_UNICODE),
            CURLOPT_TIMEOUT => 20,
        ]);

        $response = curl_exec($ch);
        $httpCode = (int)curl_getinfo($ch, CURLINFO_HTTP_CODE);
        $curlError = curl_error($ch);
        curl_close($ch);

        if ($response === false || $curlError !== '') {
            return [
                'success' => false,
                'message' => 'Error al conectar con Google Calendar API.',
                'error' => $curlError,
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'message' => 'Google Calendar API devolvió error.',
                'http_code' => $httpCode,
                'error' => $data,
            ];
        }

        return [
            'success' => true,
            'message' => 'Evento creado en Google Calendar API.',
            'event_id' => $data['id'] ?? null,
            'event_link' => $data['htmlLink'] ?? null,
        ];
    }

    private function construirDescripcion(array $pedidoData): string
    {
        return "Cliente: " . ($pedidoData['cliente_nombre'] ?? 'Cliente') . "\n"
            . "Pedido #: " . ($pedidoData['id_pedido'] ?? '') . "\n"
            . "Total: $" . number_format((float)($pedidoData['total'] ?? 0), 2) . "\n"
            . "Dirección: " . ($pedidoData['direccion'] ?? '');
    }

    private function toIso8601(string $fecha): string
    {
        $dt = new DateTime($fecha ?: 'now', new DateTimeZone('America/Mexico_City'));
        return $dt->format(DateTime::ATOM);
    }

    private function esValorConfigurado(string $value): bool
    {
        return $value !== '' && stripos($value, 'TU_') !== 0;
    }
}
