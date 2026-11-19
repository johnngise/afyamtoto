<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';

AuthService::startSession();
$basePath = '..';
$controller = new AuthController($basePath);
$systemError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $controller->parentLogin($_POST);
    } catch (RuntimeException $error) {
        error_log('[AFYAMTOTO auth] ' . $error->getMessage());
        http_response_code(503);
        $systemError = 'Le service de connexion est momentanément indisponible. Vérifiez la configuration MySQL et réessayez.';
    }
}

$errors = $controller->errors();
$oldInput = $controller->oldInput();
$notice = AuthService::consumeFlash();
$authenticatedUser = AuthService::currentUser();
$csrfToken = CsrfMiddleware::token();

require dirname(__DIR__) . '/app/Views/auth/connexion.php';
