<?php

declare(strict_types=1);

namespace App\Controller;

use App\Translator;

final class SecurityController extends AppController
{
    public function login(): void
    {
        $this->render('login', [
            'pageTitle' => Translator::get('login.title'),
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
            'pageTitle' => Translator::get('register.title'),
            'showNav' => false,
            'scripts' => ['/js/auth.js'],
            'name' => '',
            'email' => '',
            'errors' => [],
            'formError' => null,
        ]);
    }
}
