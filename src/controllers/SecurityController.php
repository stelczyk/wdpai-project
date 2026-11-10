<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Translator;

final class SecurityController extends AppController
{
    private const AFTER_LOGIN = '/expeditions';

    public function login(): void
    {
        if ((new AuthService())->currentUser() !== null) {
            $this->redirect(self::AFTER_LOGIN);
        }

        $this->renderLogin();
    }

    public function authenticate(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');

        $errors = $this->validateLogin($email, $password);
        if ($errors !== []) {
            http_response_code(422);
            $this->renderLogin($email, $errors);

            return;
        }

        $auth = new AuthService();
        $user = $auth->attempt($email, $password);

        // Jeden komunikat dla złego e-maila i złego hasła – nie zdradzamy, które konta istnieją.
        if ($user === null) {
            http_response_code(401);
            $this->renderLogin($email, [], Translator::get('login.invalid_credentials'));

            return;
        }

        $auth->login($user);
        $this->redirect(self::AFTER_LOGIN);
    }

    // Tylko POST: zwykły link (GET) mógłby zostać wywołany np. przez obrazek na obcej stronie.
    public function logout(): void
    {
        (new AuthService())->logout();
        $this->redirect('/login');
    }

    public function register(): void
    {
        $this->render('register', [
            'pageTitle' => Translator::get('register.title'),
            'showNav' => false,
            'scripts' => ['/js/auth.js'],
            'name' => '',
            'email' => '',
            'errors' => [],
            'formError' => null,
        ]);
    }

    /** @return array<string, string> komunikaty błędów pól, klucz = nazwa pola */
    private function validateLogin(string $email, string $password): array
    {
        $errors = [];

        if ($email === '') {
            $errors['email'] = Translator::get('validation.required');
        } elseif (filter_var($email, FILTER_VALIDATE_EMAIL) === false) {
            $errors['email'] = Translator::get('validation.email');
        }

        if ($password === '') {
            $errors['password'] = Translator::get('validation.required');
        }

        return $errors;
    }

    // Hasła nigdy nie odsyłamy do formularza – wraca tylko e-mail.
    private function renderLogin(string $email = '', array $errors = [], ?string $formError = null): void
    {
        $this->render('login', [
            'pageTitle' => Translator::get('login.title'),
            'showNav' => false,
            'scripts' => ['/js/auth.js'],
            'email' => $email,
            'errors' => $errors,
            'formError' => $formError,
        ]);
    }
}
