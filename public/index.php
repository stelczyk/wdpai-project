<?php

declare(strict_types=1);

use App\Controller\ActivityController;
use App\Controller\AdminController;
use App\Controller\CaptainController;
use App\Controller\DefaultController;
use App\Controller\ExpeditionController;
use App\Controller\PasswordController;
use App\Controller\SecurityController;
use App\Controller\UserApiController;
use App\HttpException;
use App\Routing;
use App\Session;
use App\Translator;

require_once dirname(__DIR__) . '/vendor/autoload.php';

// Język ustalamy przed routingiem, żeby także strony błędów były w wybranym języku.
Translator::initFromRequest();
Session::start();

$routing = new Routing();

$routing->get('/', DefaultController::class, 'index');
$routing->get('/login', SecurityController::class, 'login');
$routing->post('/login', SecurityController::class, 'authenticate');
$routing->post('/logout', SecurityController::class, 'logout');
$routing->get('/register', SecurityController::class, 'register');
$routing->post('/register', SecurityController::class, 'store');
$routing->get('/forgot-password', PasswordController::class, 'forgot');
$routing->post('/forgot-password', PasswordController::class, 'sendLink');
$routing->get('/reset-password', PasswordController::class, 'resetForm');
$routing->post('/reset-password', PasswordController::class, 'reset');
$routing->get('/expeditions', ExpeditionController::class, 'index');
$routing->post('/expeditions/join', ExpeditionController::class, 'join');
// Trasa statyczna przed /expeditions/{id} – inaczej „new” trafiłoby do walidacji id (400).
$routing->get('/expeditions/new', ExpeditionController::class, 'create');
$routing->post('/expeditions', ExpeditionController::class, 'store');
$routing->get('/expeditions/{id}', ExpeditionController::class, 'show');
$routing->get('/expeditions/{id}/ranking', ExpeditionController::class, 'ranking');
$routing->get('/activities/new', ActivityController::class, 'create');
$routing->post('/activities', ActivityController::class, 'store');
$routing->get('/captain/{id}', CaptainController::class, 'panel');
// approve-all przed trasą z {activityId} – inaczej „approve-all” trafiłoby tam jako błędne id (400).
$routing->post('/captain/{id}/activities/approve-all', CaptainController::class, 'approveAll');
$routing->post('/captain/{id}/activities/{activityId}', CaptainController::class, 'decide');
// Panel administratora: strona HTML + API JSON, z którego korzysta public/js/users.js.
$routing->get('/admin/users', AdminController::class, 'users');
$routing->get('/api/users', UserApiController::class, 'index');
$routing->patch('/api/users/{id}/block', UserApiController::class, 'block');
$routing->delete('/api/users/{id}', UserApiController::class, 'delete');

// Jedno miejsce obsługi błędów: kontrolowane błędy HTTP dostają swój status,
// a każdy inny wyjątek kończy się 500 bez pokazywania szczegółów użytkownikowi.
try {
    // CSRF sprawdzany centralnie dla każdej metody zmieniającej dane (POST, PATCH, DELETE…) –
    // nowa trasa nie może przypadkiem zostać bez ochrony. GET/HEAD niczego nie zmieniają.
    // Formularze wysyłają token w polu _csrf, żądania fetch() – w nagłówku X-CSRF-Token.
    $csrfToken = $_POST['_csrf'] ?? $_SERVER['HTTP_X_CSRF_TOKEN'] ?? null;
    if (!in_array($_SERVER['REQUEST_METHOD'], ['GET', 'HEAD'], true) && !Session::isValidCsrfToken($csrfToken)) {
        throw new HttpException(403, 'Invalid CSRF token');
    }

    // Dane spoza UTF-8 odrzucamy od razu (400) – inaczej baza zwróciłaby błąd kodowania i 500.
    array_walk_recursive($_GET, $rejectInvalidUtf8 = static function (mixed $value): void {
        if (is_string($value) && !mb_check_encoding($value, 'UTF-8')) {
            throw new HttpException(400, 'Invalid UTF-8 in request');
        }
    });
    array_walk_recursive($_POST, $rejectInvalidUtf8);

    $routing->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (HttpException $exception) {
    http_response_code($exception->getStatusCode());
    (new DefaultController())->error($exception->getStatusCode());
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    (new DefaultController())->error(500);
}
