<?php

declare(strict_types=1);

namespace App\Controller;

use App\Model\User;
use App\Service\UserAdminService;
use App\Translator;
use JsonException;

/**
 * API użytkowników dla panelu administratora (public/js/users.js). Odpowiada wyłącznie JSON-em.
 * Rolę sprawdza każda akcja po stronie serwera; CSRF dla PATCH/DELETE sprawdza centralnie index.php.
 */
final class UserApiController extends AppController
{
    // GET /api/users → 200 {data: [...]}
    public function index(): never
    {
        $this->requireRole(User::ROLE_ADMIN);

        $this->json(['data' => (new UserAdminService())->list()]);
    }

    // PATCH /api/users/{id}/block, body {"isBlocked": true|false} → 200 {data: {id, isBlocked}}
    public function block(int $id): never
    {
        $admin = $this->requireRole(User::ROLE_ADMIN);

        try {
            $payload = json_decode(file_get_contents('php://input') ?: '', true, 8, JSON_THROW_ON_ERROR);
        } catch (JsonException) {
            $this->json(['error' => Translator::get('admin.error_json')], 400);
        }

        if (!is_array($payload) || !is_bool($payload['isBlocked'] ?? null)) {
            $this->json(['error' => Translator::get('admin.error_is_blocked')], 422);
        }

        $result = (new UserAdminService())->setBlocked($admin, $id, $payload['isBlocked']);
        $this->failOn($result);

        $this->json(['data' => ['id' => $id, 'isBlocked' => $payload['isBlocked']]]);
    }

    // DELETE /api/users/{id} → 204 bez treści
    public function delete(int $id): never
    {
        $admin = $this->requireRole(User::ROLE_ADMIN);

        $this->failOn((new UserAdminService())->delete($admin, $id));

        http_response_code(204);
        exit;
    }

    // Wynik serwisu → kod HTTP i komunikat dla JS. OK przepuszcza dalej.
    private function failOn(string $result): void
    {
        match ($result) {
            UserAdminService::NOT_FOUND => $this->json(['error' => Translator::get('admin.error_not_found')], 404),
            UserAdminService::SELF => $this->json(['error' => Translator::get('admin.error_self')], 422),
            UserAdminService::CAPTAIN => $this->json(['error' => Translator::get('admin.error_captain')], 409),
            default => null,
        };
    }
}
