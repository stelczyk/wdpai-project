<?php

declare(strict_types=1);

namespace App\Controller;

final class SecurityController extends AppController
{
    public function login(): void
    {
        $this->render('login', [
            'pageTitle' => 'Logowanie',
            'showNav' => false,
            'scripts' => ['/js/auth.js'],
            'email' => '',
            'errors' => [],
            'formError' => null,
        ]);
    }

    public function register(): void
    {
        $this->render('register', [
            'pageTitle' => 'Rejestracja',
            'showNav' => false,
            'scripts' => ['/js/auth.js'],
            'name' => '',
            'email' => '',
            'errors' => [],
            'formError' => null,
        ]);
    }
}
