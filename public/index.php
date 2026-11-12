<?php

declare(strict_types=1);

use App\Controller\ActivityController;
use App\Controller\CaptainController;
use App\Controller\DefaultController;
use App\Controller\ExpeditionController;
use App\Controller\SecurityController;
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
$routing->get('/expeditions', ExpeditionController::class, 'index');
$routing->get('/expeditions/{id}', ExpeditionController::class, 'show');
$routing->get('/expeditions/{id}/ranking', ExpeditionController::class, 'ranking');
$routing->get('/activities/new', ActivityController::class, 'create');
$routing->get('/captain/{id}', CaptainController::class, 'panel');

// Jedno miejsce obsługi błędów: kontrolowane błędy HTTP dostają swój status,
// a każdy inny wyjątek kończy się 500 bez pokazywania szczegółów użytkownikowi.
try {
    // CSRF sprawdzany centralnie dla każdego POST – nowa trasa nie może przypadkiem zostać bez ochrony.
    if ($_SERVER['REQUEST_METHOD'] === 'POST' && !Session::isValidCsrfToken($_POST['_csrf'] ?? null)) {
        throw new HttpException(403, 'Invalid CSRF token');
    }

    $routing->dispatch($_SERVER['REQUEST_METHOD'], $_SERVER['REQUEST_URI']);
} catch (HttpException $exception) {
    http_response_code($exception->getStatusCode());
    (new DefaultController())->error($exception->getStatusCode());
} catch (Throwable $exception) {
    error_log((string) $exception);
    http_response_code(500);
    (new DefaultController())->error(500);
}
