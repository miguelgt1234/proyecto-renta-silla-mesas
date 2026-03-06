<?php
/**
 * Controlador para integración con Firebase Cloud Messaging (HTTP v1).
 *
 * Endpoint:
 * POST https://fcm.googleapis.com/v1/projects/{project_id}/messages:send
 */

class FcmHandler
{
    private array $config;

    public function __construct()
    {
        $config = require __DIR__ . '/../config/google_apis.php';
        $this->config = $config['fcm'] ?? [];
    }

    public function enviarNotificacionPedido(?string $deviceToken, array $pedidoData): array
    {
        $projectId = $this->config['project_id'] ?? '';
        $accessToken = $this->config['access_token'] ?? '';

        if (!$this->esValorConfigurado($projectId) || !$this->esValorConfigurado($accessToken)) {
            return [
                'success' => false,
                'message' => 'FCM no configurado: define TU_PROJECT_ID_DE_FIREBASE y TU_ACCESS_TOKEN_DE_FCM.',
            ];
        }

        if ($deviceToken === null || $deviceToken === '') {
            return [
                'success' => false,
                'message' => 'No hay device token para enviar FCM.',
            ];
        }

        $url = 'https://fcm.googleapis.com/v1/projects/' . rawurlencode($projectId) . '/messages:send';
        $payload = [
            'message' => [
                'token' => $deviceToken,
                'notification' => [
                    'title' => 'Pedido confirmado',
                    'body' => 'Tu pedido #' . ($pedidoData['id_pedido'] ?? '') . ' fue creado correctamente.',
                ],
                'data' => [
                    'id_pedido' => (string)($pedidoData['id_pedido'] ?? ''),
                    'tipo' => 'pedido_confirmado',
                ],
            ],
        ];

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
                'message' => 'Error al conectar con FCM.',
                'error' => $curlError,
            ];
        }

        $data = json_decode($response, true);

        if ($httpCode < 200 || $httpCode >= 300) {
            return [
                'success' => false,
                'message' => 'FCM devolvió error.',
                'http_code' => $httpCode,
                'error' => $data,
            ];
        }

        return [
            'success' => true,
            'message' => 'Push enviado por FCM.',
            'name' => $data['name'] ?? null,
        ];
    }

    private function esValorConfigurado(string $value): bool
    {
        return $value !== '' && stripos($value, 'TU_') !== 0;
    }
}
