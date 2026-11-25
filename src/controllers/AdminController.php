<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Translator;

/** Strony administratora. Sama strona to szkielet – dane dociąga users.js z /api/users. */
final class AdminController extends AppController
{
    public function users(): void
    {
        $admin = $this->requireRole(User::ROLE_ADMIN);

        $this->render('admin-users', [
            'pageTitle' => Translator::get('admin.title'),
            'scripts' => ['/js/api.js', '/js/format.js', '/js/users.js'],
            'adminId' => $admin->id,
        ]);
    }
}
