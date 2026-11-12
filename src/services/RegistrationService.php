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
    private const PASSWORD_MIN_LENGTH = 8;
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

        if ($email === '') {
            $errors['email'] = Translator::get('validation.required');
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = Translator::get('validation.email');
        } elseif ($this->users->findByEmail($email) !== null) {
            $errors['email'] = Translator::get('validation.email_taken');
        }

        if (mb_strlen($password) < self::PASSWORD_MIN_LENGTH) {
            $errors['password'] = Translator::get('validation.min_length', ['min' => self::PASSWORD_MIN_LENGTH]);
        }

        if ($passwordConfirm !== $password) {
            $errors['password_confirm'] = Translator::get('validation.password_mismatch');
        }

        if (!$termsAccepted) {
            $errors['terms'] = Translator::get('validation.terms');
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
