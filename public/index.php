<?php

declare(strict_types=1);

use App\Controller\ActivityController;
use App\Controller\CaptainController;
use App\Controller\DefaultController;
use App\Controller\ExpeditionController;
use App\Controller\SecurityController;
use App\Routing;

require_once dirname(__DIR__) . '/vendor/autoload.php';

$routing = new Routing();

$routing->get('/', DefaultController::class, 'index');
$routing->get('/login', SecurityController::class, 'login');
$routing->get('/register', SecurityController::class, 'register');
$routing->get('/expeditions', ExpeditionController::class, 'index');
$routing->get('/expeditions/{id}', ExpeditionController::class, 'show');
$routing->get('/expeditions/{id}/ranking', ExpeditionController::class, 'ranking');
$routing->get('/activities/new', ActivityController::class, 'create');
$routing->get('/captain/{id}', CaptainController::class, 'panel');

$routeFound = $routing->dispatch(
    $_SERVER['REQUEST_METHOD'],
    $_SERVER['REQUEST_URI'],
);

if (!$routeFound) {
    http_response_code(404);
    (new DefaultController())->notFound();
}
