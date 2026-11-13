<?php
declare(strict_types=1);

/**
 * Enrutador mínimo: asocia un método HTTP y una ruta a una función.
 * Las partes entre llaves son parámetros:
 *
 *   $router->patch('/orders/{code}/status', function (array $params): void { ... });
 */
final class Router
{
    private array $routes = [];

    public function get(string $pattern, callable $handler): void
    {
        $this->add('GET', $pattern, $handler);
    }

    public function post(string $pattern, callable $handler): void
    {
        $this->add('POST', $pattern, $handler);
    }

    public function patch(string $pattern, callable $handler): void
    {
        $this->add('PATCH', $pattern, $handler);
    }

    private function add(string $method, string $pattern, callable $handler): void
    {
        // '/orders/{code}/status'  ->  '#^/orders/(?P<code>[^/]+)/status$#'
        $regex = '#^' . preg_replace('#\{(\w+)\}#', '(?P<$1>[^/]+)', $pattern) . '$#';
        $this->routes[] = [$method, $regex, $handler];
    }

    public function dispatch(string $method, string $path): void
    {
        $pathExists = false;

        foreach ($this->routes as [$routeMethod, $regex, $handler]) {
            if (!preg_match($regex, $path, $matches)) {
                continue;
            }
            if ($routeMethod !== $method) {
                $pathExists = true;
                continue;
            }
            // Solo los parámetros con nombre: ['code' => 'PED-000014']
            $params = array_filter($matches, 'is_string', ARRAY_FILTER_USE_KEY);
            $handler(array_map('rawurldecode', $params));
            return;
        }

        if ($pathExists) {
            jsonError('Método no permitido', 405);
        }
        jsonError('Ruta no encontrada', 404);
    }
}
