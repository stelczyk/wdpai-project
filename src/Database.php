<?php

declare(strict_types=1);

namespace App;

use PDO;

final class Database
{
    private static ?PDO $connection = null;

    /**
     * Zwraca jedno, współdzielone połączenie PDO – tworzone przy pierwszym wywołaniu.
     * Dane połączenia pochodzą ze zmiennych środowiskowych (.env), nie z kodu.
     */
    public static function connect(): PDO
    {
        if (self::$connection === null) {
            $dsn = sprintf(
                'pgsql:host=%s;port=%s;dbname=%s',
                Env::get('DB_HOST'),
                Env::get('DB_PORT'),
                Env::get('POSTGRES_DB'),
            );

            self::$connection = new PDO($dsn, Env::get('POSTGRES_USER'), Env::get('POSTGRES_PASSWORD'), [
                PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
                PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
                // Prawdziwe prepared statements po stronie PostgreSQL, bez emulacji w PHP.
                PDO::ATTR_EMULATE_PREPARES => false,
            ]);
        }

        return self::$connection;
    }

    /**
     * Wykonuje $work w jednej transakcji: wszystkie zapytania zapisują się razem albo żadne –
     * wyjątek w dowolnym kroku cofa całość (rollBack) i leci dalej. Zwraca wynik $work.
     *
     * @template T
     * @param callable(): T $work
     * @return T
     */
    public static function transaction(callable $work): mixed
    {
        $db = self::connect();
        $db->beginTransaction();

        try {
            $result = $work();
            $db->commit();

            return $result;
        } catch (\Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }
    }
}
