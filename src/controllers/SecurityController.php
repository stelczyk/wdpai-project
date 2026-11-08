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
            'scripts' => ['/js/login.js'],
            'email' => '',
            'errors' => [],
            'formError' => null,
        ]);
    }
}
