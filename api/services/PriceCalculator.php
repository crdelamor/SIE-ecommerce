<?php
declare(strict_types=1);

// Error del checkout con el código HTTP que debe devolver la API
final class CheckoutException extends RuntimeException
{
}

/**
 * Calcula los totales del carrito con los precios de la base de datos (nunca los del navegador).
 * Reglas del plan, igual que en seed.sql:
 *   - descuento: BIENVENIDA10 resta un 10 % del subtotal
 *   - envío: 4,95 €, gratis si el subtotal (sin IVA, antes del descuento) llega a 50 €
 *   - IVA: el de cada producto, sobre el precio ya con descuento; el envío no lleva IVA
 * Todo se calcula en céntimos para no tener errores de redondeo.
 */
final class PriceCalculator
{
    private const COUPON = 'BIENVENIDA10';
    private const COUPON_PERCENT = 10;
    private const SHIPPING = 495;
    private const FREE_SHIPPING_FROM = 5000;

    public function __construct(private OrderRepository $orders = new OrderRepository())
    {
    }

    // $items: [product_id => quantity]
    public function quote(array $items, ?string $coupon): array
    {
        if ($coupon !== null && strtoupper($coupon) !== self::COUPON) {
            throw new CheckoutException('El cupón no es válido', 400);
        }

        $products = $this->orders->findProducts(array_keys($items));

        $lines = [];
        $subtotal = 0;
        $taxSum = 0; // suma de línea x tipo de IVA, antes de aplicar el descuento

        foreach ($items as $productId => $quantity) {
            $product = $products[$productId] ?? null;
            if ($product === null) {
                throw new CheckoutException('Uno de los productos ya no está disponible', 400);
            }
            if ($quantity > (int) $product['stock']) {
                throw new CheckoutException("No hay stock suficiente de {$product['name']}", 409);
            }

            $unitPrice = (int) round($product['price'] * 100);
            $taxRate = (int) round($product['tax_rate'] * 100); // 21.00 -> 2100
            $lineTotal = $unitPrice * $quantity;

            $subtotal += $lineTotal;
            $taxSum += $lineTotal * $taxRate;

            $lines[] = [
                'product_id' => $productId,
                'name'       => $product['name'],
                'quantity'   => $quantity,
                'unit_price' => $unitPrice / 100,
                'tax_rate'   => $taxRate / 100,
                'line_total' => $lineTotal / 100,
            ];
        }

        $discount = $coupon !== null ? (int) round($subtotal * self::COUPON_PERCENT / 100) : 0;
        $shipping = $subtotal >= self::FREE_SHIPPING_FROM ? 0 : self::SHIPPING;
        // El IVA de cada línea baja en la misma proporción que el descuento
        $tax = (int) round($taxSum * ($subtotal - $discount) / $subtotal / 10000);
        $total = $subtotal - $discount + $shipping + $tax;

        return [
            'items'    => $lines,
            'coupon'   => $coupon !== null ? self::COUPON : null,
            'subtotal' => $subtotal / 100,
            'discount' => $discount / 100,
            'shipping' => $shipping / 100,
            'tax'      => $tax / 100,
            'total'    => $total / 100,
        ];
    }
}
