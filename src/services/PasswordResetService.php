<?php

declare(strict_types=1);

namespace App\Service;

use App\Database;
use App\Env;
use App\Mailer;
use App\Repository\PasswordResetRepository;
use App\Repository\UserRepository;
use App\Translator;
use Throwable;

final class PasswordResetService
{
    private const TOKEN_LIFETIME = 3600;   // link ważny 1 godzinę

    public function __construct(
        private readonly UserRepository $users = new UserRepository(),
        private readonly PasswordResetRepository $resets = new PasswordResetRepository(),
        private readonly Mailer $mailer = new Mailer(),
    ) {
    }

    /**
     * Wysyła link resetu, jeśli konto istnieje. Nic nie zwraca – kontroler zawsze pokazuje
     * ten sam komunikat, więc formularza nie da się użyć do sprawdzania, kto ma konto.
     */
    public function requestReset(string $email): void
    {
        $user = $this->users->findByEmail($email);
        if ($user === null) {
            return;
        }

        // Token trafia tylko do maila; w bazie zostaje jego skrót.
        $token = bin2hex(random_bytes(32));
        $this->resets->create($user->id, hash('sha256', $token), self::TOKEN_LIFETIME);

        $link = rtrim(Env::get('APP_URL'), '/') . '/reset-password?token=' . $token;

        try {
            $this->mailer->send(
                $user->email,
                $user->name,
                Translator::get('reset.mail_subject'),
                Translator::get('reset.mail_body', ['name' => $user->name, 'link' => $link, 'minutes' => self::TOKEN_LIFETIME / 60]),
            );
        } catch (Throwable $exception) {
            // Błąd SMTP do logu; użytkownik widzi ten sam komunikat co zawsze (bez 500 i bez zdradzania konta).
            error_log('Password reset mail failed: ' . $exception->getMessage());
        }
    }

    public function isTokenValid(string $token): bool
    {
        return $this->resets->findValid(hash('sha256', $token)) !== null;
    }

    /**
     * Zmienia hasło i zużywa token w jednej transakcji: albo oba zapisy się udadzą, albo żaden.
     * Zwraca false, gdy token jest nieprawidłowy, wygasł albo został już użyty.
     */
    public function resetPassword(string $token, string $newPassword): bool
    {
        $reset = $this->resets->findValid(hash('sha256', $token));
        if ($reset === null) {
            return false;
        }

        $db = Database::connect();
        $db->beginTransaction();

        try {
            if (!$this->resets->markUsed($reset['id'])) {
                $db->rollBack();   // ktoś zużył token chwilę wcześniej

                return false;
            }

            $this->users->updatePassword($reset['userId'], password_hash($newPassword, PASSWORD_DEFAULT));
            $db->commit();
        } catch (Throwable $exception) {
            $db->rollBack();
            throw $exception;
        }

        return true;
    }
}
