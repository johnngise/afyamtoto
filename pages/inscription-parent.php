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
        $controller->parentRegistration($_POST);
    } catch (RuntimeException $error) {
        error_log('[AFYAMTOTO auth] ' . $error->getMessage());
        http_response_code(503);
        $systemError = 'La création du compte est momentanément indisponible. Vérifiez la configuration MySQL et réessayez.';
    }
}

$errors = $controller->errors();
$oldInput = $controller->oldInput();
$notice = AuthService::consumeFlash();
$authenticatedUser = AuthService::currentUser();
$csrfToken = CsrfMiddleware::token();

require dirname(__DIR__) . '/app/Views/auth/inscription-parent.php';
