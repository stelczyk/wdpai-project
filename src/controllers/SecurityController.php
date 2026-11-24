<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\AuthService;
use App\Service\RegistrationService;
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
        $ip = (string) ($_SERVER['REMOTE_ADDR'] ?? '0.0.0.0');

        // Przy blokadzie hasła w ogóle nie sprawdzamy – nawet poprawne nie przejdzie, więc zgadywanie nic nie da.
        $lockedMinutes = $auth->lockedMinutes($email, $ip);
        if ($lockedMinutes > 0) {
            http_response_code(429);
            header('Retry-After: ' . $lockedMinutes * 60);
            $this->renderLogin($email, [], Translator::get('login.locked', ['minutes' => $lockedMinutes]));

            return;
        }

        $user = $auth->attempt($email, $password, $ip);

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
        if ((new AuthService())->currentUser() !== null) {
            $this->redirect(self::AFTER_LOGIN);
        }

        $this->renderRegister();
    }

    public function store(): void
    {
        $name = trim((string) ($_POST['name'] ?? ''));
        $email = trim((string) ($_POST['email'] ?? ''));
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $termsAccepted = ($_POST['terms'] ?? '') === '1';

        $registration = new RegistrationService();
        $errors = $registration->validate($name, $email, $password, $passwordConfirm, $termsAccepted);

        if ($errors !== []) {
            http_response_code(422);
            $this->renderRegister($name, $email, $errors);

            return;
        }

        // Ta sama odpowiedź dla nowego i zajętego adresu – formularz nie zdradza, kto ma konto.
        // Bez automatycznego logowania: inaczej różnica (zalogowany / nie) znów by to zdradzała.
        $registration->registerOrNotify($name, $email, $password);
        $this->redirect('/login?registered=1');
    }

    /** @return array<string, string> komunikaty błędów pól, klucz = nazwa pola */
    private function validateLogin(string $email, string $password): array
    {
        $errors = [];

        $emailError = RegistrationService::emailError($email);
        if ($emailError !== null) {
            $errors['email'] = $emailError;
        }

        // Dłuższego hasła nie da się ustawić, więc nie ma po co go weryfikować (limit długości wejścia).
        if ($password === '') {
            $errors['password'] = Translator::get('validation.required');
        } elseif (strlen($password) > RegistrationService::PASSWORD_MAX_BYTES) {
            $errors['password'] = Translator::get('validation.password_max', ['max' => RegistrationService::PASSWORD_MAX_BYTES]);
        }

        return $errors;
    }

    // Hasła nigdy nie odsyłamy do formularza – wracają tylko imię i e-mail.
    private function renderRegister(string $name = '', string $email = '', array $errors = []): void
    {
        $this->render('register', [
            'pageTitle' => Translator::get('register.title'),
            'showNav' => false,
            'scripts' => ['/js/auth.js'],
            'name' => $name,
            'email' => $email,
            'errors' => $errors,
            'formError' => null,
        ]);
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
            // Po zmianie hasła PasswordController przekierowuje tu z ?password_reset=1.
            'passwordChanged' => ($_GET['password_reset'] ?? '') === '1',
            'registered' => ($_GET['registered'] ?? '') === '1',
        ]);
    }
}
