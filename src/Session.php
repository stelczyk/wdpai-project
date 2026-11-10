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

    // Nowy identyfikator sesji po zalogowaniu – stary (np. podrzucony przez atakującego) przestaje działać.
    public static function setUserId(int $id): void
    {
        session_regenerate_id(true);
        $_SESSION[self::USER_ID] = $id;
    }
}
