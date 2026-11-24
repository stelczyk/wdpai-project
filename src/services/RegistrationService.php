<?php

declare(strict_types=1);

namespace App\Service;

use App\Model\User;
use App\Repository\UserRepository;
use App\Translator;
use PDOException;

final class RegistrationService
{
    private const NAME_MAX_LENGTH = 50;      // = VARCHAR(50) w tabeli users
    private const EMAIL_MAX_LENGTH = 255;    // = VARCHAR(255) w tabeli users
    private const PASSWORD_MIN_LENGTH = 8;
    // bcrypt (PASSWORD_DEFAULT) bierze pod uwagę tylko pierwsze 72 bajty – dłuższe hasło byłoby złudzeniem.
    public const PASSWORD_MAX_BYTES = 72;
    private const UNIQUE_VIOLATION = '23505'; // kod błędu PostgreSQL: naruszenie UNIQUE

    public function __construct(private readonly UserRepository $users = new UserRepository())
    {
    }

    /** @return array<string, string> błędy pól, klucz = nazwa pola w formularzu */
    public function validate(string $name, string $email, string $password, string $passwordConfirm, bool $termsAccepted): array
    {
        $errors = [];

        if ($name === '') {
            $errors['name'] = Translator::get('validation.required');
        } elseif (mb_strlen($name) > self::NAME_MAX_LENGTH) {
            $errors['name'] = Translator::get('validation.max_length', ['max' => self::NAME_MAX_LENGTH]);
        }

        $emailError = self::emailError($email);
        if ($emailError !== null) {
            $errors['email'] = $emailError;
        }

        $errors += self::validatePassword($password, $passwordConfirm);

        if (!$termsAccepted) {
            $errors['terms'] = Translator::get('validation.terms');
        }

        return $errors;
    }

    /** Reguły adresu e-mail wspólne dla logowania, rejestracji i resetu hasła. Null = poprawny. */
    public static function emailError(string $email): ?string
    {
        return match (true) {
            $email === '' => Translator::get('validation.required'),
            mb_strlen($email) > self::EMAIL_MAX_LENGTH => Translator::get('validation.max_length', ['max' => self::EMAIL_MAX_LENGTH]),
            filter_var($email, FILTER_VALIDATE_EMAIL) === false => Translator::get('validation.email'),
            default => null,
        };
    }

    /** Reguły hasła wspólne dla rejestracji i resetu hasła. */
    public static function validatePassword(string $password, string $passwordConfirm): array
    {
        $errors = [];

        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors['password'] = Translator::get('validation.min_length', ['min' => self::PASSWORD_MIN_LENGTH]);
        } elseif (strlen($password) > self::PASSWORD_MAX_BYTES) {
            $errors['password'] = Translator::get('validation.password_max', ['max' => self::PASSWORD_MAX_BYTES]);
        }

        if ($passwordConfirm !== $password) {
            $errors['password_confirm'] = Translator::get('validation.password_mismatch');
        }

        return $errors;
    }

    /**
     * Zapisuje konto z zahashowanym hasłem. Zwraca null, gdy e-mail zajęto w międzyczasie
     * (dwie rejestracje naraz) – wtedy unikalny indeks w bazie odrzuca drugi INSERT.
     */
    public function register(string $name, string $email, string $password): ?User
    {
        try {
            return $this->users->create($email, $name, password_hash($password, PASSWORD_DEFAULT));
        } catch (PDOException $exception) {
            if ($exception->getCode() === self::UNIQUE_VIOLATION) {
                return null;
            }
            throw $exception;
        }
    }
}
