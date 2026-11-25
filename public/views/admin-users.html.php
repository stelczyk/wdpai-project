<?php
// Panel użytkowników administratora (lab 11 – Fetch API). Serwer wysyła tylko szkielet: users.js pobiera
// listę z GET /api/users, klonuje <template> wiersza i wypełnia go przez textContent (dane nie są HTML-em).
// Blokada: PATCH /api/users/{id}/block, usunięcie: DELETE /api/users/{id} – oba z nagłówkiem X-CSRF-Token.
use App\Session;

$roles = ['USER', 'CAPTAIN', 'ADMIN'];
?>
<main class="expeditions-page admin-page" data-users-panel data-endpoint="/api/users"
      data-csrf-token="<?= htmlspecialchars(Session::csrfToken()) ?>" data-admin-id="<?= (int) $adminId ?>"
      data-msg-loading="<?= $t('admin.loading') ?>" data-msg-count="<?= $t('admin.count') ?>"
      data-msg-empty="<?= $t('admin.empty') ?>" data-msg-network="<?= $t('admin.error_network') ?>"
      data-msg-busy="<?= $t('admin.busy') ?>"
      data-msg-blocked="<?= $t('admin.status_blocked') ?>" data-msg-active="<?= $t('admin.status_active') ?>"
      data-msg-block="<?= $t('admin.block') ?>" data-msg-unblock="<?= $t('admin.unblock') ?>"
      data-msg-delete="<?= $t('admin.delete') ?>" data-msg-confirm="<?= $t('admin.delete_confirm') ?>"
      data-msg-done-blocked="<?= $t('admin.done_blocked') ?>" data-msg-done-unblocked="<?= $t('admin.done_unblocked') ?>"
      data-msg-done-deleted="<?= $t('admin.done_deleted') ?>"
      <?php foreach ($roles as $role): ?>data-msg-role-<?= strtolower($role) ?>="<?= $t('admin.role_' . $role) ?>" <?php endforeach; ?>>
    <section class="exp-hero" aria-labelledby="admin-title">
        <div class="exp-hero-text">
            <p class="eyebrow">
                <span class="dot dot--accent" aria-hidden="true"></span>
                <span class="eyebrow-accent"><?= $t('admin.eyebrow') ?></span>
            </p>
            <h1 id="admin-title"><?= $t('admin.title') ?></h1>
            <p class="lead"><?= $t('admin.lead') ?></p>
        </div>
    </section>

    <div class="users-toolbar">
        <p class="users-status" data-users-status role="status" aria-live="polite"><?= $t('admin.loading') ?></p>
        <button class="exp-button exp-button--ghost" type="button" data-users-refresh><?= $t('admin.refresh') ?></button>
    </div>

    <ul class="users-list" data-users-list aria-label="<?= $t('admin.list_label') ?>" aria-busy="true"></ul>

    <template data-user-row>
        <li class="user-row">
            <span class="activity-avatar" data-field="initials" aria-hidden="true"></span>
            <span class="user-identity">
                <strong data-field="name"></strong>
                <span class="user-email" data-field="email"></span>
            </span>
            <span class="badges user-badges">
                <span class="badge badge--role" data-field="role"></span>
                <span class="badge user-state" data-field="state"></span>
                <span class="badge badge--plain" data-field="you" hidden><?= $t('admin.you') ?></span>
                <span class="badge badge--plain" data-field="captain" hidden><?= $t('admin.captain') ?></span>
            </span>
            <span class="user-actions">
                <button class="icon-button" type="button" data-action="block">
                    <svg class="icon-lock" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 8 0v4"/>
                    </svg>
                    <svg class="icon-unlock" viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <rect x="5" y="11" width="14" height="10" rx="2"/><path d="M8 11V7a4 4 0 0 1 7.5-1.9"/>
                    </svg>
                </button>
                <button class="icon-button icon-button--danger" type="button" data-action="delete">
                    <svg viewBox="0 0 24 24" aria-hidden="true" focusable="false">
                        <path d="M4 7h16M10 11v6M14 11v6M6 7l1 13h10l1-13M9 7V4h6v3"/>
                    </svg>
                </button>
            </span>
        </li>
    </template>
</main>
