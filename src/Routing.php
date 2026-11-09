<?php

declare(strict_types=1);

namespace App;

use App\Controller\AppController;

final class Routing
{
    // Największa wartość typu INT w PostgreSQL – większe id i tak nie istnieją w bazie.
    private const MAX_ID = 2147483647;

    private array $routes = [];

    public function get(string $path, string $controller, string $action): void
    {
        $this->add('GET', $path, $controller, $action);
    }

    public function post(string $path, string $controller, string $action): void
    {
        $this->add('POST', $path, $controller, $action);
    }

    private function add(
        string $method,
        string $path,
        string $controller,
        string $action,
    ): void {
        $this->routes[] = [
            'method' => $method,
            'pattern' => $this->createPattern($path),
            'controller' => $controller,
            'action' => $action,
        ];
    }

    // Zamienia fragmenty {nazwa} na nazwany parametr. Przyjmuje dowolny fragment adresu,
    // żeby /expeditions/abc trafiło do walidacji (400), a nie przepadło jako nieznana trasa (404).
    // Trasy statyczne (np. /expeditions/new) trzeba więc rejestrować przed trasą z {id}.
    private function createPattern(string $path): string
    {
        $parts = explode('/', $path);

        foreach ($parts as &$part) {
            if (str_starts_with($part, '{') && str_ends_with($part, '}')) {
                $part = '(?P<' . trim($part, '{}') . '>[^/]+)';
                continue;
            }

            $part = preg_quote($part, '~');
        }
        unset($part);

        return '~^' . implode('/', $parts) . '$~';
    }

    /**
     * @throws HttpException 400 przy nieprawidłowym id, 404 gdy żadna trasa nie pasuje.
     */
    public function dispatch(string $method, string $uri): void
    {
        $path = parse_url($uri, PHP_URL_PATH) ?: '/';

        foreach ($this->routes as $route) {
            if ($route['method'] !== $method) {
                continue;
            }

            if (!preg_match($route['pattern'], $path, $matches)) {
                continue;
            }

            $parameters = array_filter(
                $matches,
                static fn (int|string $key): bool => is_string($key),
                ARRAY_FILTER_USE_KEY,
            );

            $parameters = array_map($this->parseId(...), $parameters);

            $controllerClass = $route['controller'];
            $controller = new $controllerClass();

            if (!$controller instanceof AppController) {
                throw new \RuntimeException('Invalid controller');
            }

            $controller->{$route['action']}(...$parameters);

            return;
        }

        throw new HttpException(404, 'Route not found: ' . $method . ' ' . $path);
    }

    // Id musi być dodatnią liczbą całkowitą bez zer wiodących, mieszczącą się w INT bazy.
    private function parseId(string $value): int
    {
        if (preg_match('/^[1-9]\d{0,9}$/', $value) !== 1 || (int) $value > self::MAX_ID) {
            throw new HttpException(400, 'Invalid id: ' . $value);
        }

        return (int) $value;
    }
}
