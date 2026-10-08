<?php
declare(strict_types=1);

/** Datos del formulario no válidos; $fields indica el error de cada campo. */
final class SupportValidationException extends RuntimeException
{
    public function __construct(public readonly array $fields)
    {
        parent::__construct('Revisa los campos marcados');
    }
}

/**
 * Validación y alta de incidencias de soporte (registra support.requested).
 */
final class SupportService
{
    /** Motivos de una tienda de cartas: clave que envía el formulario => texto del asunto. */
    public const REASONS = [
        'estado_conservacion' => 'Estado de conservación distinto',
        'idioma_version'      => 'Idioma o versión incorrectos',
        'dano_envio'          => 'Daño en el envío',
        'falta_producto'      => 'Falta producto',
        'otro'                => 'Otro',
    ];

    public function __construct(private SupportTicketRepository $tickets = new SupportTicketRepository())
    {
    }

    /**
     * Valida los datos, guarda la incidencia y registra el evento en una transacción.
     * Devuelve el id de la incidencia.
     *
     * El ticket solo se vincula al pedido si el código existe y el email coincide con el
     * del cliente; si no, se guarda igual con el código en el asunto. La respuesta es la
     * misma en los dos casos, así el formulario no sirve para averiguar qué pedidos existen.
     *
     * @throws SupportValidationException si algún campo no es válido
     */
    public function create(array $data): int
    {
        $input = $this->validate($data);

        $orderId = null;
        if ($input['order_code'] !== null) {
            $order = $this->tickets->findOrderByCode($input['order_code']);
            if ($order !== null && strcasecmp($order['email'], $input['email']) === 0) {
                $orderId = (int) $order['id'];
            }
        }

        $subject = self::REASONS[$input['reason']]
            . ($input['order_code'] !== null ? ' · ' . $input['order_code'] : '');

        $pdo = db();
        $pdo->beginTransaction();
        try {
            $ticketId = $this->tickets->create($orderId, $input['name'], $input['email'], $subject, $input['message']);

            // Sin nombre, email ni mensaje: para la analítica basta con el motivo y el pedido
            logEvent('support.requested', 'ticket', $ticketId, [
                'reason'       => $input['reason'],
                'order_code'   => $input['order_code'],
                'order_linked' => $orderId !== null,
            ]);

            $pdo->commit();
        } catch (Throwable $e) {
            $pdo->rollBack();
            throw $e;
        }

        return $ticketId;
    }

    /** Comprueba cada campo y devuelve los datos limpios, o lanza con todos los errores juntos. */
    private function validate(array $data): array
    {
        $text = fn (string $key): string => is_string($data[$key] ?? null) ? trim($data[$key]) : '';

        $input = [
            'name'       => $text('name'),
            'email'      => $text('email'),
            'order_code' => strtoupper($text('order_code')) ?: null,
            'reason'     => $text('reason'),
            'message'    => $text('message'),
        ];
        $errors = [];

        if ($input['name'] === '' || mb_strlen($input['name']) > 120) {
            $errors['name'] = 'Escribe tu nombre (máximo 120 caracteres).';
        }
        if (mb_strlen($input['email']) > 150 || filter_var($input['email'], FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = 'Escribe un email válido.';
        }
        if ($input['order_code'] !== null && !preg_match('/^PED-\d{6}$/', $input['order_code'])) {
            $errors['order_code'] = 'El código de pedido tiene el formato PED-000123.';
        }
        if (!array_key_exists($input['reason'], self::REASONS)) {
            $errors['reason'] = 'Elige un motivo.';
        }
        $length = mb_strlen($input['message']);
        if ($length < 10 || $length > 2000) {
            $errors['message'] = 'Cuéntanos qué ha pasado (entre 10 y 2000 caracteres).';
        }

        if ($errors) {
            throw new SupportValidationException($errors);
        }

        return $input;
    }
}
