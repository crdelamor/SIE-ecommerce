<?php
declare(strict_types=1);

require_once __DIR__ . '/../lib/PHPMailer/Exception.php';
require_once __DIR__ . '/../lib/PHPMailer/PHPMailer.php';
require_once __DIR__ . '/../lib/PHPMailer/SMTP.php';

use PHPMailer\PHPMailer\PHPMailer;

/**
 * Correo de confirmación del pedido por SMTP (PHPMailer).
 * Los datos del servidor están en config.php, bloque 'mail'. Si está desactivado o falla,
 * no pasa nada: el pedido ya está pagado y el error queda en el log del servidor.
 */
final class MailService
{
    // $config solo se pasa en pruebas; si no, se lee el bloque 'mail' de config.php
    public function __construct(
        private OrderRepository $orders = new OrderRepository(),
        private ?array $config = null,
    ) {
    }

    // Devuelve true si el correo se ha enviado
    public function sendOrderConfirmation(string $code): bool
    {
        $config = $this->config ?? (require __DIR__ . '/../config.php')['mail'] ?? null;
        if (empty($config['enabled'])) {
            return false;
        }

        $contact = $this->orders->findContact($code);
        $order = $this->orders->findSummary($code);
        if ($contact === null || $order === null) {
            return false;
        }

        try {
            $mail = new PHPMailer(true);
            $mail->isSMTP();
            // Si el servidor SMTP no contesta, como mucho 10 s (por defecto son 300 y el pago se queda esperando)
            $mail->Timeout = 10;
            $mail->getSMTPInstance()->Timelimit = 10;
            $mail->Host = $config['host'];
            $mail->Port = (int) $config['port'];
            $mail->SMTPSecure = $config['secure'] ?? PHPMailer::ENCRYPTION_STARTTLS; // 'tls' = STARTTLS
            $mail->SMTPAuth = ($config['user'] ?? '') !== '';
            $mail->Username = $config['user'];
            $mail->Password = $config['password'];
            $mail->CharSet = PHPMailer::CHARSET_UTF8;

            $mail->setFrom($config['from'], $config['from_name']);
            $mail->addAddress($contact['email'], $contact['name']);
            $mail->Subject = "Tu pedido {$order['code']} está confirmado";
            $mail->isHTML(true);
            $mail->Body = $this->html($order, $contact['name'], $config['site_url']);
            $mail->AltBody = $this->text($order, $contact['name'], $config['site_url']);

            $mail->send();
            return true;
        } catch (Throwable $e) {
            error_log('No se pudo enviar el correo del pedido ' . $code . ': ' . $e->getMessage());
            return false;
        }
    }

    private function text(array $order, string $name, string $siteUrl): string
    {
        $lines = [];
        foreach ($order['items'] as $item) {
            $lines[] = "- {$item['quantity']} x {$item['name']}: " . $this->euros($item['line_total']);
        }

        return "Hola, {$name}:\n\n"
            . "Hemos recibido tu pedido {$order['code']}.\n\n"
            . implode("\n", $lines) . "\n\n"
            . 'Subtotal (sin IVA): ' . $this->euros($order['subtotal']) . "\n"
            . ($order['discount'] > 0 ? 'Descuento: -' . $this->euros($order['discount']) . "\n" : '')
            . 'Envío: ' . ($order['shipping'] > 0 ? $this->euros($order['shipping']) : 'Gratis') . "\n"
            . 'IVA: ' . $this->euros($order['tax']) . "\n"
            . 'Total: ' . $this->euros($order['total']) . "\n\n"
            . "Puedes ver tu pedido en {$siteUrl}/#/pedido/{$order['code']}\n\n"
            . "The Black Lotus TCG · Prototipo académico, sin actividad comercial real: no se ha cobrado nada.\n";
    }

    // Los correos solo admiten tablas y estilos en línea. Colores de theme.css.
    private function html(array $order, string $name, string $siteUrl): string
    {
        $e = fn (string $text): string => htmlspecialchars($text, ENT_QUOTES, 'UTF-8');
        $font = "font-family:'Space Grotesk',Arial,sans-serif";

        $rows = '';
        foreach ($order['items'] as $item) {
            $image = $item['image_url'] ?? 'placeholder.png';
            if (!str_starts_with($image, 'http')) {
                $image = "{$siteUrl}/{$image}";
            }
            $rows .= '<tr>'
                . '<td style="padding:10px 0;width:56px"><img src="' . $e($image) . '" width="56" height="56" alt=""'
                . ' style="display:block;width:56px;height:56px;object-fit:cover;border:1px solid #e3e3dd;border-radius:8px;background:#fafaf7"></td>'
                . '<td style="padding:10px 12px"><strong>' . $e($item['name']) . '</strong>'
                . '<br><span style="color:#5a5a5a;font-size:13px">Cantidad: ' . $item['quantity'] . '</span></td>'
                . '<td style="padding:10px 0;text-align:right;white-space:nowrap">' . $this->euros($item['line_total']) . '</td>'
                . '</tr>';
        }

        $line = fn (string $label, string $value): string =>
            '<tr><td style="padding:3px 0;color:#5a5a5a">' . $label . '</td>'
            . '<td style="padding:3px 0;text-align:right;white-space:nowrap">' . $value . '</td></tr>';

        $link = $e("{$siteUrl}/#/pedido/{$order['code']}");

        return '<div style="background:#fafaf7;padding:24px 12px;' . $font . ';color:#111">'
            . '<div style="max-width:560px;margin:0 auto;background:#fff;border:1px solid #e3e3dd;border-radius:8px;overflow:hidden">'
            . '<div style="background:#111;color:#fff;padding:18px 24px;font-size:20px;font-weight:bold">'
            . 'The Black Lotus <span style="color:#c8102e">TCG</span></div>'
            . '<div style="padding:24px">'
            . '<p style="margin:0 0 4px;color:#5a5a5a;font-size:14px">Pedido ' . $e($order['code']) . '</p>'
            . '<h1 style="margin:0 0 8px;font-size:24px">¡Gracias, ' . $e($name) . '!</h1>'
            . '<p style="margin:0 0 20px">Hemos recibido tu pedido y el pago está confirmado.</p>'
            . '<table style="width:100%;border-collapse:collapse;border-top:1px solid #e3e3dd;border-bottom:1px solid #e3e3dd">'
            . $rows . '</table>'
            . '<table style="width:100%;border-collapse:collapse;margin-top:12px">'
            . $line('Subtotal (sin IVA)', $this->euros($order['subtotal']))
            . ($order['discount'] > 0 ? $line('Descuento', '-' . $this->euros($order['discount'])) : '')
            . $line('Envío', $order['shipping'] > 0 ? $this->euros($order['shipping']) : 'Gratis')
            . $line('IVA', $this->euros($order['tax']))
            . '<tr><td style="padding:12px 0 0;font-size:18px;font-weight:bold">Total</td>'
            . '<td style="padding:12px 0 0;text-align:right;white-space:nowrap;font-size:18px;font-weight:bold">'
            . $this->euros($order['total']) . '</td></tr>'
            . '</table>'
            . '<p style="margin:24px 0 0;text-align:center"><a href="' . $link . '" style="display:inline-block;'
            . 'background:#111;color:#fff;text-decoration:none;font-weight:bold;padding:12px 28px;border-radius:999px">Ver mi pedido</a></p>'
            . '</div></div>'
            . '<p style="max-width:560px;margin:16px auto 0;color:#5a5a5a;font-size:12px;text-align:center">'
            . 'The Black Lotus TCG · Prototipo académico, sin actividad comercial real: no se ha cobrado nada.</p>'
            . '</div>';
    }

    // Con espacio duro para que el € no se baje de línea en el móvil
    private function euros(float $amount): string
    {
        return number_format($amount, 2, ',', '.') . "\u{00A0}€";
    }
}
