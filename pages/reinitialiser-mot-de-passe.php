<?php

declare(strict_types=1);

require_once dirname(__DIR__) . '/app/Services/AuthService.php';
require_once dirname(__DIR__) . '/app/Controllers/AuthController.php';
require_once dirname(__DIR__) . '/app/Middleware/CsrfMiddleware.php';

AuthService::startSession();
header('Cache-Control: no-store, private');
header('Referrer-Policy: no-referrer');
$basePath = '..';
$token = is_string($_POST['token'] ?? null)
    ? $_POST['token']
    : (is_string($_GET['token'] ?? null) ? $_GET['token'] : '');
$controller = new AuthController($basePath);
$systemError = null;

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'POST') {
    try {
        $controller->resetPassword($_POST);
    } catch (RuntimeException $error) {
        error_log('[AFYAMTOTO auth] ' . $error->getMessage());
        http_response_code(503);
        $systemError = 'Le service de réinitialisation est momentanément indisponible. Réessayez plus tard.';
    }
}

$errors = $controller->errors();
$csrfToken = CsrfMiddleware::token();
require dirname(__DIR__) . '/app/Views/auth/reinitialiser-mot-de-passe.php';
