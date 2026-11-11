<?php

declare(strict_types=1);

namespace App;

/**
 * Sesja PHP z bezpiecznymi ustawieniami cookie. W sesji trzymamy wyłącznie id użytkownika –
 * rolę i dane zawsze czytamy z bazy, więc klient nie ma wpływu na swoje uprawnienia.
 */
final class Session
{
    private const USER_ID = 'user_id';

    public static function start(): void
    {
        if (session_status() === PHP_SESSION_ACTIVE) {
            return;
        }

        // strict_mode: PHP nie przyjmie identyfikatora sesji, którego sam nie wygenerował (session fixation).
        ini_set('session.use_strict_mode', '1');
        ini_set('session.use_only_cookies', '1');

        // „nocache” wysyła Cache-Control: no-store – przeglądarka nie trzyma kopii stron,
        // więc po wylogowaniu „Wstecz” pobierze stronę od nowa (już jako gość).
        session_cache_limiter('nocache');

        session_set_cookie_params([
            'lifetime' => 0,
            'path' => '/',
            'secure' => ($_SERVER['HTTPS'] ?? '') === 'on',
            'httponly' => true,
            'samesite' => 'Lax',
        ]);
        session_start();
    }

    public static function userId(): ?int
    {
        $id = $_SESSION[self::USER_ID] ?? null;

        return is_int($id) ? $id : null;
    }

    // Wylogowanie: czyścimy dane, usuwamy sesję po stronie serwera i wygaszamy cookie w przeglądarce.
    public static function destroy(): void
    {
        $_SESSION = [];

        $cookie = session_get_cookie_params();
        setcookie(session_name(), '', [
            'expires' => time() - 3600,
            'path' => $cookie['path'],
            'domain' => $cookie['domain'],
            'secure' => $cookie['secure'],
            'httponly' => $cookie['httponly'],
            'samesite' => $cookie['samesite'],
        ]);

        session_destroy();
    }

    // Nowy identyfikator sesji po zalogowaniu – stary (np. podrzucony przez atakującego) przestaje działać.
    public static function setUserId(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION[self::USER_ID] = $id;
    }
}
