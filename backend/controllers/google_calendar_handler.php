<?php
/**
 * Controlador para integración con Google Calendar
 * Genera enlaces para crear eventos sin requerir autenticación directa
 * 
 * Versión sin dependencias externas - Funciona inmediatamente
 * 
 * NOTA: Para integración automática completa con Calendar API,
 * instala: composer require google/apiclient
 */

class GoogleCalendarHandler {
    
    /**
     * Genera un enlace para crear evento en Google Calendar
     * El usuario puede hacer clic y confirmar en su navegador
     */
    public static function generarEnlaceCalendar(array $pedidoData) {
        $inicio = self::formatoGoogleCalendar($pedidoData['fecha_entrega']);
        $fin = self::formatoGoogleCalendar($pedidoData['fecha_recogida']);
        
        $titulo = 'Renta de Mobiliario - Pedido #' . $pedidoData['id_pedido'];
        
        $descripcion = "Cliente: " . $pedidoData['cliente_nombre'] . "\n" .
                      "Pedido #: " . $pedidoData['id_pedido'] . "\n" .
                      "Total: $" . number_format($pedidoData['total'], 2) . "\n" .
                      "Dirección: " . $pedidoData['direccion'];

        $url = 'https://calendar.google.com/calendar/render?' .
               'action=TEMPLATE' .
               '&text=' . urlencode($titulo) .
               '&dates=' . $inicio . '/' . $fin .
               '&details=' . urlencode($descripcion) .
               '&location=' . urlencode($pedidoData['direccion']) .
               '&ctz=America/Mexico_City';

        return $url;
    }

    /**
     * Crea un evento en Google Calendar
     * Versión simple sin API directa - retorna enlace para que usuario lo cree
     */
    public function crearEventoPedido(array $pedidoData) {
        try {
            $enlace = self::generarEnlaceCalendar($pedidoData);
            
            return [
                'success' => true,
                'message' => 'Enlace generado. Haz clic para crear el evento en Google Calendar.',
                'calendar_link' => $enlace,
                'event_id' => 'template_' . $pedidoData['id_pedido'],
                'event_link' => $enlace
            ];
        } catch (Exception $e) {
            return [
                'success' => false,
                'message' => 'Error al generar enlace de calendario: ' . $e->getMessage(),
                'error' => $e->getMessage()
            ];
        }
    }

    /**
     * Formatea una fecha al formato de Google Calendar (YYYYMMDDTHHMMSSZ)
     */
    private static function formatoGoogleCalendar($fecha) {
        try {
            $dt = new DateTime($fecha, new DateTimeZone('America/Mexico_City'));
            return $dt->format('Ymd\THis\Z');
        } catch (Exception $e) {
            // Fallback si hay error
            return date('Ymd\THis\Z', strtotime($fecha));
        }
    }
}

// Endpoint para generar enlace (AJAX)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'crear_evento') {
    header('Content-Type: application/json; charset=utf-8');

    try {
        $pedidoData = [
            'id_pedido' => $_POST['id_pedido'] ?? null,
            'cliente_nombre' => $_POST['cliente_nombre'] ?? 'Cliente',
            'fecha_entrega' => $_POST['fecha_entrega'] ?? '',
            'fecha_recogida' => $_POST['fecha_recogida'] ?? '',
            'direccion' => $_POST['direccion'] ?? '',
            'total' => $_POST['total'] ?? 0
        ];

        $handler = new GoogleCalendarHandler();
        $resultado = $handler->crearEventoPedido($pedidoData);

        echo json_encode($resultado);
    } catch (Exception $e) {
        echo json_encode([
            'success' => false,
            'message' => $e->getMessage()
        ]);
    }
    exit;
}

