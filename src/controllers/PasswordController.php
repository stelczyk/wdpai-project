<?php

declare(strict_types=1);

namespace App\Controller;

use App\Service\PasswordResetService;
use App\Service\RegistrationService;
use App\Translator;

/** Reset hasła: formularz e-maila → mail z linkiem → formularz nowego hasła. */
final class PasswordController extends AppController
{
    public function forgot(): void
    {
        $this->renderForgot(sent: ($_GET['sent'] ?? '') === '1');
    }

    public function sendLink(): void
    {
        $email = trim((string) ($_POST['email'] ?? ''));

        $error = match (true) {
            $email === '' => Translator::get('validation.required'),
            filter_var($email, FILTER_VALIDATE_EMAIL) === false => Translator::get('validation.email'),
            default => null,
        };

        if ($error !== null) {
            http_response_code(422);
            $this->renderForgot($email, ['email' => $error]);

            return;
        }

        (new PasswordResetService())->requestReset($email);

        // Ten sam komunikat niezależnie od tego, czy konto istnieje. Przekierowanie (PRG),
        // żeby odświeżenie strony nie wysłało maila ponownie.
        $this->redirect('/forgot-password?sent=1');
    }

    public function resetForm(): void
    {
        $token = (string) ($_GET['token'] ?? '');
        $this->renderReset($token, tokenValid: (new PasswordResetService())->isTokenValid($token));
    }

    public function reset(): void
    {
        $token = (string) ($_POST['token'] ?? '');
        $password = (string) ($_POST['password'] ?? '');
        $passwordConfirm = (string) ($_POST['password_confirm'] ?? '');
        $resets = new PasswordResetService();

        $errors = RegistrationService::validatePassword($password, $passwordConfirm);
        if ($errors !== [] && $resets->isTokenValid($token)) {
            http_response_code(422);
            $this->renderReset($token, tokenValid: true, errors: $errors);

            return;
        }

        if ($errors !== [] || !$resets->resetPassword($token, $password)) {
            http_response_code(400);
            $this->renderReset($token, tokenValid: false);

            return;
        }

        $this->redirect('/login?password_reset=1');
    }

    private function renderForgot(string $email = '', array $errors = [], bool $sent = false): void
    {
        $this->render('forgot-password', [
            'pageTitle' => Translator::get('reset.forgot_title'),
            'showNav' => false,
            'email' => $email,
            'errors' => $errors,
            'sent' => $sent,
        ]);
    }

    private function renderReset(string $token, bool $tokenValid, array $errors = []): void
    {
        $this->render('reset-password', [
            'pageTitle' => Translator::get('reset.new_title'),
            'showNav' => false,
            'scripts' => ['/js/auth.js'],
            'token' => $token,
            'tokenValid' => $tokenValid,
            'errors' => $errors,
        ]);
    }
}
